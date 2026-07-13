<?php
/**
 * Fake bulk scanner runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScannerRuntimeInterface;

/**
 * Records dry-run scan lookups for unit tests.
 */
final class FakeBulkScannerRuntime implements BulkScannerRuntimeInterface {

	/**
	 * Scan pages keyed by cursor.
	 *
	 * @var array<int,int[]>
	 */
	public $pages = array();

	/**
	 * Attachment image map.
	 *
	 * @var array<int,bool>
	 */
	public $images = array();

	/**
	 * Preview records keyed by attachment ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $preview = array();

	/**
	 * Recorded scan calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $scan_calls = array();

	/**
	 * Read one bounded page of attachment IDs after the given cursor.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param int             $after_id Exclusive attachment-ID cursor.
	 * @param int             $limit Page size.
	 * @return int[]
	 */
	public function scan_page( BulkScanFilters $filters, int $after_id, int $limit ): array {
		$this->scan_calls[] = array(
			'filters'  => $filters->to_array(),
			'after_id' => $after_id,
			'limit'    => $limit,
		);

		return $this->pages[ $after_id ] ?? array();
	}

	/**
	 * Determine whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return $this->images[ $attachment_id ] ?? true;
	}

	/**
	 * Read lightweight preview records for the given attachment IDs.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 * @return array<int,array<string,mixed>>
	 */
	public function preview_records( array $attachment_ids ): array {
		$records = array();

		foreach ( $attachment_ids as $attachment_id ) {
			if ( isset( $this->preview[ $attachment_id ] ) ) {
				$records[ $attachment_id ] = $this->preview[ $attachment_id ];
			}
		}

		return $records;
	}
}
