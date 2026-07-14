<?php
/**
 * CLI exit-code constants.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Stable exit-code meanings for Phase 13.1 commands.
 */
final class CliExitCode {

	public const SUCCESS  = 0;
	public const FAILURE  = 1;
	public const DEGRADED = 2;

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
