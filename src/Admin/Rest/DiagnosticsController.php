<?php
/**
 * Diagnostics REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;

/**
 * Registers the admin diagnostics route.
 */
final class DiagnosticsController implements RestControllerInterface {

	/**
	 * REST runtime.
	 *
	 * @var RestRuntimeInterface
	 */
	private $runtime;

	/**
	 * Error factory.
	 *
	 * @var RestErrorFactory
	 */
	private $errors;

	/**
	 * Diagnostics service.
	 *
	 * @var DiagnosticsServiceInterface
	 */
	private $diagnostics;

	/**
	 * Create the controller.
	 *
	 * @param RestRuntimeInterface       $runtime REST runtime.
	 * @param RestErrorFactory           $errors Error factory.
	 * @param DiagnosticsServiceInterface $diagnostics Diagnostics service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		DiagnosticsServiceInterface $diagnostics
	) {
		$this->runtime     = $runtime;
		$this->errors      = $errors;
		$this->diagnostics = $diagnostics;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/diagnostics',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_diagnostics' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
				),
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool|mixed
	 */
	public function can_manage_options() {
		if ( $this->runtime->current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->errors->forbidden();
	}

	/**
	 * Handle the route callback.
	 *
	 * @return mixed
	 */
	public function get_diagnostics() {
		try {
			return $this->runtime->response( $this->diagnostics->report(), 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->unexpected();
		}
	}
}
