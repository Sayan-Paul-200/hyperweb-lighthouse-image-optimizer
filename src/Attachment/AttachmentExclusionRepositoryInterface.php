<?php
/**
 * Attachment exclusion repository contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Reads attachment-level optimization exclusion state.
 */
interface AttachmentExclusionRepositoryInterface {

	/**
	 * Determine whether an attachment is excluded from automation.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_excluded( int $attachment_id ): bool;
}
