<?php
/**
 * Logs REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Logging\LogBrowserService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogDeletionService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogQuery;
use HyperWeb\LighthouseImageOptimizer\Logging\LogRetentionService;

/**
 * Registers logs browsing and retention routes.
 */
final class LogsController implements RestControllerInterface {

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
	 * Log browser.
	 *
	 * @var LogBrowserService
	 */
	private $browser;

	/**
	 * Log deletion service.
	 *
	 * @var LogDeletionService
	 */
	private $deletion;

	/**
	 * Log retention service.
	 *
	 * @var LogRetentionService
	 */
	private $retention;

	/**
	 * Create the controller.
	 *
	 * @param RestRuntimeInterface $runtime REST runtime.
	 * @param RestErrorFactory     $errors Error factory.
	 * @param LogBrowserService    $browser Log browser.
	 * @param LogDeletionService   $deletion Log deletion service.
	 * @param LogRetentionService  $retention Log retention service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		LogBrowserService $browser,
		LogDeletionService $deletion,
		LogRetentionService $retention
	) {
		$this->runtime   = $runtime;
		$this->errors    = $errors;
		$this->browser   = $browser;
		$this->deletion  = $deletion;
		$this->retention = $retention;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace = rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' );

		$this->runtime->register_route(
			$namespace,
			'/logs',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_logs' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => array(
						'level' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_level' ),
							'validate_callback' => array( $this, 'validate_level' ),
						),
						'code' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_code' ),
							'validate_callback' => array( $this, 'validate_code' ),
						),
						'attachment_id' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_attachment_id' ),
							'validate_callback' => array( $this, 'validate_attachment_id' ),
						),
						'page' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_page' ),
							'validate_callback' => array( $this, 'validate_page' ),
						),
						'per_page' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_per_page' ),
							'validate_callback' => array( $this, 'validate_per_page' ),
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_logs' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
				),
			)
		);

		$this->runtime->register_route(
			$namespace,
			'/logs/retention',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_retention' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => array(
						'retention_days' => array(
							'required'          => true,
							'sanitize_callback' => array( $this, 'sanitize_retention_days' ),
							'validate_callback' => array( $this, 'validate_retention_days' ),
						),
					),
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
	 * Handle paginated log browsing.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function get_logs( $request ) {
		if ( RequestData::has_param( $request, 'level' ) && ! $this->validate_level( RequestData::param( $request, 'level', null ) ) ) {
			return $this->errors->invalid_log_level();
		}

		if ( RequestData::has_param( $request, 'code' ) && ! $this->validate_code( RequestData::param( $request, 'code', null ) ) ) {
			return $this->errors->invalid_log_code();
		}

		if ( RequestData::has_param( $request, 'attachment_id' ) && ! $this->validate_attachment_id( RequestData::param( $request, 'attachment_id', null ) ) ) {
			return $this->errors->invalid_log_attachment_id();
		}

		if ( RequestData::has_param( $request, 'page' ) && ! $this->validate_page( RequestData::param( $request, 'page', null ) ) ) {
			return $this->errors->invalid_log_page();
		}

		if ( RequestData::has_param( $request, 'per_page' ) && ! $this->validate_per_page( RequestData::param( $request, 'per_page', null ) ) ) {
			return $this->errors->invalid_log_per_page();
		}

		try {
			return $this->runtime->response(
				$this->browser->page(
					new LogQuery(
						$this->sanitize_level( RequestData::param( $request, 'level', LogQuery::LEVEL_ALL ) ),
						$this->sanitize_code( RequestData::param( $request, 'code', null ) ),
						$this->sanitize_attachment_id( RequestData::param( $request, 'attachment_id', null ) ),
						$this->sanitize_page( RequestData::param( $request, 'page', LogQuery::DEFAULT_PAGE ) ),
						$this->sanitize_per_page( RequestData::param( $request, 'per_page', LogQuery::DEFAULT_PER_PAGE ) )
					)
				)->to_array(),
				200
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->logs_unavailable();
		}
	}

	/**
	 * Delete one bounded clear-all logs batch.
	 *
	 * @return mixed
	 */
	public function delete_logs() {
		try {
			return $this->runtime->response(
				array(
					'action' => 'clear_all',
					'result' => $this->deletion->clear_all()->to_array(),
				),
				200
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->logs_delete_unavailable();
		}
	}

	/**
	 * Save log retention days.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function update_retention( $request ) {
		if ( ! $this->validate_retention_days( RequestData::param( $request, 'retention_days', null ) ) ) {
			return $this->errors->invalid_log_retention_days();
		}

		try {
			return $this->runtime->response(
				array(
					'action' => 'save_retention',
					'result' => $this->retention->update(
						$this->sanitize_retention_days( RequestData::param( $request, 'retention_days', 30 ) )
					)->to_array(),
				),
				200
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->logs_retention_unavailable();
		}
	}

	/**
	 * Sanitize one log level filter.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_level( $value ): string {
		return is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : LogQuery::LEVEL_ALL;
	}

	/**
	 * Validate one log level filter.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_level( $value ): bool {
		return null === $value || ( is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), LogQuery::levels(), true ) );
	}

	/**
	 * Sanitize one exact code filter.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	public function sanitize_code( $value ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$code = strtolower( trim( (string) $value ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return '' === $code ? null : substr( $code, 0, 64 );
	}

	/**
	 * Validate one exact code filter.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_code( $value ): bool {
		if ( null === $value || '' === $value ) {
			return true;
		}

		return is_scalar( $value ) && 1 === preg_match( '/^[A-Za-z0-9_]{1,64}$/', trim( (string) $value ) );
	}

	/**
	 * Sanitize one optional attachment filter.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	public function sanitize_attachment_id( $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return is_numeric( $value ) && 0 < (int) $value ? (int) $value : null;
	}

	/**
	 * Validate one optional attachment filter.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_attachment_id( $value ): bool {
		return null === $value || '' === $value || ( is_numeric( $value ) && 0 < (int) $value );
	}

	/**
	 * Sanitize one page number.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_page( $value ): int {
		return is_numeric( $value ) ? max( 1, (int) $value ) : LogQuery::DEFAULT_PAGE;
	}

	/**
	 * Validate one page number.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_page( $value ): bool {
		return null === $value || ( is_numeric( $value ) && 0 < (int) $value );
	}

	/**
	 * Sanitize one page-size value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_per_page( $value ): int {
		return is_numeric( $value ) ? min( LogQuery::MAX_PER_PAGE, max( 1, (int) $value ) ) : LogQuery::DEFAULT_PER_PAGE;
	}

	/**
	 * Validate one page-size value.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_per_page( $value ): bool {
		return null === $value || ( is_numeric( $value ) && 0 < (int) $value );
	}

	/**
	 * Sanitize requested retention days.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_retention_days( $value ): int {
		return is_numeric( $value ) ? max( 1, (int) $value ) : 30;
	}

	/**
	 * Validate requested retention days.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_retention_days( $value ): bool {
		return is_numeric( $value ) && 0 < (int) $value;
	}
}
