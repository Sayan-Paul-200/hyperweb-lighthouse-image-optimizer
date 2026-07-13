<?php
/**
 * Late-discovered critical image locator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Locates one uniquely matched attachment-backed IMG fragment from current singular post content.
 */
final class LateDiscoveredCriticalImageLocator {

	/**
	 * Runtime seam.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Create locator.
	 *
	 * @param AttachmentImageRuntimeInterface $runtime Runtime seam.
	 * @param ImageMarkupAnalyzerInterface    $analyzer Markup analyzer.
	 */
	public function __construct( AttachmentImageRuntimeInterface $runtime, ImageMarkupAnalyzerInterface $analyzer ) {
		$this->runtime  = $runtime;
		$this->analyzer = $analyzer;
	}

	/**
	 * Locate one unique content-image fragment for the requested attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return LateDiscoveredCriticalImageMatch|null
	 */
	public function locate( int $attachment_id ): ?LateDiscoveredCriticalImageMatch {
		if ( $attachment_id < 1 ) {
			return null;
		}

		$content = $this->runtime->current_singular_post_content();

		if ( '' === trim( $content ) ) {
			return null;
		}

		$fragments = $this->img_fragments( $content );
		$matches   = array();

		foreach ( $fragments as $fragment ) {
			$analysis = $this->analyzer->analyze( $fragment );

			if ( ! $analysis->is_renderable_img() ) {
				continue;
			}

			if ( $attachment_id === $this->attachment_id_from_fragment( $fragment ) ) {
				$matches[] = $fragment;
			}
		}

		if ( 1 !== count( $matches ) ) {
			return null;
		}

		return new LateDiscoveredCriticalImageMatch( $attachment_id, $matches[0] );
	}

	/**
	 * Extract IMG fragments conservatively from content.
	 *
	 * @param string $content Raw post content.
	 * @return string[]
	 */
	private function img_fragments( string $content ): array {
		$found = preg_match_all( '/<img\b[^>]*>/i', $content, $matches );

		if ( ! is_int( $found ) || $found < 1 ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static function ( string $fragment ): string {
						return trim( $fragment );
					},
					$matches[0]
				),
				static function ( string $fragment ): bool {
					return '' !== $fragment;
				}
			)
		);
	}

	/**
	 * Resolve one attachment ID from a raw IMG fragment.
	 *
	 * @param string $fragment IMG fragment.
	 * @return int
	 */
	private function attachment_id_from_fragment( string $fragment ): int {
		foreach ( array( 'data-id', 'data-attachment-id', 'attachment_id' ) as $attribute ) {
			if ( 1 === preg_match( $this->attribute_pattern( $attribute ), $fragment, $matches ) ) {
				foreach ( array( 1, 2, 3 ) as $index ) {
					if ( isset( $matches[ $index ] ) && '' !== $matches[ $index ] ) {
						return max( 0, (int) $matches[ $index ] );
					}
				}
			}
		}

		if ( 1 === preg_match( '/\bclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $fragment, $matches ) ) {
			$class_name = '';

			foreach ( array( 1, 2 ) as $index ) {
				if ( isset( $matches[ $index ] ) && '' !== $matches[ $index ] ) {
					$class_name = $matches[ $index ];
					break;
				}
			}

			if ( is_string( $class_name ) && 1 === preg_match( '/\bwp-image-(\d+)\b/', $class_name, $class_matches ) ) {
				return max( 0, (int) $class_matches[1] );
			}
		}

		return 0;
	}

	/**
	 * Build one quoted-or-unquoted numeric attribute pattern.
	 *
	 * @param string $attribute Attribute name.
	 * @return string
	 */
	private function attribute_pattern( string $attribute ): string {
		return sprintf(
			'/\b%s\s*=\s*(?:"([1-9]\d*)"|\'([1-9]\d*)\'|([1-9]\d*))/i',
			preg_quote( $attribute, '/' )
		);
	}
}
