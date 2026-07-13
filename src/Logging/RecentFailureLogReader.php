<?php
/**
 * Recent failure log reader.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Reads a bounded, safe summary of recent warning and error log entries.
 */
final class RecentFailureLogReader {

	/**
	 * Default number of entries to read.
	 *
	 * @var int
	 */
	public const DEFAULT_LIMIT = 5;

	/**
	 * Read-only log database adapter.
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
	 * Maximum rows to return.
	 *
	 * @var int
	 */
	private $limit;

	/**
	 * Create a WordPress-backed reader.
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
				/**
				 * Select recent log entries.
				 *
				 * @param string   $table Table name.
				 * @param string[] $levels Log levels.
				 * @param int      $limit Maximum row count.
				 * @return array<int,array<string,mixed>>
				 */
				public function select_recent_entries( string $table, array $levels, int $limit ): array {
					unset( $table, $levels, $limit );
					return array();
				}

				/**
				 * Select one bounded page of log entries.
				 *
				 * @param string   $table Table name.
				 * @param LogQuery $query Log query.
				 * @return array<int,array<string,mixed>>
				 */
				public function select_entries( string $table, LogQuery $query ): array {
					unset( $table, $query );
					return array();
				}

				/**
				 * Count matching log entries.
				 *
				 * @param string   $table Table name.
				 * @param LogQuery $query Log query.
				 * @return int
				 */
				public function count_entries( string $table, LogQuery $query ): int {
					unset( $table, $query );
					return 0;
				}
			},
			LogTableSchema::TABLE_SUFFIX
		);
	}

	/**
	 * Create the reader.
	 *
	 * @param LogReadDatabaseInterface $database Read-only database adapter.
	 * @param string                   $table_name Log table name.
	 * @param int                      $limit Maximum entries.
	 */
	public function __construct( LogReadDatabaseInterface $database, string $table_name, int $limit = self::DEFAULT_LIMIT ) {
		$this->database   = $database;
		$this->table_name = $table_name;
		$this->limit      = max( 1, $limit );
	}

	/**
	 * Read the recent failure summary.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function read(): array {
		$rows    = $this->database->select_recent_entries(
			$this->table_name,
			array( LogLevel::WARNING, LogLevel::ERROR ),
			$this->limit
		);
		$entries = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$entries[] = array(
				'created_at_gmt' => isset( $row['created_at_gmt'] ) && is_scalar( $row['created_at_gmt'] ) ? substr( trim( (string) $row['created_at_gmt'] ), 0, 19 ) : '',
				'level'          => isset( $row['level'] ) && is_scalar( $row['level'] ) ? LogLevel::normalize( (string) $row['level'] ) : LogLevel::ERROR,
				'code'           => isset( $row['code'] ) && is_scalar( $row['code'] ) ? LogCode::normalize( (string) $row['code'] ) : LogCode::UNKNOWN,
				'message'        => isset( $row['message'] ) && is_scalar( $row['message'] ) ? trim( (string) $row['message'] ) : '',
				'attachment_id'  => isset( $row['attachment_id'] ) && is_numeric( $row['attachment_id'] ) && 0 < (int) $row['attachment_id']
					? (int) $row['attachment_id']
					: null,
			);
		}

		return $entries;
	}
}
