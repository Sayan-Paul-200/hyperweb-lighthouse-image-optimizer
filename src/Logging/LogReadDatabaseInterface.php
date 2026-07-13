<?php
/**
 * Read-only log database adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Wraps bounded log read queries so summaries can be unit-tested.
 */
interface LogReadDatabaseInterface {

	/**
	 * Select recent log entries for the given levels.
	 *
	 * @param string   $table Table name.
	 * @param string[] $levels Log levels.
	 * @param int      $limit Maximum rows to read.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_recent_entries( string $table, array $levels, int $limit ): array;

	/**
	 * Select one bounded page of log rows for the given query.
	 *
	 * @param string   $table Table name.
	 * @param LogQuery $query Query object.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_entries( string $table, LogQuery $query ): array;

	/**
	 * Count log rows matching the given query.
	 *
	 * @param string   $table Table name.
	 * @param LogQuery $query Query object.
	 * @return int
	 */
	public function count_entries( string $table, LogQuery $query ): int;
}
