<?php
/**
 * Fake conversion clock.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionClockInterface;

/**
 * Returns a fixed timestamp for conversion tests.
 */
final class FakeConversionClock implements ConversionClockInterface {

	/**
	 * Current timestamp.
	 *
	 * @var int
	 */
	private $now;

	/**
	 * Create clock.
	 *
	 * @param int $now Current timestamp.
	 */
	public function __construct( int $now = 1783526500 ) {
		$this->now = $now;
	}

	/**
	 * Get current timestamp.
	 *
	 * @return int
	 */
	public function now(): int {
		return $this->now;
	}
}
