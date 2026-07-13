<?php
/**
 * Bulk scanner runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Describes WordPress lookups needed for dry-run scanning and preview rendering.
 */
interface BulkScannerRuntimeInterface {

	/**
	 * Read one bounded page of attachment IDs after the given cursor.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param int             $after_id Exclusive attachment-ID cursor.
	 * @param int             $limit Page size.
	 * @return int[]
	 */
	public function scan_page( BulkScanFilters $filters, int $after_id, int $limit ): array;

	/**
	 * Determine whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool;

	/**
	 * Read lightweight preview records for the given attachment IDs.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 * @return array<int,array<string,mixed>>
	 */
	public function preview_records( array $attachment_ids ): array;
}
