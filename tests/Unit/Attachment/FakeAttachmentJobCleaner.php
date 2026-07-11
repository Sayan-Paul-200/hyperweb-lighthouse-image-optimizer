<?php
/**
 * Fake attachment job cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentJobCleanerInterface;

/**
 * Records attachment job cleanup calls.
 */
final class FakeAttachmentJobCleaner implements AttachmentJobCleanerInterface {

	/**
	 * Attachment IDs passed to the cleaner.
	 *
	 * @var int[]
	 */
	public $attachment_ids = array();

	/**
	 * Result returned by the cleaner.
	 *
	 * @var AttachmentCleanupResult
	 */
	public $result;

	/**
	 * Create fake cleaner.
	 *
	 * @param AttachmentCleanupResult|null $result Optional result.
	 */
	public function __construct( ?AttachmentCleanupResult $result = null ) {
		$this->result = $result ?? AttachmentCleanupResult::success(
			array( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_CANCELLED ),
			array( 'Cancelled 0 pending attachment job(s).' )
		);
	}

	/**
	 * Cancel pending actions.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	public function cancel_pending_actions( int $attachment_id ): AttachmentCleanupResult {
		$this->attachment_ids[] = $attachment_id;

		return $this->result;
	}
}
