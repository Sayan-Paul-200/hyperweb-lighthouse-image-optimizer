<?php
/**
 * REST controller contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Registers plugin-owned REST routes.
 */
interface RestControllerInterface {

	/**
	 * Register controller routes.
	 *
	 * @return void
	 */
	public function register_routes(): void;
}
