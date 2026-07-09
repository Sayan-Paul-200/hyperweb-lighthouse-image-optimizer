<?php
/**
 * Null log database adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Fails safely when WordPress database services are unavailable.
 */
final class NullLogDatabase implements LogDatabaseInterface {

	/**
	 * Insert a log row.
	 *
	 * @param string              $table Table name.
	 * @param array<string,mixed> $data Row data.
	 * @param string[]            $formats Insert formats.
	 * @return bool
	 */
	public function insert( string $table, array $data, array $formats ): bool {
		unset( $table, $data, $formats );

		return false;
	}

	/**
	 * Delete old log rows in a bounded batch.
	 *
	 * @param string $table Table name.
	 * @param string $cutoff_gmt Delete rows older than this GMT datetime.
	 * @param int    $limit Maximum rows to delete.
	 * @return int
	 */
	public function delete_older_than( string $table, string $cutoff_gmt, int $limit ): int {
		unset( $table, $cutoff_gmt, $limit );

		return 0;
	}
}
