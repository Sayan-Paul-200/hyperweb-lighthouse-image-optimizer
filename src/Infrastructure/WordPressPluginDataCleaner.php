<?php
/**
 * WordPress plugin data cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Logging\LogTableSchema;

/**
 * Deletes plugin-owned data when destructive uninstall is explicitly enabled.
 */
final class WordPressPluginDataCleaner implements PluginDataCleanerInterface {

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Create the cleaner.
	 *
	 * @param OptionStoreInterface $options Option store.
	 */
	public function __construct( OptionStoreInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Delete plugin-owned data.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult {
		foreach ( LifecyclePolicy::owned_attachment_meta_keys() as $meta_key ) {
			if ( function_exists( 'delete_post_meta_by_key' ) ) {
				\delete_post_meta_by_key( $meta_key );
			}
		}

		$this->drop_log_table();

		foreach ( LifecyclePolicy::owned_options() as $option ) {
			$this->options->delete( $option );
		}

		return LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_DATA_DELETED ) );
	}

	/**
	 * Drop the plugin log table.
	 *
	 * @return void
	 */
	private function drop_log_table(): void {
		/**
		 * WordPress database object.
		 *
		 * @var \wpdb|null $wpdb
		 */
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$table_name = LogTableSchema::table_name( $wpdb->prefix );

		if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is validated as an identifier above.
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}
