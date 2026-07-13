<?php
/**
 * Status REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;

/**
 * Registers the admin status route.
 */
final class StatusController implements RestControllerInterface {

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
	 * Status service.
	 *
	 * @var StatusSummaryService
	 */
	private $status;

	/**
	 * Refresh service.
	 *
	 * @var StatusRefreshService
	 */
	private $refresh;

	/**
	 * Create the controller.
	 *
	 * @param RestRuntimeInterface $runtime REST runtime.
	 * @param RestErrorFactory     $errors Error factory.
	 * @param StatusSummaryService $status Status service.
	 * @param StatusRefreshService $refresh Refresh service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		StatusSummaryService $status,
		StatusRefreshService $refresh
	) {
		$this->runtime = $runtime;
		$this->errors  = $errors;
		$this->status  = $status;
		$this->refresh = $refresh;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/status',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'recalculate_status' ),
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
	public function get_status() {
		try {
			return $this->runtime->response( $this->status->summary(), 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->unexpected();
		}
	}

	/**
	 * Queue one asynchronous statistics recalculation request.
	 *
	 * @return mixed
	 */
	public function recalculate_status() {
		try {
			$result = $this->refresh->request_recalculation();
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->status_recalculate_unavailable();
		}

		if ( ! $result->is_successful() ) {
			return $this->errors->status_recalculate_unavailable();
		}

		return $this->runtime->response( $result->to_array(), 200 );
	}
}
