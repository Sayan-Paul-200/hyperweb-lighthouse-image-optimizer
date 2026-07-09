<?php
/**
 * Log retention pruning.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Deletes old log rows without unbounded work.
 */
final class LogPruner implements LogPrunerInterface {

	public const BATCH_SIZE      = 500;
	private const DAY_IN_SECONDS = 86400;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Database adapter.
	 *
	 * @var LogDatabaseInterface
	 */
	private $database;

	/**
	 * Log table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Clock callback returning a Unix timestamp.
	 *
	 * @var callable|null
	 */
	private $clock;

	/**
	 * Create a WordPress-backed pruner.
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
				SettingsRepository::for_wordpress(),
				new WordPressLogDatabase( $wpdb ),
				LogTableSchema::table_name( $wpdb->prefix )
			);
		}

		return new self(
			SettingsRepository::for_wordpress(),
			new NullLogDatabase(),
			LogTableSchema::TABLE_SUFFIX
		);
	}

	/**
	 * Create the pruner.
	 *
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 * @param LogDatabaseInterface        $database Database adapter.
	 * @param string                      $table_name Log table name.
	 * @param callable|null               $clock Optional clock returning a Unix timestamp.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		LogDatabaseInterface $database,
		string $table_name,
		?callable $clock = null
	) {
		$this->settings   = $settings;
		$this->database   = $database;
		$this->table_name = $table_name;
		$this->clock      = $clock;
	}

	/**
	 * Prune old log rows.
	 *
	 * @return int Number of rows removed.
	 */
	public function prune(): int {
		$cutoff_gmt = gmdate(
			'Y-m-d H:i:s',
			$this->now() - ( $this->settings->log_retention_days() * self::DAY_IN_SECONDS )
		);

		return $this->database->delete_older_than( $this->table_name, $cutoff_gmt, self::BATCH_SIZE );
	}

	/**
	 * Get current Unix timestamp.
	 *
	 * @return int
	 */
	private function now(): int {
		if ( null !== $this->clock ) {
			return (int) call_user_func( $this->clock );
		}

		return time();
	}
}
