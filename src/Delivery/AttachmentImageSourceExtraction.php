<?php
/**
 * Attachment image source extraction result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries normalized original image candidates extracted from fallback markup.
 */
final class AttachmentImageSourceExtraction {

	/**
	 * Width-keyed original sources.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $sources;

	/**
	 * Create extraction result.
	 *
	 * @param array<int|string,mixed> $sources Sources.
	 */
	public function __construct( array $sources ) {
		$this->sources = $this->normalize_sources( $sources );
	}

	/**
	 * Whether any sources were extracted.
	 *
	 * @return bool
	 */
	public function has_sources(): bool {
		return array() !== $this->sources;
	}

	/**
	 * Get extracted sources.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function sources(): array {
		return $this->sources;
	}

	/**
	 * Serialize extraction result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'sources' => $this->sources,
		);
	}

	/**
	 * Normalize one source collection.
	 *
	 * @param array<int|string,mixed> $sources Sources.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_sources( array $sources ): array {
		$normalized = array();

		foreach ( $sources as $source ) {
			if ( ! is_array( $source ) ) {
				continue;
			}

			$url        = isset( $source['url'] ) && is_scalar( $source['url'] ) ? trim( (string) $source['url'] ) : '';
			$descriptor = isset( $source['descriptor'] ) && is_scalar( $source['descriptor'] ) ? trim( (string) $source['descriptor'] ) : '';
			$value      = isset( $source['value'] ) && is_numeric( $source['value'] ) ? (int) $source['value'] : 0;

			if ( '' === $url || 'w' !== $descriptor || $value < 1 || isset( $normalized[ $value ] ) ) {
				continue;
			}

			$normalized[ $value ] = array(
				'url'        => $url,
				'descriptor' => 'w',
				'value'      => $value,
			);
		}

		return $normalized;
	}
}
