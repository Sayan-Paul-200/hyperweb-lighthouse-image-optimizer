<?php
/**
 * Conversion result code taxonomy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Defines stable machine-readable conversion result codes.
 */
final class ConversionResultCode {

	public const OPTIMIZED       = 'optimized';
	public const ALREADY_CURRENT = 'already_current';

	public const SKIPPED_UNSUPPORTED_SOURCE_MIME = 'skipped_unsupported_source_mime';
	public const SKIPPED_TARGET_NOT_ENABLED      = 'skipped_target_not_enabled';
	public const SKIPPED_TARGET_NOT_SUPPORTED    = 'skipped_target_not_supported';
	public const SKIPPED_ANIMATED_IMAGE          = 'skipped_animated_image';
	public const SKIPPED_NOT_SMALLER             = 'skipped_not_smaller';
	public const SKIPPED_RESOURCE_LIMIT          = 'skipped_resource_limit';
	public const SKIPPED_EXCLUDED                = 'skipped_excluded';
	public const SKIPPED_OUTSIDE_UPLOADS         = 'skipped_outside_uploads';
	public const SKIPPED_UNKNOWN                 = 'skipped_unknown';

	public const SOURCE_MISSING                       = 'source_missing';
	public const SOURCE_UNREADABLE                    = 'source_unreadable';
	public const SOURCE_INVALID_MIME                  = 'source_invalid_mime';
	public const SOURCE_CORRUPT                       = 'source_corrupt';
	public const UPLOADS_UNAVAILABLE                  = 'uploads_unavailable';
	public const UNSAFE_SOURCE_PATH                   = 'unsafe_source_path';
	public const INVALID_TARGET_FORMAT                = 'invalid_target_format';
	public const DESTINATION_OUTSIDE_UPLOADS          = 'destination_outside_uploads';
	public const TEMPORARY_OUTSIDE_UPLOADS            = 'temporary_outside_uploads';
	public const DESTINATION_COLLISION                = 'destination_collision';
	public const TEMPORARY_COLLISION                  = 'temporary_collision';
	public const DESTINATION_REALPATH_OUTSIDE_UPLOADS = 'destination_realpath_outside_uploads';
	public const TEMPORARY_REALPATH_OUTSIDE_UPLOADS   = 'temporary_realpath_outside_uploads';
	public const EDITOR_UNAVAILABLE                   = 'editor_unavailable';
	public const EDITOR_LOAD_FAILED                   = 'editor_load_failed';
	public const CONVERSION_FAILED                    = 'conversion_failed';
	public const TEMPORARY_WRITE_FAILED               = 'temporary_write_failed';
	public const OUTPUT_VALIDATION_FAILED             = 'output_validation_failed';
	public const ATOMIC_MOVE_FAILED                   = 'atomic_move_failed';
	public const METADATA_WRITE_FAILED                = 'metadata_write_failed';
	public const LOCK_UNAVAILABLE                     = 'lock_unavailable';
	public const QUEUE_UNAVAILABLE                    = 'queue_unavailable';
	public const PERMISSION_DENIED                    = 'permission_denied';
	public const INVALID_JOB_PAYLOAD                  = 'invalid_job_payload';

	/**
	 * Get success codes.
	 *
	 * @return string[]
	 */
	public static function success_codes(): array {
		return array(
			self::OPTIMIZED,
			self::ALREADY_CURRENT,
		);
	}

	/**
	 * Get skipped codes.
	 *
	 * @return string[]
	 */
	public static function skipped_codes(): array {
		return array(
			self::SKIPPED_UNSUPPORTED_SOURCE_MIME,
			self::SKIPPED_TARGET_NOT_ENABLED,
			self::SKIPPED_TARGET_NOT_SUPPORTED,
			self::SKIPPED_ANIMATED_IMAGE,
			self::SKIPPED_NOT_SMALLER,
			self::SKIPPED_RESOURCE_LIMIT,
			self::SKIPPED_EXCLUDED,
			self::SKIPPED_OUTSIDE_UPLOADS,
			self::SKIPPED_UNKNOWN,
		);
	}

	/**
	 * Get failure codes.
	 *
	 * @return string[]
	 */
	public static function failure_codes(): array {
		return array(
			self::SOURCE_MISSING,
			self::SOURCE_UNREADABLE,
			self::SOURCE_INVALID_MIME,
			self::SOURCE_CORRUPT,
			self::UPLOADS_UNAVAILABLE,
			self::UNSAFE_SOURCE_PATH,
			self::INVALID_TARGET_FORMAT,
			self::DESTINATION_OUTSIDE_UPLOADS,
			self::TEMPORARY_OUTSIDE_UPLOADS,
			self::DESTINATION_COLLISION,
			self::TEMPORARY_COLLISION,
			self::DESTINATION_REALPATH_OUTSIDE_UPLOADS,
			self::TEMPORARY_REALPATH_OUTSIDE_UPLOADS,
			self::EDITOR_UNAVAILABLE,
			self::EDITOR_LOAD_FAILED,
			self::CONVERSION_FAILED,
			self::TEMPORARY_WRITE_FAILED,
			self::OUTPUT_VALIDATION_FAILED,
			self::ATOMIC_MOVE_FAILED,
			self::METADATA_WRITE_FAILED,
			self::LOCK_UNAVAILABLE,
			self::QUEUE_UNAVAILABLE,
			self::PERMISSION_DENIED,
			self::INVALID_JOB_PAYLOAD,
		);
	}

	/**
	 * Get all known conversion result codes.
	 *
	 * @return string[]
	 */
	public static function all_codes(): array {
		return array_merge(
			self::success_codes(),
			self::skipped_codes(),
			self::failure_codes()
		);
	}

	/**
	 * Normalize a code for a result status.
	 *
	 * @param string $status Result status.
	 * @param string $code Result code.
	 * @return string
	 */
	public static function normalize_for_status( string $status, string $code ): string {
		$code = self::normalize_shape( $code );

		if ( ConversionResult::STATUS_SUCCESS === $status ) {
			return in_array( $code, self::success_codes(), true ) ? $code : self::OPTIMIZED;
		}

		if ( ConversionResult::STATUS_SKIPPED === $status ) {
			return in_array( $code, self::skipped_codes(), true ) ? $code : self::SKIPPED_UNKNOWN;
		}

		return in_array( $code, self::failure_codes(), true ) ? $code : self::CONVERSION_FAILED;
	}

	/**
	 * Normalize a code into machine-readable shape.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private static function normalize_shape( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return substr( $code, 0, 64 );
	}
}
