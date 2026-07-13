<?php
/**
 * WordPress read-only log database adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Executes bounded log-summary reads through wpdb.
 */
final class WordPressLogReadDatabase implements LogReadDatabaseInterface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Create the adapter.
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Select recent log entries for the given levels.
	 *
	 * @param string   $table Table name.
	 * @param string[] $levels Log levels.
	 * @param int      $limit Maximum rows to read.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_recent_entries( string $table, array $levels, int $limit ): array {
		if ( ! $this->is_safe_table_name( $table ) || $limit < 1 ) {
			return array();
		}

		$levels = array_values(
			array_filter(
				array_map(
					array( LogLevel::class, 'normalize' ),
					$levels
				),
				static function ( string $level ): bool {
					return in_array( $level, array( LogLevel::WARNING, LogLevel::ERROR ), true );
				}
			)
		);

		if ( array() === $levels ) {
			return array();
		}

		try {
			$placeholders = implode( ', ', array_fill( 0, count( $levels ), '%s' ) );
			$args         = array_merge( $levels, array( $limit ) );
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Variadic placeholders are built dynamically from normalized level filters.
			$sql = $this->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT created_at_gmt, level, code, message, attachment_id FROM {$table} WHERE level IN ({$placeholders}) ORDER BY created_at_gmt DESC, id DESC LIMIT %d",
				...$args
			);
			// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $this->wpdb->get_results( $sql, 'ARRAY_A' );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return array();
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Select one bounded page of log rows for the given query.
	 *
	 * @param string   $table Table name.
	 * @param LogQuery $query Query object.
	 * @return array<int,array<string,mixed>>
	 */
	public function select_entries( string $table, LogQuery $query ): array {
		if ( ! $this->is_safe_table_name( $table ) ) {
			return array();
		}

		$args      = array();
		$where_sql = $this->where_sql( $query, $args );
		$args[]    = $query->per_page();
		$args[]    = $query->offset();

		try {
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Variadic placeholders are assembled from normalized query state.
			$sql = $this->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT created_at_gmt, level, code, message, attachment_id, job_id FROM {$table} {$where_sql} ORDER BY created_at_gmt DESC, id DESC LIMIT %d OFFSET %d",
				...$args
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $this->wpdb->get_results( $sql, 'ARRAY_A' );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return array();
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count log rows matching the given query.
	 *
	 * @param string   $table Table name.
	 * @param LogQuery $query Query object.
	 * @return int
	 */
	public function count_entries( string $table, LogQuery $query ): int {
		if ( ! $this->is_safe_table_name( $table ) ) {
			return 0;
		}

		$args      = array();
		$where_sql = $this->where_sql( $query, $args );

		try {
			if ( array() === $args ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name and static WHERE clause were validated earlier.
				$sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
			} else {
				// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholder-bearing WHERE clause is assembled separately with matching args.
				$sql = $this->wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM {$table} {$where_sql}",
					...$args
				);
				// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$count = $this->wpdb->get_var( $sql );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return 0;
		}

		return is_numeric( $count ) ? max( 0, (int) $count ) : 0;
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

	/**
	 * Build a prepared WHERE clause for a log query.
	 *
	 * @param LogQuery              $query Query object.
	 * @param array<int,string|int> $args Prepared-statement arguments.
	 * @return string
	 */
	private function where_sql( LogQuery $query, array &$args ): string {
		$where = array( 'WHERE 1=1' );

		if ( LogQuery::LEVEL_ALL !== $query->level() ) {
			$where[] = 'AND level = %s';
			$args[]  = $query->level();
		}

		if ( null !== $query->code() ) {
			$where[] = 'AND code = %s';
			$args[]  = $query->code();
		}

		if ( null !== $query->attachment_id() ) {
			$where[] = 'AND attachment_id = %d';
			$args[]  = $query->attachment_id();
		}

		return implode( ' ', $where );
	}
}
