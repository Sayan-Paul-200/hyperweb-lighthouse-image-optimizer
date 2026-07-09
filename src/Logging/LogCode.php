<?php
/**
 * Stable log codes.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Defines and validates machine-readable log codes.
 */
final class LogCode {

	public const UNKNOWN               = 'unknown';
	public const LOG_WRITE_FAILED      = 'log_write_failed';
	public const LOG_TABLE_UNAVAILABLE = 'log_table_unavailable';
	public const LOG_CONTEXT_TRUNCATED = 'log_context_truncated';
	public const LOG_RETENTION_PRUNED  = 'log_retention_pruned';
	public const LOG_RETENTION_FAILED  = 'log_retention_failed';

	/**
	 * Normalize a code into the stable machine-readable shape.
	 *
	 * @param string $code Log code.
	 * @return string
	 */
	public static function normalize( string $code ): string {
		$code = strtolower( trim( $code ) );

		return self::is_valid( $code ) ? $code : self::UNKNOWN;
	}

	/**
	 * Determine whether a code is stable and machine-readable.
	 *
	 * @param string $code Log code.
	 * @return bool
	 */
	public static function is_valid( string $code ): bool {
		return 1 === preg_match( '/^[a-z][a-z0-9_]{0,63}$/', $code );
	}
}
