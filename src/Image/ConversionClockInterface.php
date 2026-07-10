<?php
/**
 * Conversion clock contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Provides timestamps for conversion outputs.
 */
interface ConversionClockInterface {

	/**
	 * Get current Unix timestamp.
	 *
	 * @return int
	 */
	public function now(): int;
}
