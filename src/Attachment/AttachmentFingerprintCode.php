<?php
/**
 * Attachment fingerprint code taxonomy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Defines stable machine-readable attachment fingerprint codes.
 */
final class AttachmentFingerprintCode {

	public const FINGERPRINT_MATCH       = 'fingerprint_match';
	public const FINGERPRINT_MISSING     = 'fingerprint_missing';
	public const FINGERPRINT_INVALID     = 'fingerprint_invalid';
	public const FINGERPRINT_MISMATCH    = 'fingerprint_mismatch';
	public const SOURCE_PATH_CHANGED     = 'source_path_changed';
	public const SOURCE_BYTES_CHANGED    = 'source_bytes_changed';
	public const SOURCE_MODIFIED_CHANGED = 'source_modified_time_changed';
	public const METADATA_HASH_CHANGED   = 'metadata_hash_changed';

	/**
	 * Get all known fingerprint codes.
	 *
	 * @return string[]
	 */
	public static function all_codes(): array {
		return array(
			self::FINGERPRINT_MATCH,
			self::FINGERPRINT_MISSING,
			self::FINGERPRINT_INVALID,
			self::FINGERPRINT_MISMATCH,
			self::SOURCE_PATH_CHANGED,
			self::SOURCE_BYTES_CHANGED,
			self::SOURCE_MODIFIED_CHANGED,
			self::METADATA_HASH_CHANGED,
		);
	}

	/**
	 * Normalize a fingerprint code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function normalize( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return in_array( $code, self::all_codes(), true ) ? $code : self::FINGERPRINT_INVALID;
	}
}
