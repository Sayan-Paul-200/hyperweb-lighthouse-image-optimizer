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
	 * @since  0.1.0-alpha.3
	 * @return void
	 */
	public static function activate() {
		$version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			: '0.1.0-alpha.3';

		$db_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			: '1';

		$schema_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			? (int) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			: 1;

		$installer = HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer::for_wordpress(
			$version,
			$db_version,
			$schema_version
		);

		$installer->install();
	}
}
