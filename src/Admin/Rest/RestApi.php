<?php
/**
 * REST API provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers the plugin REST controllers on rest_api_init.
 */
final class RestApi implements HookProviderInterface {

	/**
	 * REST controllers.
	 *
	 * @var RestControllerInterface[]
	 */
	private $controllers;

	/**
	 * Create the provider.
	 *
	 * @param RestControllerInterface[] $controllers Controllers.
	 */
	public function __construct( array $controllers ) {
		$this->controllers = array_values(
			array_filter(
				$controllers,
				static function ( $controller ): bool {
					return $controller instanceof RestControllerInterface;
				}
			)
		);
	}

	/**
	 * Register the REST init hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'rest_api_init', array( $this, 'register_routes' ), 10, 0 );
	}

	/**
	 * Register all controller routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}
