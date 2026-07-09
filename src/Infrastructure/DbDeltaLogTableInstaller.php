<?php
/**
 * Database delta log table installer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Logging\LogTableSchema;

/**
 * Installs the bounded log table through WordPress dbDelta().
 */
final class DbDeltaLogTableInstaller implements LogTableInstallerInterface {

	/**
	 * Install or upgrade the log table.
	 *
	 * @return InstallerResult
	 */
	public function install(): InstallerResult {
		/**
		 * WordPress database object.
		 *
		 * @var \wpdb|null $wpdb
		 */
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return InstallerResult::failure(
				array( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ),
				array( 'The WordPress database object is unavailable.' )
			);
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			$upgrade_file = $this->upgrade_file();

			if ( '' === $upgrade_file || ! file_exists( $upgrade_file ) ) {
				return InstallerResult::failure(
					array( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ),
					array( 'The WordPress upgrade library is unavailable.' )
				);
			}

			require_once $upgrade_file;
		}

		$table_name = LogTableSchema::table_name( $wpdb->prefix );
		$sql        = LogTableSchema::sql( $wpdb->prefix, $wpdb->get_charset_collate() );

		\dbDelta( $sql );

		if ( ! $this->table_exists( $wpdb, $table_name ) ) {
			return InstallerResult::failure(
				array( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ),
				array( 'The log table could not be verified after creation.' )
			);
		}

		return InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) );
	}

	/**
	 * Get the WordPress upgrade library path.
	 *
	 * @return string
	 */
	private function upgrade_file(): string {
		if ( ! defined( 'ABSPATH' ) ) {
			return '';
		}

		return (string) constant( 'ABSPATH' ) . 'wp-admin/includes/upgrade.php';
	}

	/**
	 * Verify the table exists after dbDelta().
	 *
	 * @param \wpdb  $wpdb WordPress database object.
	 * @param string $table_name Expected table name.
	 * @return bool
	 */
	private function table_exists( \wpdb $wpdb, string $table_name ): bool {
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $table_name === $found;
	}
}
