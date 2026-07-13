<?php
/**
 * Attachment statistics scanner contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Scans bounded pages of attachment IDs with plugin-owned image metadata.
 */
interface AttachmentStatisticsScannerInterface {

	/**
	 * Get one bounded page of attachment IDs that own plugin metadata.
	 *
	 * @param int $page Page number, starting at 1.
	 * @param int $page_size Maximum attachment IDs to return.
	 * @return int[]
	 */
	public function scan_page( int $page, int $page_size ): array;
}
