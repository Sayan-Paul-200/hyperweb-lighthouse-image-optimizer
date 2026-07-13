<?php
/**
 * Fake attachment statistics scanner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentStatisticsScannerInterface;

/**
 * Returns configured attachment ID pages for statistics tests.
 */
final class FakeAttachmentStatisticsScanner implements AttachmentStatisticsScannerInterface {

	/**
	 * Attachment ID pages.
	 *
	 * @var array<int,int[]>
	 */
	private $pages;

	/**
	 * Last requested page size.
	 *
	 * @var int|null
	 */
	public $last_page_size;

	/**
	 * Requested pages.
	 *
	 * @var int[]
	 */
	public $requested_pages = array();

	/**
	 * Optional throwable.
	 *
	 * @var \Throwable|null
	 */
	public $throwable;

	/**
	 * Create scanner.
	 *
	 * @param array<int,int[]> $pages Attachment ID pages.
	 */
	public function __construct( array $pages ) {
		$this->pages = array_values( $pages );
	}

	/**
	 * Get one bounded page of attachment IDs.
	 *
	 * @param int $page Page number.
	 * @param int $page_size Page size.
	 * @throws \Throwable When the fake scanner is configured to throw.
	 * @return int[]
	 */
	public function scan_page( int $page, int $page_size ): array {
		$this->requested_pages[] = $page;
		$this->last_page_size    = $page_size;

		if ( $this->throwable instanceof \Throwable ) {
			throw $this->throwable;
		}

		return $this->pages[ $page - 1 ] ?? array();
	}
}
