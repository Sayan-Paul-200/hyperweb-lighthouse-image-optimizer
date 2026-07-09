<?php
/**
 * Format support checker contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Provides minimal save-time image format support checks.
 */
interface FormatSupportCheckerInterface {

	/**
	 * Determine whether a target format is supported.
	 *
	 * A null result means support could not be determined in the current runtime.
	 *
	 * @param string $format Target format.
	 * @return bool|null
	 */
	public function supports( string $format ): ?bool;
}
