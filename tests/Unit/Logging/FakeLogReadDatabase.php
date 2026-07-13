<?php
/**
 * Fake read-only log database adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogReadDatabaseInterface;

/**
 * Returns deterministic log rows for summary-reader tests.
 */
final class FakeLogReadDatabase implements LogReadDatabaseInterface {

	/**
	 * Rows to return.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $rows = array();

	/**
	 * Recorded queries.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $calls = array();

	/**
	 * Total rows to report for paginated queries.
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * Select recent log entries for the given levels.
	 *
	 * @param string   $table Table name.
	 * @param string[] $levels Levels.
	 * @param int      $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_recent_entries( string $table, array $levels, int $limit ): array {
		$this->calls[] = array(
			'table'  => $table,
			'levels' => $levels,
			'limit'  => $limit,
		);

		return $this->rows;
	}

	/**
	 * Select one bounded page of log rows.
	 *
	 * @param string   $table Table name.
	 * @param \HyperWeb\LighthouseImageOptimizer\Logging\LogQuery $query Query object.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_entries( string $table, \HyperWeb\LighthouseImageOptimizer\Logging\LogQuery $query ): array {
		$this->calls[] = array(
			'table' => $table,
			'query' => $query->to_array(),
			'type'  => 'page',
		);

		return $this->rows;
	}

	/**
	 * Count rows matching the given query.
	 *
	 * @param string   $table Table name.
	 * @param \HyperWeb\LighthouseImageOptimizer\Logging\LogQuery $query Query object.
	 * @return int
	 */
	public function count_entries( string $table, \HyperWeb\LighthouseImageOptimizer\Logging\LogQuery $query ): int {
		$this->calls[] = array(
			'table' => $table,
			'query' => $query->to_array(),
			'type'  => 'count',
		);

		return $this->count;
	}
}
