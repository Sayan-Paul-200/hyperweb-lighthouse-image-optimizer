<?php
/**
 * System attachment clock.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Uses the system clock for attachment metadata timestamps.
 */
final class SystemAttachmentClock implements AttachmentClockInterface {

	/**
	 * Get current timestamp.
	 *
	 * @return int
	 */
	public function now(): int {
		return time();
	}
}
