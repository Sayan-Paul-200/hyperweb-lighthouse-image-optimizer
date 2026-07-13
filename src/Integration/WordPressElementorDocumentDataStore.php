<?php
/**
 * WordPress-backed Elementor document-data store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Reads and decodes Elementor document data from the canonical post-meta source.
 */
final class WordPressElementorDocumentDataStore implements ElementorDocumentDataStoreInterface {

	/**
	 * Canonical Elementor document-data meta key.
	 *
	 * @var string
	 */
	public const META_KEY = '_elementor_data';

	/**
	 * Read one Elementor document.
	 *
	 * @param int $document_id Post/document ID.
	 * @return ElementorDocumentData
	 */
	public function read_document( int $document_id ): ElementorDocumentData {
		$document_id = max( 0, $document_id );

		if ( $document_id < 1 || ! function_exists( 'get_post_meta' ) ) {
			return ElementorDocumentData::missing();
		}

		$raw = \get_post_meta( $document_id, self::META_KEY, true );

		if ( '' === $raw || null === $raw || array() === $raw ) {
			return ElementorDocumentData::missing();
		}

		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );

			if ( ! is_array( $decoded ) || ! $this->is_list_array( $decoded ) ) {
				return ElementorDocumentData::invalid();
			}

			return ElementorDocumentData::valid( $this->normalize_elements( $decoded ) );
		}

		if ( is_array( $raw ) && $this->is_list_array( $raw ) ) {
			return ElementorDocumentData::valid( $this->normalize_elements( $raw ) );
		}

		return ElementorDocumentData::invalid();
	}

	/**
	 * Normalize one element list recursively.
	 *
	 * @param array<int,mixed> $elements Raw Elementor elements.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_elements( array $elements ): array {
		$normalized = array();

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$normalized[] = array(
				'id'         => isset( $element['id'] ) && is_scalar( $element['id'] ) ? trim( (string) $element['id'] ) : '',
				'elType'     => isset( $element['elType'] ) && is_scalar( $element['elType'] ) ? trim( (string) $element['elType'] ) : '',
				'widgetType' => isset( $element['widgetType'] ) && is_scalar( $element['widgetType'] ) ? trim( (string) $element['widgetType'] ) : '',
				'settings'   => isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array(),
				'elements'   => isset( $element['elements'] ) && is_array( $element['elements'] ) ? $this->normalize_elements( $element['elements'] ) : array(),
			);
		}

		return $normalized;
	}

	/**
	 * Whether one array uses zero-based sequential numeric keys.
	 *
	 * @param array<mixed> $value Candidate array.
	 * @return bool
	 */
	private function is_list_array( array $value ): bool {
		$index = 0;

		foreach ( array_keys( $value ) as $key ) {
			if ( $key !== $index ) {
				return false;
			}

			++$index;
		}

		return true;
	}
}
