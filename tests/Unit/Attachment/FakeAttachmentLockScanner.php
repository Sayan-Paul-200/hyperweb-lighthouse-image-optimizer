<?php
/**
 * Fake attachment lock scanner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockScannerInterface;

/**
 * Returns configured attachment IDs for lock recovery and diagnostics tests.
 */
final class FakeAttachmentLockScanner implements AttachmentLockScannerInterface {

	/**
	 * Attachment IDs.
	 *
	 * @var int[]
	 */
	private $attachment_ids;

	/**
	 * Last requested limit.
	 *
	 * @var int|null
	 */
	public $last_limit;

	/**
	 * Create scanner.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 */
	public function __construct( array $attachment_ids ) {
		$this->attachment_ids = array_values( $attachment_ids );
	}

	/**
	 * Get locked attachment IDs.
	 *
	 * @param int $limit Maximum attachments to return.
	 * @return int[]
	 */
	public function locked_attachment_ids( int $limit ): array {
		$this->last_limit = $limit;

		return array_slice( $this->attachment_ids, 0, $limit );
	}
}
