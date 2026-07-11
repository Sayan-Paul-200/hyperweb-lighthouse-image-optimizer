<?php
/**
 * Attachment job cleaner contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Cancels plugin-owned pending jobs for one attachment.
 */
interface AttachmentJobCleanerInterface {

	/**
	 * Cancel pending plugin-owned jobs for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	public function cancel_pending_actions( int $attachment_id ): AttachmentCleanupResult;
}
