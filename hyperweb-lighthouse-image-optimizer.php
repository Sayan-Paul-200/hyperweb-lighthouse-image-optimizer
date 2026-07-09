<?php
/**
 * The plugin bootstrap file.
 *
 * @link              https://github.com/Sayan-Paul-200
 * @since             0.1.0-alpha.3
 * @package           Hyperweb_Lighthouse_Image_Optimizer
 *
 * @wordpress-plugin
 * Plugin Name:       HyperWeb Lighthouse Image Optimizer
 * Plugin URI:        https://hyperweblabs.in/
 * Description:       Optimize WordPress images for better Lighthouse performance by generating and serving WebP or AVIF versions, reducing image payloads, and preserving original files.
 * Version:           0.1.0-alpha.3
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Sayan Paul
 * Author URI:        https://github.com/Sayan-Paul-200/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hyperweb-lighthouse-image-optimizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION', '0.1.0-alpha.3' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION', '1' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION', '1' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP', '7.4' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_WP' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_WP', '6.5' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_ACTION_SCHEDULER_VERSION' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_ACTION_SCHEDULER_VERSION', '3.9.3' );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_FILE' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_FILE', __FILE__ );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_URL' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME' ) ) {
	define( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Get the WordPress version currently loading the plugin.
 *
 * @return string|null
 */
function hwlio_get_wordpress_version() {
	global $wp_version;

	if ( isset( $wp_version ) && is_string( $wp_version ) ) {
		return $wp_version;
	}

	return null;
}

/**
 * Get bootstrap requirement failure messages.
 *
 * @return string[]
 */
function hwlio_get_bootstrap_failures() {
	if ( version_compare( PHP_VERSION, HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP, '<' ) ) {
		return array(
			sprintf(
				/* translators: 1: minimum PHP version, 2: current PHP version. */
				__( 'HyperWeb Lighthouse Image Optimizer requires PHP %1$s or higher. This site is running PHP %2$s.', 'hyperweb-lighthouse-image-optimizer' ),
				HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP,
				PHP_VERSION
			),
		);
	}

	$requirements_file = HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'src/Infrastructure/Requirements.php';

	if ( ! file_exists( $requirements_file ) ) {
		return array(
			__( 'HyperWeb Lighthouse Image Optimizer is missing its bootstrap requirements helper.', 'hyperweb-lighthouse-image-optimizer' ),
		);
	}

	require_once $requirements_file;

	$required_files = array(
		HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'vendor/autoload.php'                          => 'vendor/autoload.php',
		HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'libraries/action-scheduler/action-scheduler.php' => 'libraries/action-scheduler/action-scheduler.php',
	);

	return HyperWeb\LighthouseImageOptimizer\Infrastructure\Requirements::evaluate(
		PHP_VERSION,
		hwlio_get_wordpress_version(),
		HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP,
		HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_WP,
		$required_files
	);
}

/**
 * Show admin-facing bootstrap failures.
 *
 * @param string[] $failures Requirement failure messages.
 * @return void
 */
function hwlio_register_bootstrap_failure_notice( $failures ) {
	if ( ! is_admin() ) {
		return;
	}

	add_action(
		'admin_notices',
		static function () use ( $failures ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'HyperWeb Lighthouse Image Optimizer is inactive because bootstrap requirements are not met:', 'hyperweb-lighthouse-image-optimizer' );
			echo '</p><ul>';

			foreach ( $failures as $failure ) {
				echo '<li>' . esc_html( $failure ) . '</li>';
			}

			echo '</ul></div>';
		}
	);
}

/**
 * Load runtime dependencies required before the legacy plugin class runs.
 *
 * @return void
 */
function hwlio_load_runtime_dependencies() {
	require_once HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'vendor/autoload.php';
	require_once HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'libraries/action-scheduler/action-scheduler.php';
}

/**
 * The code that runs during plugin activation.
 */
function activate_hyperweb_lighthouse_image_optimizer() {
	$failures = hwlio_get_bootstrap_failures();

	if ( array() !== $failures ) {
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME );
		}

		wp_die(
			esc_html( implode( ' ', $failures ) ),
			esc_html__( 'Plugin activation failed', 'hyperweb-lighthouse-image-optimizer' ),
			array( 'back_link' => true )
		);
	}

	hwlio_load_runtime_dependencies();

	require_once HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'includes/class-hyperweb-lighthouse-image-optimizer-activator.php';
	Hyperweb_Lighthouse_Image_Optimizer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_hyperweb_lighthouse_image_optimizer() {
	require_once HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'includes/class-hyperweb-lighthouse-image-optimizer-deactivator.php';
	Hyperweb_Lighthouse_Image_Optimizer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hyperweb_lighthouse_image_optimizer' );
register_deactivation_hook( __FILE__, 'deactivate_hyperweb_lighthouse_image_optimizer' );

$hwlio_bootstrap_failures = hwlio_get_bootstrap_failures();

if ( array() !== $hwlio_bootstrap_failures ) {
	hwlio_register_bootstrap_failure_notice( $hwlio_bootstrap_failures );
	return;
}

hwlio_load_runtime_dependencies();

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_PATH . 'includes/class-hyperweb-lighthouse-image-optimizer.php';

/**
 * Begins execution of the plugin.
 *
 * @since 0.1.0-alpha.3
 * @return void
 */
function run_hyperweb_lighthouse_image_optimizer() {
	$plugin = new Hyperweb_Lighthouse_Image_Optimizer();
	$plugin->run();
}

run_hyperweb_lighthouse_image_optimizer();
