<?php
/**
 * Attachment image source extractor.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Extracts normalized original image candidates from fallback image markup.
 */
final class AttachmentImageSourceExtractor {

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Create extractor.
	 *
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 */
	public function __construct( ImageMarkupAnalyzerInterface $analyzer ) {
		$this->analyzer = $analyzer;
	}

	/**
	 * Extract normalized original candidates from one fallback image fragment.
	 *
	 * @param string   $html Fallback image HTML.
	 * @param int|null $known_width Known width from runtime size facts.
	 * @return AttachmentImageSourceExtraction
	 */
	public function extract( string $html, ?int $known_width = null ): AttachmentImageSourceExtraction {
		$analysis = $this->analyzer->analyze( $html );

		if ( ! $analysis->is_renderable_img() ) {
			return new AttachmentImageSourceExtraction( array() );
		}

		$srcset = $this->attribute_value( $html, 'srcset' );
		$sources = $this->parse_srcset( $srcset );

		if ( array() === $sources ) {
			$src   = $this->attribute_value( $html, 'src' );
			$width = $this->positive_int( $known_width );

			if ( null === $width ) {
				$width = $this->positive_int( $this->attribute_value( $html, 'width' ) );
			}

			if ( null !== $width && null !== $src && '' !== trim( $src ) ) {
				$sources = array(
					$width => array(
						'url'        => trim( $src ),
						'descriptor' => 'w',
						'value'      => $width,
					),
				);
			}
		}

		return new AttachmentImageSourceExtraction( $sources );
	}

	/**
	 * Parse one conservative core-style srcset string.
	 *
	 * @param string|null $srcset Raw srcset.
	 * @return array<int,array<string,mixed>>
	 */
	private function parse_srcset( ?string $srcset ): array {
		if ( null === $srcset || '' === trim( $srcset ) ) {
			return array();
		}

		$sources = array();
		$parts   = preg_split( '/\s*,\s*/', trim( $srcset ) );

		if ( ! is_array( $parts ) ) {
			return array();
		}

		foreach ( $parts as $part ) {
			$part = trim( (string) $part );

			if ( '' === $part ) {
				continue;
			}

			if ( 1 !== preg_match( '/^(.+?)\s+([1-9]\d*)w$/', $part, $matches ) ) {
				continue;
			}

			$url   = trim( $matches[1] );
			$width = isset( $matches[2] ) ? (int) $matches[2] : 0;

			if ( '' === $url || $width < 1 || isset( $sources[ $width ] ) ) {
				continue;
			}

			$sources[ $width ] = array(
				'url'        => $url,
				'descriptor' => 'w',
				'value'      => $width,
			);
		}

		return $sources;
	}

	/**
	 * Extract one raw attribute value exactly from the image fragment.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function attribute_value( string $html, string $attribute ): ?string {
		$pattern = sprintf(
			'/\b%s\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
			preg_quote( $attribute, '/' )
		);

		if ( 1 !== preg_match( $pattern, $html, $matches ) ) {
			return null;
		}

		foreach ( array( 1, 2, 3 ) as $index ) {
			if ( array_key_exists( $index, $matches ) ) {
				return $matches[ $index ];
			}
		}

		return null;
	}

	/**
	 * Normalize one positive integer candidate.
	 *
	 * @param mixed $value Value.
	 * @return int|null
	 */
	private function positive_int( $value ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$int = (int) $value;

		return $int > 0 ? $int : null;
	}
}
