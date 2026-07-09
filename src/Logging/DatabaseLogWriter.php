<?php
/**
 * Database log writer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Persists log entries into the plugin log table.
 */
final class DatabaseLogWriter implements LogWriterInterface {

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
	 * Create a WordPress-backed writer.
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
	 * Create the writer.
	 *
	 * @param LogDatabaseInterface $database Database adapter.
	 * @param string               $table_name Log table name.
	 */
	public function __construct( LogDatabaseInterface $database, string $table_name ) {
		$this->database   = $database;
		$this->table_name = $table_name;
	}

	/**
	 * Persist a log entry.
	 *
	 * @param LogEntry $entry Sanitized entry.
	 * @return bool
	 */
	public function write( LogEntry $entry ): bool {
		if ( ! $this->is_safe_table_name( $this->table_name ) ) {
			return false;
		}

		try {
			return $this->database->insert(
				$this->table_name,
				array(
					'created_at_gmt' => $entry->created_at_gmt(),
					'level'          => $entry->level(),
					'code'           => $entry->code(),
					'message'        => $entry->message(),
					'attachment_id'  => $entry->attachment_id(),
					'job_id'         => $entry->job_id(),
					'context_json'   => $this->encode_context( $entry->context() ),
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Validate a table identifier before passing it to the database layer.
	 *
	 * @param string $table_name Table name.
	 * @return bool
	 */
	private function is_safe_table_name( string $table_name ): bool {
		return 1 === preg_match( '/^[A-Za-z0-9_]+$/', $table_name );
	}

	/**
	 * Encode context for storage.
	 *
	 * @param array<mixed> $context Context.
	 * @return string|null
	 */
	private function encode_context( array $context ): ?string {
		if ( array() === $context ) {
			return null;
		}

		if ( function_exists( 'wp_json_encode' ) ) {
			$json = \wp_json_encode( $context );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WordPress may be unavailable in unit-level logging code.
			$json = json_encode( $context );
		}

		return false === $json ? null : $json;
	}
}
