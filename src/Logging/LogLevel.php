<?php
/**
 * Log levels.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Defines supported log levels.
 */
final class LogLevel {

	public const DEBUG   = 'debug';
	public const INFO    = 'info';
	public const WARNING = 'warning';
	public const ERROR   = 'error';

	/**
	 * Get all supported levels.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::DEBUG,
			self::INFO,
			self::WARNING,
			self::ERROR,
		);
	}

	/**
	 * Determine whether a level is supported.
	 *
	 * @param string $level Log level.
	 * @return bool
	 */
	public static function is_valid( string $level ): bool {
		return in_array( strtolower( trim( $level ) ), self::all(), true );
	}

	/**
	 * Normalize a level into the supported set.
	 *
	 * @param string $level Log level.
	 * @return string
	 */
	public static function normalize( string $level ): string {
		$level = strtolower( trim( $level ) );

		return self::is_valid( $level ) ? $level : self::ERROR;
	}
}
