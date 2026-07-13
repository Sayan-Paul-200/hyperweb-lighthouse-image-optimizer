<?php
/**
 * WordPress-backed image markup analyzer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Uses WordPress HTML parsing utilities to inspect one image fragment conservatively.
 */
final class WordPressImageMarkupAnalyzer implements ImageMarkupAnalyzerInterface {

	/**
	 * Analyze one image fragment.
	 *
	 * @param string $html Markup fragment.
	 * @return ImageMarkupAnalysis
	 */
	public function analyze( string $html ): ImageMarkupAnalysis {
		$html = trim( $html );

		if ( '' === $html || ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return ImageMarkupAnalysis::invalid();
		}

		$processor = new \WP_HTML_Tag_Processor( $html );

		if ( ! method_exists( $processor, 'next_tag' ) || ! $processor->next_tag() ) {
			return ImageMarkupAnalysis::invalid();
		}

		if ( $this->paused_at_incomplete_token( $processor ) || $this->is_tag_closer( $processor ) ) {
			return ImageMarkupAnalysis::invalid();
		}

		$tag = strtoupper( (string) $this->get_tag( $processor ) );

		if ( 'PICTURE' === $tag ) {
			return ImageMarkupAnalysis::already_picture();
		}

		if ( 'IMG' !== $tag || ! $this->is_single_img_fragment( $html ) ) {
			return ImageMarkupAnalysis::invalid();
		}

		return ImageMarkupAnalysis::renderable(
			$this->extract_attribute_value( $html, 'sizes' ),
			$this->extract_attribute_value( $html, 'loading' ),
			$this->extract_attribute_value( $html, 'fetchpriority' ),
			$this->extract_attribute_value( $html, 'decoding' )
		);
	}

	/**
	 * Whether the processor paused at an incomplete token.
	 *
	 * @param object $processor Tag processor.
	 * @return bool
	 */
	private function paused_at_incomplete_token( $processor ): bool {
		return method_exists( $processor, 'paused_at_incomplete_token' ) && (bool) $processor->paused_at_incomplete_token();
	}

	/**
	 * Whether the current token is a closer.
	 *
	 * @param object $processor Tag processor.
	 * @return bool
	 */
	private function is_tag_closer( $processor ): bool {
		return method_exists( $processor, 'is_tag_closer' ) && (bool) $processor->is_tag_closer();
	}

	/**
	 * Get the current tag name.
	 *
	 * @param object $processor Tag processor.
	 * @return string
	 */
	private function get_tag( $processor ): string {
		return method_exists( $processor, 'get_tag' ) ? (string) $processor->get_tag() : '';
	}

	/**
	 * Whether markup is exactly one standalone image fragment.
	 *
	 * @param string $html Markup fragment.
	 * @return bool
	 */
	private function is_single_img_fragment( string $html ): bool {
		return 1 === preg_match( '/^<img\b(?:(?:"[^"]*"|\'[^\']*\'|[^\'">])*)\/?>$/i', trim( $html ) );
	}

	/**
	 * Extract an original attribute value exactly from the fragment.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function extract_attribute_value( string $html, string $attribute ): ?string {
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
}
