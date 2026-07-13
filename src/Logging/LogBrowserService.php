<?php
/**
 * Paginated log browser service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Builds safe paginated logs payloads from the log table.
 */
final class LogBrowserService {

	/**
	 * Read-only database adapter.
	 *
	 * @var LogReadDatabaseInterface
	 */
	private $database;

	/**
	 * Log table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Create a WordPress-backed browser.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		/**
		 * WordPress database object.
		 *
		 * @var \wpdb|null $wpdb
		 */
		global $wpdb;

		if ( $wpdb instanceof \wpdb ) {
			return new self(
				new WordPressLogReadDatabase( $wpdb ),
				LogTableSchema::table_name( $wpdb->prefix )
			);
		}

		return new self(
			new class() implements LogReadDatabaseInterface {
				public function select_recent_entries( string $table, array $levels, int $limit ): array {
					unset( $table, $levels, $limit );
					return array();
				}

				public function select_entries( string $table, LogQuery $query ): array {
					unset( $table, $query );
					return array();
				}

				public function count_entries( string $table, LogQuery $query ): int {
					unset( $table, $query );
					return 0;
				}
			},
			LogTableSchema::TABLE_SUFFIX
		);
	}

	/**
	 * Create the browser.
	 *
	 * @param LogReadDatabaseInterface $database Read-only database adapter.
	 * @param string                   $table_name Table name.
	 */
	public function __construct( LogReadDatabaseInterface $database, string $table_name ) {
		$this->database   = $database;
		$this->table_name = $table_name;
	}

	/**
	 * Build one paginated logs page.
	 *
	 * @param LogQuery $query Query object.
	 * @return LogPage
	 */
	public function page( LogQuery $query ): LogPage {
		$rows  = $this->database->select_entries( $this->table_name, $query );
		$total = $this->database->count_entries( $this->table_name, $query );
		$items = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$items[] = LogRowView::from_row( $row );
		}

		return new LogPage( $items, $query, $total );
	}
}
