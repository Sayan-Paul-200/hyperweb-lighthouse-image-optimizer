<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Sayan-Paul-200
 * @since             1.0.0
 * @package           Hyperweb_Lighthouse_Image_Optimizer
 *
 * @wordpress-plugin
 * Plugin Name:       HyperWeb Lighthouse Image Optimizer
 * Plugin URI:        https://hyperweblabs.in/
 * Description:       Optimize WordPress images for better Lighthouse performance by generating and serving WebP or AVIF versions, reducing image payloads, and preserving original files.
 * Version:           1.0.0
 * Author:            Sayan Paul
 * Author URI:        https://github.com/Sayan-Paul-200/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hyperweb-lighthouse-image-optimizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hyperweb-lighthouse-image-optimizer-activator.php
 */
function activate_hyperweb_lighthouse_image_optimizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hyperweb-lighthouse-image-optimizer-activator.php';
	Hyperweb_Lighthouse_Image_Optimizer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hyperweb-lighthouse-image-optimizer-deactivator.php
 */
function deactivate_hyperweb_lighthouse_image_optimizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hyperweb-lighthouse-image-optimizer-deactivator.php';
	Hyperweb_Lighthouse_Image_Optimizer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hyperweb_lighthouse_image_optimizer' );
register_deactivation_hook( __FILE__, 'deactivate_hyperweb_lighthouse_image_optimizer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hyperweb-lighthouse-image-optimizer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hyperweb_lighthouse_image_optimizer() {

	$plugin = new Hyperweb_Lighthouse_Image_Optimizer();
	$plugin->run();

}
run_hyperweb_lighthouse_image_optimizer();
