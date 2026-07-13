<?php
/**
 * Fake attachment job control.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentJobControlInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentJobControlResult;

/**
 * In-memory attachment job control seam for tests.
 */
final class FakeAttachmentJobControl implements AttachmentJobControlInterface {

	/**
	 * Pending count.
	 *
	 * @var int
	 */
	public $pending = 0;

	/**
	 * In-progress count.
	 *
	 * @var int
	 */
	public $in_progress = 0;

	/**
	 * Cancel result.
	 *
	 * @var AttachmentJobControlResult|null
	 */
	public $cancel_result;

	/**
	 * Count pending plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function pending_count(): int {
		return max( 0, $this->pending );
	}

	/**
	 * Count in-progress plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function in_progress_count(): int {
		return max( 0, $this->in_progress );
	}

	/**
	 * Cancel pending plugin-owned attachment jobs.
	 *
	 * @return AttachmentJobControlResult
	 */
	public function cancel_pending(): AttachmentJobControlResult {
		return $this->cancel_result instanceof AttachmentJobControlResult
			? $this->cancel_result
			: AttachmentJobControlResult::success( 0, array( 'Cancelled 0 pending attachment job(s).' ) );
	}
}
