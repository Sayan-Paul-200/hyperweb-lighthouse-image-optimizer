<?php
/**
 * Activation entry point.
 *
 * @link       https://github.com/Sayan-Paul-200
 * @since      0.1.0-alpha.3
 *
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 */

/**
 * Runs activation-safe setup routines.
 *
 * @since      0.1.0-alpha.3
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 * @author     Sayan Paul <sayanpaul666.ap@gmail.com>
 */
class Hyperweb_Lighthouse_Image_Optimizer_Activator {

	/**
	 * Initialize plugin storage and setup state.
	 *
	 * Network activation intentionally installs only the current site context.
	 * Existing network sites upgrade lazily at runtime, and future sites initialize
	 * through the multisite integration hook.
	 *
	 * @since  0.1.0-alpha.3
	 * @param bool          $network_wide Whether WordPress is network-activating the plugin.
	 * @param callable|null $installer_factory Optional installer factory for tests.
	 * @return void
	 */
	public static function activate( bool $network_wide = false, ?callable $installer_factory = null ) {
		unset( $network_wide );

		$version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			: '0.1.0-alpha.4';

		$db_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			: '1';

		$schema_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			? (int) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			: 1;

		$installer = null;

		if ( null !== $installer_factory ) {
			$installer = call_user_func( $installer_factory, $version, $db_version, $schema_version );
		}

		if ( ! $installer instanceof HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer ) {
			$installer = HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer::for_wordpress(
				$version,
				$db_version,
				$schema_version
			);
		}

		$installer->install();
	}
}
