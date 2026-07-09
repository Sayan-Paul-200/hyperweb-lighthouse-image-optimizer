<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/Sayan-Paul-200
 * @since      1.0.0
 *
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 * @author     Sayan Paul <sayanpaul666.ap@gmail.com>
 */
class Hyperweb_Lighthouse_Image_Optimizer_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'hyperweb-lighthouse-image-optimizer',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
