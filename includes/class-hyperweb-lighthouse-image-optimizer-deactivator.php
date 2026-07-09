<?php
/**
 * Deactivation entry point.
 *
 * @link       https://github.com/Sayan-Paul-200
 * @since      0.1.0-alpha.3
 *
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 */

/**
 * Runs non-destructive deactivation cleanup.
 *
 * @since      0.1.0-alpha.3
 * @package    Hyperweb_Lighthouse_Image_Optimizer
 * @subpackage Hyperweb_Lighthouse_Image_Optimizer/includes
 * @author     Sayan Paul <sayanpaul666.ap@gmail.com>
 */
class Hyperweb_Lighthouse_Image_Optimizer_Deactivator {

	/**
	 * Unschedule plugin-owned maintenance without deleting user data.
	 *
	 * @since  0.1.0-alpha.3
	 * @return void
	 */
	public static function deactivate() {
		if ( ! class_exists( \HyperWeb\LighthouseImageOptimizer\Infrastructure\Deactivator::class ) ) {
			return;
		}

		\HyperWeb\LighthouseImageOptimizer\Infrastructure\Deactivator::for_wordpress()->deactivate();
	}
}
