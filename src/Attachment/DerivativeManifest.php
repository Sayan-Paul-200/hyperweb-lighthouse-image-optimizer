<?php
/**
 * Derivative manifest value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries the plugin-owned `_hwlio_derivatives` manifest.
 */
final class DerivativeManifest {

	public const SCHEMA_VERSION      = 1;
	public const FORMAT_STATUS_READY = 'ready';

	/**
	 * Attachment fingerprint.
	 *
	 * @var AttachmentFingerprint|null
	 */
	private $fingerprint;

	/**
	 * Updated timestamp.
	 *
	 * @var int
	 */
	private $updated_at;

	/**
	 * Manifest sizes.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $sizes;

	/**
	 * Create manifest.
	 *
	 * @param AttachmentFingerprint|null        $fingerprint Fingerprint.
	 * @param int                               $updated_at Updated timestamp.
	 * @param array<string,array<string,mixed>> $sizes Sizes.
	 */
	public function __construct( ?AttachmentFingerprint $fingerprint = null, int $updated_at = 0, array $sizes = array() ) {
		$this->fingerprint = $fingerprint;
		$this->updated_at  = max( 0, $updated_at );
		$this->sizes       = $this->normalize_sizes( $sizes );
	}

	/**
	 * Build empty manifest.
	 *
	 * @return self
	 */
	public static function empty(): self {
		return new self();
	}

	/**
	 * Get fingerprint.
	 *
	 * @return AttachmentFingerprint|null
	 */
	public function fingerprint(): ?AttachmentFingerprint {
		return $this->fingerprint;
	}

	/**
	 * Get updated timestamp.
	 *
	 * @return int
	 */
	public function updated_at(): int {
		return $this->updated_at;
	}

	/**
	 * Get sizes.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function sizes(): array {
		return $this->sizes;
	}

	/**
	 * Whether manifest has ready derivative entries.
	 *
	 * @return bool
	 */
	public function has_derivatives(): bool {
		foreach ( $this->sizes as $size ) {
			if ( isset( $size['formats'] ) && is_array( $size['formats'] ) && array() !== $size['formats'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get ready formats from all sizes.
	 *
	 * @return string[]
	 */
	public function ready_formats(): array {
		$seen = array();

		foreach ( $this->sizes as $size ) {
			$formats = isset( $size['formats'] ) && is_array( $size['formats'] ) ? $size['formats'] : array();

			foreach ( array_keys( $formats ) as $format ) {
				if ( is_string( $format ) && in_array( $format, AttachmentStatus::formats(), true ) ) {
					$seen[ $format ] = true;
				}
			}
		}

		return array_values(
			array_filter(
				AttachmentStatus::formats(),
				static function ( string $format ) use ( $seen ): bool {
					return isset( $seen[ $format ] );
				}
			)
		);
	}

	/**
	 * Build a copy with new data.
	 *
	 * @param AttachmentFingerprint|null        $fingerprint Fingerprint.
	 * @param int                               $updated_at Updated timestamp.
	 * @param array<string,array<string,mixed>> $sizes Sizes.
	 * @return self
	 */
	public function with_data( ?AttachmentFingerprint $fingerprint, int $updated_at, array $sizes ): self {
		return new self( $fingerprint, $updated_at, $sizes );
	}

	/**
	 * Serialize manifest.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'fingerprint'    => $this->fingerprint instanceof AttachmentFingerprint ? $this->fingerprint->to_array() : null,
			'updated_at'     => $this->updated_at,
			'sizes'          => $this->sizes,
		);
	}

	/**
	 * Normalize sizes by key.
	 *
	 * @param array<string,array<string,mixed>> $sizes Sizes.
	 * @return array<string,array<string,mixed>>
	 */
	private function normalize_sizes( array $sizes ): array {
		$normalized = array();

		foreach ( $sizes as $size_name => $size ) {
			if ( ! is_string( $size_name ) || '' === trim( $size_name ) || ! is_array( $size ) ) {
				continue;
			}

			$normalized[ substr( trim( $size_name ), 0, 64 ) ] = $size;
		}

		ksort( $normalized );

		return $normalized;
	}
}
