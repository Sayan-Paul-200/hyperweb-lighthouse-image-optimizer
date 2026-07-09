<?php
/**
 * Log retention pruning.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;

/**
 * Deletes old log rows without unbounded work.
 */
final class LogPruner implements LogPrunerInterface {

	public const DEFAULT_RETENTION_DAYS = 30;
	public const MAX_RETENTION_DAYS     = 3650;
	public const BATCH_SIZE             = 500;
	private const DAY_IN_SECONDS        = 86400;

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

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
				new WordPressOptionStore(),
				new WordPressLogDatabase( $wpdb ),
				LogTableSchema::table_name( $wpdb->prefix )
			);
		}

		return new self(
			new WordPressOptionStore(),
			new NullLogDatabase(),
			LogTableSchema::TABLE_SUFFIX
		);
	}

	/**
	 * Create the pruner.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @param LogDatabaseInterface $database Database adapter.
	 * @param string               $table_name Log table name.
	 * @param callable|null        $clock Optional clock returning a Unix timestamp.
	 */
	public function __construct(
		OptionStoreInterface $options,
		LogDatabaseInterface $database,
		string $table_name,
		?callable $clock = null
	) {
		$this->options    = $options;
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
			$this->now() - ( $this->retention_days() * self::DAY_IN_SECONDS )
		);

		return $this->database->delete_older_than( $this->table_name, $cutoff_gmt, self::BATCH_SIZE );
	}

	/**
	 * Get effective retention days from settings.
	 *
	 * @return int
	 */
	private function retention_days(): int {
		$settings = $this->options->get( Installer::OPTION_SETTINGS, SettingsSchema::defaults() );

		if ( ! is_array( $settings ) || ! array_key_exists( 'log_retention_days', $settings ) ) {
			return self::DEFAULT_RETENTION_DAYS;
		}

		$value = $settings['log_retention_days'];

		if ( is_int( $value ) ) {
			$days = $value;
		} elseif ( is_string( $value ) && ctype_digit( $value ) ) {
			$days = (int) $value;
		} else {
			return self::DEFAULT_RETENTION_DAYS;
		}

		if ( $days < 1 || $days > self::MAX_RETENTION_DAYS ) {
			return self::DEFAULT_RETENTION_DAYS;
		}

		return $days;
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
