<?php
/**
 * Cache invalidation request payload.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;

/**
 * Carries safe derivative state-change data for cache integrations.
 */
final class CacheInvalidationRequest {

	public const EVENT_DERIVATIVES_SAVED   = 'derivatives_saved';
	public const EVENT_DERIVATIVES_DELETED = 'derivatives_deleted';

	/**
	 * Event name.
	 *
	 * @var string
	 */
	private $event;

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Relative derivative paths.
	 *
	 * @var string[]
	 */
	private $relative_paths;

	/**
	 * Formats.
	 *
	 * @var string[]
	 */
	private $formats;

	/**
	 * Timestamp.
	 *
	 * @var string
	 */
	private $timestamp_gmt;

	/**
	 * Sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Create request.
	 *
	 * @param string   $event Event name.
	 * @param int      $attachment_id Attachment ID.
	 * @param string   $reason Reason.
	 * @param string[] $relative_paths Relative derivative paths.
	 * @param string[] $formats Formats.
	 * @param string   $timestamp_gmt Timestamp.
	 */
	public function __construct(
		string $event,
		int $attachment_id,
		string $reason,
		array $relative_paths,
		array $formats,
		string $timestamp_gmt
	) {
		$this->sanitizer      = new DerivativeManifestSanitizer();
		$this->event          = in_array( $event, array( self::EVENT_DERIVATIVES_SAVED, self::EVENT_DERIVATIVES_DELETED ), true )
			? $event
			: self::EVENT_DERIVATIVES_SAVED;
		$this->attachment_id  = max( 0, $attachment_id );
		$this->reason         = $this->normalize_key( $reason, 'unspecified' );
		$this->relative_paths = $this->normalize_relative_paths( $relative_paths );
		$this->formats        = $this->normalize_formats( $formats );
		$this->timestamp_gmt  = trim( $timestamp_gmt );
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get event name.
	 *
	 * @return string
	 */
	public function event(): string {
		return $this->event;
	}

	/**
	 * Get reason.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Get relative paths.
	 *
	 * @return string[]
	 */
	public function relative_paths(): array {
		return $this->relative_paths;
	}

	/**
	 * Get formats.
	 *
	 * @return string[]
	 */
	public function formats(): array {
		return $this->formats;
	}

	/**
	 * Get timestamp.
	 *
	 * @return string
	 */
	public function timestamp_gmt(): string {
		return $this->timestamp_gmt;
	}

	/**
	 * Serialize for the WordPress action payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'event'          => $this->event,
			'reason'         => $this->reason,
			'attachment_id'  => $this->attachment_id,
			'relative_paths' => $this->relative_paths,
			'formats'        => $this->formats,
			'timestamp_gmt'  => $this->timestamp_gmt,
		);
	}

	/**
	 * Normalize safe relative paths.
	 *
	 * @param string[] $paths Paths.
	 * @return string[]
	 */
	private function normalize_relative_paths( array $paths ): array {
		$normalized = array();

		foreach ( $paths as $path ) {
			if ( ! is_scalar( $path ) ) {
				continue;
			}

			$path = $this->sanitizer->safe_relative_path( (string) $path );

			if ( '' !== $path ) {
				$normalized[] = $path;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalize format names.
	 *
	 * @param string[] $formats Formats.
	 * @return string[]
	 */
	private function normalize_formats( array $formats ): array {
		$normalized = array();

		foreach ( $formats as $format ) {
			if ( ! is_scalar( $format ) ) {
				continue;
			}

			$format = $this->normalize_key( (string) $format, '' );

			if ( in_array( $format, array( 'webp', 'avif' ), true ) ) {
				$normalized[] = $format;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalize a machine-readable key.
	 *
	 * @param string $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function normalize_key( string $value, string $fallback ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? $fallback : substr( $value, 0, 64 );
	}
}
