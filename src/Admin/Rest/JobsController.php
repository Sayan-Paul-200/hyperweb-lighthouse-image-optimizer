<?php
/**
 * Jobs REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueProgress;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionIncompleteException;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionAccessDeniedException;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionNotFoundException;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;

/**
 * Registers bulk dry-run scan routes.
 */
final class JobsController implements RestControllerInterface {

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
	 * Bulk scan service.
	 *
	 * @var BulkScanService
	 */
	private $scans;

	/**
	 * Bulk queue service.
	 *
	 * @var BulkQueueService
	 */
	private $queues;

	/**
	 * Queue control service.
	 *
	 * @var QueueControlService
	 */
	private $queue_control;

	/**
	 * Create the controller.
	 *
	 * @param RestRuntimeInterface $runtime REST runtime.
	 * @param RestErrorFactory     $errors Error factory.
	 * @param BulkScanService      $scans Bulk scan service.
	 * @param BulkQueueService     $queues Bulk queue service.
	 * @param QueueControlService  $queue_control Queue control service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		BulkScanService $scans,
		BulkQueueService $queues,
		QueueControlService $queue_control
	) {
		$this->runtime       = $runtime;
		$this->errors        = $errors;
		$this->scans         = $scans;
		$this->queues        = $queues;
		$this->queue_control = $queue_control;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/jobs/scan',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'scan_jobs' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => array(
						'scan_token'     => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_scan_token' ),
							'validate_callback' => array( $this, 'validate_scan_token' ),
						),
						'scan_scope'     => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_scan_scope' ),
							'validate_callback' => array( $this, 'validate_scan_scope' ),
						),
						'target_format'  => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_target_format' ),
							'validate_callback' => array( $this, 'validate_target_format' ),
						),
						'date_from'      => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_date' ),
							'validate_callback' => array( $this, 'validate_date' ),
						),
						'date_to'        => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_date' ),
							'validate_callback' => array( $this, 'validate_date' ),
						),
						'attachment_ids' => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_attachment_ids' ),
							'validate_callback' => array( $this, 'validate_attachment_ids' ),
						),
					),
				),
			)
		);

		foreach (
			array(
				'/jobs/queue'  => 'queue_jobs',
				'/jobs/retry'  => 'retry_jobs',
				'/jobs/pause'  => 'pause_jobs',
				'/jobs/resume' => 'resume_jobs',
			) as $route => $callback
		) {
			$this->runtime->register_route(
				rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
				$route,
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, $callback ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => in_array( $callback, array( 'pause_jobs', 'resume_jobs' ), true )
							? array()
							: array(
								'scan_token' => array(
									'required'          => true,
									'sanitize_callback' => array( $this, 'sanitize_scan_token' ),
									'validate_callback' => array( $this, 'validate_scan_token' ),
								),
							),
					),
				)
			);
		}

		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/jobs/pending',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'cancel_pending_jobs' ),
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
	 * Handle the dry-run scan route.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function scan_jobs( $request ) {
		if ( RequestData::has_param( $request, 'scan_token' ) && '' === $this->sanitize_scan_token( RequestData::param( $request, 'scan_token', '' ) ) ) {
			return $this->errors->invalid_scan_token();
		}

		if ( RequestData::has_param( $request, 'scan_scope' ) && ! $this->validate_scan_scope( RequestData::param( $request, 'scan_scope', null ) ) ) {
			return $this->errors->invalid_scan_scope();
		}

		if ( RequestData::has_param( $request, 'target_format' ) && ! $this->validate_target_format( RequestData::param( $request, 'target_format', null ) ) ) {
			return $this->errors->invalid_target_format();
		}

		if (
			( RequestData::has_param( $request, 'date_from' ) && ! $this->validate_date( RequestData::param( $request, 'date_from', null ) ) ) ||
			( RequestData::has_param( $request, 'date_to' ) && ! $this->validate_date( RequestData::param( $request, 'date_to', null ) ) )
		) {
			return $this->errors->invalid_date_filter();
		}

		if ( RequestData::has_param( $request, 'attachment_ids' ) && ! $this->validate_attachment_ids( RequestData::param( $request, 'attachment_ids', null ) ) ) {
			return $this->errors->invalid_attachment_ids();
		}

		$date_from = $this->sanitize_date( RequestData::param( $request, 'date_from', null ) );
		$date_to   = $this->sanitize_date( RequestData::param( $request, 'date_to', null ) );

		if ( null !== $date_from && null !== $date_to && $date_from > $date_to ) {
			return $this->errors->invalid_date_range();
		}

		try {
			$session = $this->run_scan( $request );
		} catch ( BulkScanSessionNotFoundException $exception ) {
			unset( $exception );

			return $this->errors->bulk_scan_session_not_found();
		} catch ( BulkScanSessionAccessDeniedException $exception ) {
			unset( $exception );

			return $this->errors->bulk_scan_session_forbidden();
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->bulk_scan_unavailable();
		}

		return $this->runtime->response( $this->payload( $session ), 200 );
	}

	/**
	 * Continue queueing the current scan session.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function queue_jobs( $request ) {
		return $this->queue_response( $request, BulkQueueProgress::MODE_QUEUE );
	}

	/**
	 * Continue retry-queueing the current scan session.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function retry_jobs( $request ) {
		return $this->queue_response( $request, BulkQueueProgress::MODE_RETRY );
	}

	/**
	 * Pause global queue execution.
	 *
	 * @return mixed
	 */
	public function pause_jobs() {
		try {
			return $this->runtime->response(
				array(
					'action'       => 'pause',
					'queueControl' => $this->queue_control->pause( $this->runtime->current_user_id() ),
				),
				200
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->bulk_queue_unavailable();
		}
	}

	/**
	 * Resume global queue execution.
	 *
	 * @return mixed
	 */
	public function resume_jobs() {
		try {
			return $this->runtime->response(
				array(
					'action'       => 'resume',
					'queueControl' => $this->queue_control->resume( $this->runtime->current_user_id() ),
				),
				200
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->bulk_queue_unavailable();
		}
	}

	/**
	 * Cancel pending plugin-owned attachment jobs.
	 *
	 * @return mixed
	 */
	public function cancel_pending_jobs() {
		try {
			$payload           = $this->queue_control->cancel_pending();
			$payload['action'] = 'cancel_pending';

			return $this->runtime->response( $payload, 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->bulk_queue_unavailable();
		}
	}

	/**
	 * Sanitize one scan token.
	 *
	 * @param mixed $value Raw token.
	 * @return string
	 */
	public function sanitize_scan_token( $value ): string {
		return is_scalar( $value ) ? BulkScanSession::normalize_token( (string) $value ) : '';
	}

	/**
	 * Validate one scan token.
	 *
	 * @param mixed $value Raw token.
	 * @return bool
	 */
	public function validate_scan_token( $value ): bool {
		return null === $value || '' === $value || ( is_scalar( $value ) && '' !== $this->sanitize_scan_token( $value ) );
	}

	/**
	 * Sanitize one scan scope.
	 *
	 * @param mixed $value Raw scope.
	 * @return string
	 */
	public function sanitize_scan_scope( $value ): string {
		return is_scalar( $value ) ? BulkScanFilters::normalize_scan_scope( (string) $value ) : BulkScanFilters::SCOPE_ALL_ELIGIBLE;
	}

	/**
	 * Validate one scan scope.
	 *
	 * @param mixed $value Raw scope.
	 * @return bool
	 */
	public function validate_scan_scope( $value ): bool {
		return null === $value || ( is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), BulkScanFilters::scopes(), true ) );
	}

	/**
	 * Sanitize one target-format selector.
	 *
	 * @param mixed $value Raw target-format selector.
	 * @return string
	 */
	public function sanitize_target_format( $value ): string {
		return is_scalar( $value ) ? BulkScanFilters::normalize_target_format( (string) $value ) : BulkScanFilters::TARGET_ALL_ENABLED;
	}

	/**
	 * Validate one target-format selector.
	 *
	 * @param mixed $value Raw selector.
	 * @return bool
	 */
	public function validate_target_format( $value ): bool {
		return null === $value || ( is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), BulkScanFilters::target_formats(), true ) );
	}

	/**
	 * Sanitize one date string.
	 *
	 * @param mixed $value Raw date.
	 * @return string|null
	 */
	public function sanitize_date( $value ): ?string {
		return BulkScanFilters::normalize_date( $value );
	}

	/**
	 * Validate one date string.
	 *
	 * @param mixed $value Raw date.
	 * @return bool
	 */
	public function validate_date( $value ): bool {
		return null === $value || '' === $value || null !== $this->sanitize_date( $value );
	}

	/**
	 * Sanitize one attachment-ID filter list.
	 *
	 * @param mixed $value Raw IDs.
	 * @return int[]
	 */
	public function sanitize_attachment_ids( $value ): array {
		return BulkScanFilters::normalize_attachment_ids( $value );
	}

	/**
	 * Validate one attachment-ID filter list.
	 *
	 * @param mixed $value Raw IDs.
	 * @return bool
	 */
	public function validate_attachment_ids( $value ): bool {
		if ( null === $value || '' === $value ) {
			return true;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( ! is_scalar( $item ) || ! is_numeric( $item ) || 0 >= (int) $item ) {
					return false;
				}
			}

			return true;
		}

		if ( is_string( $value ) ) {
			return 1 === preg_match( '/^\s*\d+(?:[\s,]+\d+)*\s*$/', $value );
		}

		return false;
	}

	/**
	 * Run a new or resumed scan.
	 *
	 * @param mixed $request Request object.
	 * @return BulkScanSession
	 */
	private function run_scan( $request ): BulkScanSession {
		$owner_user_id = $this->runtime->current_user_id();
		$scan_token    = $this->sanitize_scan_token( RequestData::param( $request, 'scan_token', '' ) );

		if ( '' !== $scan_token ) {
			return $this->scans->continue_scan( $scan_token, $owner_user_id );
		}

		return $this->scans->start_scan(
			BulkScanFilters::from_array(
				array(
					'scan_scope'     => $this->sanitize_scan_scope( RequestData::param( $request, 'scan_scope', BulkScanFilters::SCOPE_ALL_ELIGIBLE ) ),
					'target_format'  => $this->sanitize_target_format( RequestData::param( $request, 'target_format', BulkScanFilters::TARGET_ALL_ENABLED ) ),
					'date_from'      => $this->sanitize_date( RequestData::param( $request, 'date_from', null ) ),
					'date_to'        => $this->sanitize_date( RequestData::param( $request, 'date_to', null ) ),
					'attachment_ids' => $this->sanitize_attachment_ids( RequestData::param( $request, 'attachment_ids', array() ) ),
				)
			),
			$owner_user_id
		);
	}

	/**
	 * Continue one bulk queue control flow.
	 *
	 * @param mixed  $request Request object.
	 * @param string $mode Queue mode.
	 * @return mixed
	 */
	private function queue_response( $request, string $mode ) {
		$scan_token = $this->sanitize_scan_token( RequestData::param( $request, 'scan_token', '' ) );

		if ( '' === $scan_token ) {
			return $this->errors->invalid_scan_token();
		}

		try {
			$session = BulkQueueProgress::MODE_RETRY === $mode
				? $this->queues->retry( $scan_token, $this->runtime->current_user_id() )
				: $this->queues->queue( $scan_token, $this->runtime->current_user_id() );
		} catch ( BulkScanSessionNotFoundException $exception ) {
			unset( $exception );

			return $this->errors->bulk_scan_session_not_found();
		} catch ( BulkScanSessionAccessDeniedException $exception ) {
			unset( $exception );

			return $this->errors->bulk_scan_session_forbidden();
		} catch ( BulkScanSessionIncompleteException $exception ) {
			unset( $exception );

			return $this->errors->bulk_scan_not_complete();
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->bulk_queue_unavailable();
		}

		return $this->runtime->response( $this->payload( $session, BulkQueueProgress::MODE_RETRY === $mode ? 'retry' : 'queue' ), 200 );
	}

	/**
	 * Build the normalized response payload.
	 *
	 * @param BulkScanSession $session Session.
	 * @param string          $action Action label.
	 * @return array<string,mixed>
	 */
	private function payload( BulkScanSession $session, string $action = 'scan' ): array {
		$payload = array(
			'action'       => 'scan',
			'scanToken'    => $session->token(),
			'createdAtGmt' => $session->created_at_gmt(),
			'updatedAtGmt' => $session->updated_at_gmt(),
			'filters'      => $session->filters()->to_array(),
			'progress'     => $session->progress()->to_array(),
			'summary'      => $session->summary()->to_array(),
		);

		if ( 'scan' !== $action ) {
			$payload['action']        = $action;
			$payload['queueProgress'] = $session->queue_progress()->to_array();
			$payload['queueSummary']  = $session->queue_summary()->to_array();
			$payload['queueControl']  = $this->queue_control->summary();
		}

		return $payload;
	}
}
