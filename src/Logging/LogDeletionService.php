<?php
/**
 * Log deletion service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Deletes bounded batches of plugin-owned log rows.
 */
final class LogDeletionService {

	public const BATCH_SIZE = 500;

	/**
	 * Database adapter.
	 *
	 * @var LogDatabaseInterface
	 */
	private $database;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Create a WordPress-backed deleter.
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
				new WordPressLogDatabase( $wpdb ),
				LogTableSchema::table_name( $wpdb->prefix )
			);
		}

		return new self( new NullLogDatabase(), LogTableSchema::TABLE_SUFFIX );
	}

	/**
	 * Create the deleter.
	 *
	 * @param LogDatabaseInterface $database Database adapter.
	 * @param string               $table_name Table name.
	 */
	public function __construct( LogDatabaseInterface $database, string $table_name ) {
		$this->database   = $database;
		$this->table_name = $table_name;
	}

	/**
	 * Delete one bounded clear-all batch.
	 *
	 * @return LogDeletionResult
	 */
	public function clear_all(): LogDeletionResult {
		$deleted = $this->database->delete_batch( $this->table_name, self::BATCH_SIZE );

		return new LogDeletionResult(
			$deleted,
			$deleted < self::BATCH_SIZE,
			self::BATCH_SIZE
		);
	}
}
