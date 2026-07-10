<?php
/**
 * Fixed attachment clock.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;

/**
 * Provides deterministic timestamps for tests.
 */
final class FixedAttachmentClock implements AttachmentClockInterface {

	/**
	 * Timestamp.
	 *
	 * @var int
	 */
	private $now;

	/**
	 * Create clock.
	 *
	 * @param int $now Timestamp.
	 */
	public function __construct( int $now ) {
		$this->now = $now;
	}

	/**
	 * Get timestamp.
	 *
	 * @return int
	 */
	public function now(): int {
		return $this->now;
	}
}
