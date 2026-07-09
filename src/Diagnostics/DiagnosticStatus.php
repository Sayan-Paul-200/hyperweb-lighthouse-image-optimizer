<?php
/**
 * Diagnostic statuses.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Defines supported diagnostic result statuses.
 */
final class DiagnosticStatus {

	public const PASS    = 'pass';
	public const WARNING = 'warning';
	public const FAIL    = 'fail';
	public const INFO    = 'info';

	/**
	 * Get all statuses.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::PASS,
			self::WARNING,
			self::FAIL,
			self::INFO,
		);
	}

	/**
	 * Normalize a status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function normalize( string $status ): string {
		$status = strtolower( trim( $status ) );

		return in_array( $status, self::all(), true ) ? $status : self::INFO;
	}
}
