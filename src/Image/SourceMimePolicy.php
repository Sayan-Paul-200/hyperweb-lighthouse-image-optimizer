<?php
/**
 * Source MIME policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Centralizes initial source MIME eligibility decisions.
 */
final class SourceMimePolicy {

	public const MIME_JPEG = 'image/jpeg';
	public const MIME_PNG  = 'image/png';
	public const MIME_WEBP = 'image/webp';
	public const MIME_GIF  = 'image/gif';
	public const MIME_SVG  = 'image/svg+xml';
	public const MIME_AVIF = 'image/avif';

	public const TARGET_WEBP = 'webp';
	public const TARGET_AVIF = 'avif';

	/**
	 * Normalize a MIME type.
	 *
	 * @param string|null $mime_type MIME type.
	 * @return string|null
	 */
	public function normalize_mime( ?string $mime_type ): ?string {
		if ( null === $mime_type || '' === trim( $mime_type ) ) {
			return null;
		}

		$mime_type = strtolower( trim( $mime_type ) );

		if ( 'image/jpg' === $mime_type || 'image/pjpeg' === $mime_type ) {
			return self::MIME_JPEG;
		}

		if ( 'image/svg' === $mime_type ) {
			return self::MIME_SVG;
		}

		return $mime_type;
	}

	/**
	 * Determine whether a MIME type is an initially supported source.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool
	 */
	public function is_supported_source_mime( string $mime_type ): bool {
		return in_array(
			$this->normalize_mime( $mime_type ),
			array(
				self::MIME_JPEG,
				self::MIME_PNG,
				self::MIME_WEBP,
			),
			true
		);
	}

	/**
	 * Determine whether animation detection is required.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool
	 */
	public function requires_animation_detection( string $mime_type ): bool {
		return in_array(
			$this->normalize_mime( $mime_type ),
			array(
				self::MIME_GIF,
				self::MIME_WEBP,
			),
			true
		);
	}

	/**
	 * Get deterministic future target formats allowed for a source MIME.
	 *
	 * @param string $mime_type MIME type.
	 * @return string[]
	 */
	public function target_formats_for_source_mime( string $mime_type ): array {
		$mime_type = $this->normalize_mime( $mime_type );

		if ( self::MIME_JPEG === $mime_type || self::MIME_PNG === $mime_type ) {
			return array( self::TARGET_WEBP, self::TARGET_AVIF );
		}

		if ( self::MIME_WEBP === $mime_type ) {
			return array( self::TARGET_AVIF );
		}

		return array();
	}
}
