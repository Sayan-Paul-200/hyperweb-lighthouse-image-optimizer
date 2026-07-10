<?php
/**
 * System conversion clock.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Uses the current system time.
 */
final class SystemConversionClock implements ConversionClockInterface {

	/**
	 * Get current Unix timestamp.
	 *
	 * @return int
	 */
	public function now(): int {
		return time();
	}
}
