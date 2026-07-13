<?php
/**
 * Attachment job control contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Queries and controls plugin-owned attachment jobs.
 */
interface AttachmentJobControlInterface {

	/**
	 * Count pending plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function pending_count(): int;

	/**
	 * Count in-progress plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function in_progress_count(): int;

	/**
	 * Cancel pending plugin-owned attachment jobs.
	 *
	 * @return AttachmentJobControlResult
	 */
	public function cancel_pending(): AttachmentJobControlResult;
}
