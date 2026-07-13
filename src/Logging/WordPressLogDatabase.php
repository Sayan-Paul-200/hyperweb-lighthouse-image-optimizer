<?php
/**
 * WordPress log database adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Executes log-table operations through wpdb.
 */
final class WordPressLogDatabase implements LogDatabaseInterface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Create the database adapter.
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Insert a log row.
	 *
	 * @param string              $table Table name.
	 * @param array<string,mixed> $data Row data.
	 * @param string[]            $formats Insert formats.
	 * @return bool
	 */
	public function insert( string $table, array $data, array $formats ): bool {
		if ( ! $this->is_safe_table_name( $table ) ) {
			return false;
		}

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return false !== $this->wpdb->insert( $table, $data, $formats );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
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
		if ( ! $this->is_safe_table_name( $table ) || $limit < 1 ) {
			return 0;
		}

		try {
			$sql = $this->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$table} WHERE created_at_gmt < %s ORDER BY created_at_gmt ASC LIMIT %d",
				$cutoff_gmt,
				$limit
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $this->wpdb->query( $sql );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return 0;
		}

		return is_int( $deleted ) ? max( 0, $deleted ) : 0;
	}

	/**
	 * Delete one bounded batch of log rows.
	 *
	 * @param string $table Table name.
	 * @param int    $limit Maximum rows to delete.
	 * @return int
	 */
	public function delete_batch( string $table, int $limit ): int {
		if ( ! $this->is_safe_table_name( $table ) || $limit < 1 ) {
			return 0;
		}

		try {
			$sql = $this->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$table} ORDER BY id ASC LIMIT %d",
				$limit
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $this->wpdb->query( $sql );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return 0;
		}

		return is_int( $deleted ) ? max( 0, $deleted ) : 0;
	}

	/**
	 * Validate a table identifier before interpolating it into SQL.
	 *
	 * @param string $table Table name.
	 * @return bool
	 */
	private function is_safe_table_name( string $table ): bool {
		return 1 === preg_match( '/^[A-Za-z0-9_]+$/', $table );
	}
}
