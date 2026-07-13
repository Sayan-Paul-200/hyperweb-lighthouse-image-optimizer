<?php
/**
 * Low-level log database adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Wraps database operations so logging can be unit-tested without WordPress.
 */
interface LogDatabaseInterface {

	/**
	 * Insert a log row.
	 *
	 * @param string              $table Table name.
	 * @param array<string,mixed> $data Row data.
	 * @param string[]            $formats Insert formats.
	 * @return bool
	 */
	public function insert( string $table, array $data, array $formats ): bool;

	/**
	 * Delete old log rows in a bounded batch.
	 *
	 * @param string $table Table name.
	 * @param string $cutoff_gmt Delete rows older than this GMT datetime.
	 * @param int    $limit Maximum rows to delete.
	 * @return int
	 */
	public function delete_older_than( string $table, string $cutoff_gmt, int $limit ): int;

	/**
	 * Delete one bounded batch of log rows.
	 *
	 * @param string $table Table name.
	 * @param int    $limit Maximum rows to delete.
	 * @return int
	 */
	public function delete_batch( string $table, int $limit ): int;
}
