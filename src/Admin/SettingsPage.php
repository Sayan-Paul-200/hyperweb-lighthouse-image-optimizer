<?php
/**
 * Settings admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Placeholder settings page for the admin shell.
 */
final class SettingsPage extends AbstractAdminPage {

	/**
	 * Create the page.
	 */
	public function __construct() {
		parent::__construct(
			'Settings',
			'The visible settings form will be added on top of the existing Settings API registration in a later subphase.'
		);
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'settings';
	}
}
