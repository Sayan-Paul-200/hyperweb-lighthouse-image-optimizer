<?php
/**
 * Trusted attachment marker parser.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Extracts trusted attachment IDs from raw IMG fragments conservatively.
 */
final class TrustedAttachmentMarkerParser {

	/**
	 * Resolve one trusted attachment ID from an IMG fragment.
	 *
	 * @param string $fragment IMG fragment.
	 * @return int
	 */
	public function parse_attachment_id( string $fragment ): int {
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
