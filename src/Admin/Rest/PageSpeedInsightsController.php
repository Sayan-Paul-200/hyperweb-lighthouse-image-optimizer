<?php
/**
 * PageSpeed Insights REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsService;

/**
 * Registers cached and live PageSpeed Insights routes.
 */
final class PageSpeedInsightsController implements RestControllerInterface {

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
	 * Content inventory service.
	 *
	 * @var ContentInventoryService
	 */
	private $inventory;

	/**
	 * PSI service.
	 *
	 * @var PageSpeedInsightsService
	 */
	private $pagespeed;

	/**
	 * Create controller.
	 *
	 * @param RestRuntimeInterface     $runtime REST runtime.
	 * @param RestErrorFactory         $errors Error factory.
	 * @param ContentInventoryService  $inventory Content inventory service.
	 * @param PageSpeedInsightsService $pagespeed PSI service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		ContentInventoryService $inventory,
		PageSpeedInsightsService $pagespeed
	) {
		$this->runtime   = $runtime;
		$this->errors    = $errors;
		$this->inventory = $inventory;
		$this->pagespeed = $pagespeed;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/content/(?P<content_id>[\d]+)/pagespeed',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pagespeed' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => $this->route_args(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'run_pagespeed' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => $this->route_args(),
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
	 * Return one cached PSI report.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function get_pagespeed( $request ) {
		$content_id = RequestData::positive_int( $request, 'content_id' );
		$strategy   = $this->sanitize_strategy( RequestData::param( $request, 'strategy', 'mobile' ) );

		if ( 0 === $content_id ) {
			return $this->errors->invalid_content_id();
		}

		if ( ! $this->inventory->content_exists( $content_id ) ) {
			return $this->errors->content_not_found( $content_id );
		}

		if ( ! $this->validate_strategy( $strategy ) ) {
			return $this->errors->invalid_pagespeed_strategy();
		}

		try {
			return $this->runtime->response( $this->pagespeed->cached_report( $content_id, $strategy )->to_array(), 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->pagespeed_request_failed();
		}
	}

	/**
	 * Run one live PSI request.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function run_pagespeed( $request ) {
		$content_id = RequestData::positive_int( $request, 'content_id' );
		$strategy   = $this->sanitize_strategy( RequestData::param( $request, 'strategy', 'mobile' ) );

		if ( 0 === $content_id ) {
			return $this->errors->invalid_content_id();
		}

		if ( ! $this->inventory->content_exists( $content_id ) ) {
			return $this->errors->content_not_found( $content_id );
		}

		if ( ! $this->validate_strategy( $strategy ) ) {
			return $this->errors->invalid_pagespeed_strategy();
		}

		try {
			$result = $this->pagespeed->run_report( $content_id, $strategy );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->pagespeed_request_failed();
		}

		if ( ! $result->is_successful() ) {
			if ( 'pagespeed_disabled' === $result->code() ) {
				return $this->errors->pagespeed_disabled();
			}

			if ( 'pagespeed_public_url_unavailable' === $result->code() ) {
				return $this->errors->pagespeed_public_url_unavailable();
			}

			if ( 'pagespeed_quota_exceeded' === $result->code() ) {
				return $this->errors->pagespeed_quota_exceeded();
			}

			return $this->errors->pagespeed_request_failed();
		}

		return $this->runtime->response( $result->report() ? $result->report()->to_array() : array(), 200 );
	}

	/**
	 * Sanitize strategy.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_strategy( $value ): string {
		return is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : 'mobile';
	}

	/**
	 * Validate strategy.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_strategy( $value ): bool {
		return is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), array( 'mobile', 'desktop' ), true );
	}

	/**
	 * Get route arg definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function route_args(): array {
		return array(
			'content_id' => array(
				'required'          => true,
				'sanitize_callback' => array( $this, 'sanitize_content_id' ),
				'validate_callback' => array( $this, 'validate_content_id' ),
			),
			'strategy'   => array(
				'required'          => false,
				'sanitize_callback' => array( $this, 'sanitize_strategy' ),
				'validate_callback' => array( $this, 'validate_strategy' ),
			),
		);
	}

	/**
	 * Sanitize content ID.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_content_id( $value ): int {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Validate content ID.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_content_id( $value ): bool {
		return is_numeric( $value ) && 0 < (int) $value;
	}
}
