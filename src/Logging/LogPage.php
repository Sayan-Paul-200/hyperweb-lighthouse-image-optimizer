<?php
/**
 * Paginated logs page value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries one bounded page of safe log rows.
 */
final class LogPage {

	/**
	 * Safe log rows.
	 *
	 * @var LogRowView[]
	 */
	private $items;

	/**
	 * Query metadata.
	 *
	 * @var LogQuery
	 */
	private $query;

	/**
	 * Total matching rows.
	 *
	 * @var int
	 */
	private $total_items;

	/**
	 * Create the page.
	 *
	 * @param LogRowView[] $items Safe rows.
	 * @param LogQuery     $query Query metadata.
	 * @param int          $total_items Total matching rows.
	 */
	public function __construct( array $items, LogQuery $query, int $total_items ) {
		$this->items = array_values(
			array_filter(
				$items,
				static function ( $item ): bool {
					return $item instanceof LogRowView;
				}
			)
		);
		$this->query       = $query;
		$this->total_items = max( 0, $total_items );
	}

	/**
	 * Serialize the page for REST/admin consumers.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'items'      => array_map(
				static function ( LogRowView $item ): array {
					return $item->to_array();
				},
				$this->items
			),
			'page'       => $this->query->page(),
			'perPage'    => $this->query->per_page(),
			'totalItems' => $this->total_items,
			'totalPages' => $this->total_pages(),
			'filters'    => $this->query->to_array(),
		);
	}

	/**
	 * Calculate total pages.
	 *
	 * @return int
	 */
	private function total_pages(): int {
		if ( 0 === $this->total_items ) {
			return 0;
		}

		return (int) ceil( $this->total_items / max( 1, $this->query->per_page() ) );
	}
}
