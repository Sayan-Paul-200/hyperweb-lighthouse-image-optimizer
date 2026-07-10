<?php
/**
 * Attachment lock scanner contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Finds attachments that currently carry plugin lock metadata.
 */
interface AttachmentLockScannerInterface {

	/**
	 * Get locked attachment IDs.
	 *
	 * @param int $limit Maximum attachments to return.
	 * @return int[]
	 */
	public function locked_attachment_ids( int $limit ): array;
}
