<?php
/**
 * Attachment clock contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Provides deterministic timestamps for attachment metadata.
 */
interface AttachmentClockInterface {

	/**
	 * Get current timestamp.
	 *
	 * @return int
	 */
	public function now(): int;
}
