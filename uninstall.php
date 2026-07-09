<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link    https://github.com/Sayan-Paul-200
 * @since   0.1.0-alpha.3
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$hwlio_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $hwlio_autoload ) ) {
	return;
}

require_once $hwlio_autoload;

if (
	function_exists( 'is_multisite' )
	&& function_exists( 'is_network_admin' )
	&& is_multisite()
	&& is_network_admin()
) {
	\HyperWeb\LighthouseImageOptimizer\Infrastructure\NetworkUninstaller::for_wordpress(
		static function () {
			return \HyperWeb\LighthouseImageOptimizer\Infrastructure\Uninstaller::for_wordpress()->uninstall();
		}
	)->uninstall();

	return;
}

\HyperWeb\LighthouseImageOptimizer\Infrastructure\Uninstaller::for_wordpress()->uninstall();
