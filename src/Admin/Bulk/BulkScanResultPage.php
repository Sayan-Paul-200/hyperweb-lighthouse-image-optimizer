<?php
/**
 * Bulk scan result page.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries one paged preview response for a persisted bulk scan.
 */
final class BulkScanResultPage {

	/**
	 * Scan token.
	 *
	 * @var string
	 */
	private $scan_token;

	/**
	 * Current page number.
	 *
	 * @var int
	 */
	private $page;

	/**
	 * Current page size.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Total preview items.
	 *
	 * @var int
	 */
	private $total_items;

	/**
	 * Preview items.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $items;

	/**
	 * Create the result page.
	 *
	 * @param string                         $scan_token Scan token.
	 * @param int                            $page Page number.
	 * @param int                            $per_page Page size.
	 * @param int                            $total_items Total preview items.
	 * @param array<int,array<string,mixed>> $items Preview items.
	 */
	public function __construct(
		string $scan_token,
		int $page,
		int $per_page,
		int $total_items,
		array $items
	) {
		$this->scan_token  = BulkScanSession::normalize_token( $scan_token );
		$this->page        = max( 1, $page );
		$this->per_page    = max( 1, $per_page );
		$this->total_items = max( 0, $total_items );
		$this->items       = array_values( $items );
	}

	/**
	 * Serialize the page.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		$total_pages = (int) ceil( $this->total_items / max( 1, $this->per_page ) );

		return array(
			'scanToken'  => $this->scan_token,
			'page'       => $this->page,
			'perPage'    => $this->per_page,
			'totalItems' => $this->total_items,
			'totalPages' => max( 0, $total_pages ),
			'items'      => $this->items,
		);
	}
}
