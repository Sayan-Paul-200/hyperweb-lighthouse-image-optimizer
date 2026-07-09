<?php
/**
 * Defines the legacy plugin coordinator.
 *
 * @link       https://github.com/Sayan-Paul-200
 * @since      0.1.0-alpha.3
 *
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 */

/**
 * Coordinates the legacy bootstrap hooks that remain before Phase 1 composition.
 *
 * @since      0.1.0-alpha.3
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 * @author     Sayan Paul <sayanpaul666.ap@gmail.com>
 */
class Hyperweb_Lighthouse_Image_Optimizer {

	/**
	 * The loader that maintains and registers plugin hooks.
	 *
	 * @since  0.1.0-alpha.3
	 * @access protected
	 * @var    Hyperweb_Lighthouse_Image_Optimizer_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  0.1.0-alpha.3
	 * @access protected
	 * @var    string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  0.1.0-alpha.3
	 * @access protected
	 * @var    string
	 */
	protected $version;

	/**
	 * Initialize the coordinator and register bootstrap-safe hooks.
	 *
	 * @since 0.1.0-alpha.3
	 */
	public function __construct() {
		$this->version     = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' ) ? HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION : '0.1.0-alpha.3';
		$this->plugin_name = 'hyperweb-lighthouse-image-optimizer';

		$this->load_dependencies();
		$this->set_locale();
	}

	/**
	 * Load the legacy hook loader and i18n class.
	 *
	 * @since  0.1.0-alpha.3
	 * @access private
	 */
	private function load_dependencies() {
		require_once __DIR__ . '/class-hyperweb-lighthouse-image-optimizer-loader.php';
		require_once __DIR__ . '/class-hyperweb-lighthouse-image-optimizer-i18n.php';

		$this->loader = new Hyperweb_Lighthouse_Image_Optimizer_Loader();
	}

	/**
	 * Register the textdomain loading hook.
	 *
	 * @since  0.1.0-alpha.3
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new Hyperweb_Lighthouse_Image_Optimizer_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register the collected hooks with WordPress.
	 *
	 * @since 0.1.0-alpha.3
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get the plugin slug.
	 *
	 * @since  0.1.0-alpha.3
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the legacy hook loader.
	 *
	 * @since  0.1.0-alpha.3
	 * @return Hyperweb_Lighthouse_Image_Optimizer_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since  0.1.0-alpha.3
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
