<?php
/**
 * Attachments REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkPreviewService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionAccessDeniedException;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionNotFoundException;

/**
 * Registers attachment detail and action routes.
 */
final class AttachmentsController implements RestControllerInterface {

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
	 * Details service.
	 *
	 * @var AttachmentDetailsService
	 */
	private $details;

	/**
	 * Actions service.
	 *
	 * @var AttachmentActionService
	 */
	private $actions;

	/**
	 * Bulk preview service.
	 *
	 * @var BulkPreviewService
	 */
	private $preview;

	/**
	 * Create the controller.
	 *
	 * @param RestRuntimeInterface     $runtime REST runtime.
	 * @param RestErrorFactory         $errors Error factory.
	 * @param AttachmentDetailsService $details Details service.
	 * @param AttachmentActionService  $actions Action service.
	 * @param BulkPreviewService       $preview Bulk preview service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		AttachmentDetailsService $details,
		AttachmentActionService $actions,
		BulkPreviewService $preview
	) {
		$this->runtime = $runtime;
		$this->errors  = $errors;
		$this->details = $details;
		$this->actions = $actions;
		$this->preview = $preview;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace = rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' );
		$id_args   = array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => array( $this, 'sanitize_attachment_id' ),
				'validate_callback' => array( $this, 'validate_attachment_id' ),
			),
		);

		$this->runtime->register_route(
			$namespace,
			'/attachments',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_attachments' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => array(
						'scan_token' => array(
							'required'          => true,
							'sanitize_callback' => array( $this, 'sanitize_scan_token' ),
							'validate_callback' => array( $this, 'validate_scan_token' ),
						),
						'page'       => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_pagination_number' ),
							'validate_callback' => array( $this, 'validate_pagination_number' ),
						),
						'per_page'   => array(
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_pagination_number' ),
							'validate_callback' => array( $this, 'validate_pagination_number' ),
						),
					),
				),
			)
		);

		$this->runtime->register_route(
			$namespace,
			'/attachments/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_attachment' ),
					'permission_callback' => array( $this, 'can_use_attachment_routes' ),
					'args'                => $id_args,
				),
			)
		);

		$this->runtime->register_route(
			$namespace,
			'/attachments/(?P<id>[\d]+)/optimize',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'optimize_attachment' ),
					'permission_callback' => array( $this, 'can_use_attachment_routes' ),
					'args'                => array_merge(
						$id_args,
						array(
							'force' => array(
								'required'          => false,
								'sanitize_callback' => array( $this, 'sanitize_force' ),
								'validate_callback' => array( $this, 'validate_force' ),
							),
						)
					),
				),
			)
		);

		foreach (
			array(
				'/attachments/(?P<id>[\d]+)/retry'     => 'retry_attachment',
				'/attachments/(?P<id>[\d]+)/reconcile' => 'reconcile_attachment',
				'/attachments/(?P<id>[\d]+)/exclude'   => 'exclude_attachment',
				'/attachments/(?P<id>[\d]+)/include'   => 'include_attachment',
			) as $route => $callback
		) {
			$this->runtime->register_route(
				$namespace,
				$route,
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, $callback ),
						'permission_callback' => array( $this, 'can_use_attachment_routes' ),
						'args'                => $id_args,
					),
				)
			);
		}
	}

	/**
	 * Permission callback for attachment routes.
	 *
	 * @return bool|mixed
	 */
	public function can_use_attachment_routes() {
		if ( $this->runtime->current_user_can( 'upload_files' ) ) {
			return true;
		}

		return $this->errors->forbidden();
	}

	/**
	 * Permission callback for global bulk routes.
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
	 * Route callback for session-scoped preview browsing.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function list_attachments( $request ) {
		$scan_token = $this->sanitize_scan_token( RequestData::param( $request, 'scan_token', '' ) );
		$page       = $this->sanitize_pagination_number( RequestData::param( $request, 'page', 1 ) );
		$per_page   = $this->sanitize_pagination_number( RequestData::param( $request, 'per_page', 20 ) );

		if ( '' === $scan_token ) {
			return $this->errors->invalid_scan_token();
		}

		if ( ! $this->validate_pagination_number( RequestData::param( $request, 'page', 1 ) ) ) {
			return $this->errors->invalid_preview_page();
		}

		if ( ! $this->validate_pagination_number( RequestData::param( $request, 'per_page', 20 ) ) ) {
			return $this->errors->invalid_preview_per_page();
		}

		try {
			return $this->runtime->response(
				$this->preview->preview(
					$scan_token,
					$this->runtime->current_user_id(),
					$page,
					$per_page
				)->to_array(),
				200
			);
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
	}

	/**
	 * Route callback for attachment details.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function get_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		try {
			return $this->runtime->response( $this->details->details( $attachment_id ), 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->unexpected();
		}
	}

	/**
	 * Route callback for optimize.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function optimize_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		if ( RequestData::has_param( $request, 'force' ) && null === RequestData::boolean( $request, 'force' ) ) {
			return $this->errors->invalid_force_flag();
		}

		return $this->action_response( $this->actions->optimize( $attachment_id, RequestData::boolean( $request, 'force' ) === true ) );
	}

	/**
	 * Route callback for retry.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function retry_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		return $this->action_response( $this->actions->retry( $attachment_id ) );
	}

	/**
	 * Route callback for reconcile.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function reconcile_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		return $this->action_response( $this->actions->reconcile( $attachment_id ) );
	}

	/**
	 * Route callback for exclude.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function exclude_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		return $this->action_response( $this->actions->exclude( $attachment_id ) );
	}

	/**
	 * Route callback for include.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function include_attachment( $request ) {
		$attachment_id = $this->validated_attachment_id( $request );

		if ( ! is_int( $attachment_id ) ) {
			return $attachment_id;
		}

		$editable = $this->ensure_editable( $attachment_id );

		if ( true !== $editable ) {
			return $editable;
		}

		return $this->action_response( $this->actions->include( $attachment_id ) );
	}

	/**
	 * Sanitize the attachment ID.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_attachment_id( $value ): int {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Validate the attachment ID.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_attachment_id( $value ): bool {
		return is_numeric( $value ) && 0 < (int) $value;
	}

	/**
	 * Sanitize the force flag.
	 *
	 * @param mixed $value Raw value.
	 * @return bool|null
	 */
	public function sanitize_force( $value ): ?bool {
		return RequestData::normalize_bool( $value );
	}

	/**
	 * Validate the force flag.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_force( $value ): bool {
		return null === $value || null !== RequestData::normalize_bool( $value );
	}

	/**
	 * Sanitize one bulk scan token.
	 *
	 * @param mixed $value Raw token.
	 * @return string
	 */
	public function sanitize_scan_token( $value ): string {
		return is_scalar( $value ) ? BulkScanSession::normalize_token( (string) $value ) : '';
	}

	/**
	 * Validate one bulk scan token.
	 *
	 * @param mixed $value Raw token.
	 * @return bool
	 */
	public function validate_scan_token( $value ): bool {
		return is_scalar( $value ) && '' !== $this->sanitize_scan_token( $value );
	}

	/**
	 * Sanitize one pagination number.
	 *
	 * @param mixed $value Raw number.
	 * @return int
	 */
	public function sanitize_pagination_number( $value ): int {
		return is_numeric( $value ) ? max( 1, (int) $value ) : 1;
	}

	/**
	 * Validate one pagination number.
	 *
	 * @param mixed $value Raw number.
	 * @return bool
	 */
	public function validate_pagination_number( $value ): bool {
		return is_numeric( $value ) && 0 < (int) $value;
	}

	/**
	 * Validate the attachment ID, existence, and image eligibility.
	 *
	 * @param mixed $request Request object.
	 * @return int|mixed
	 */
	private function validated_attachment_id( $request ) {
		$attachment_id = RequestData::positive_int( $request, 'id' );

		if ( 0 === $attachment_id ) {
			return $this->errors->invalid_attachment_id();
		}

		if ( ! $this->runtime->attachment_exists( $attachment_id ) ) {
			return $this->errors->attachment_not_found( $attachment_id );
		}

		if ( ! $this->runtime->attachment_is_image( $attachment_id ) ) {
			return $this->errors->attachment_not_image( $attachment_id );
		}

		return $attachment_id;
	}

	/**
	 * Ensure the current user can edit the attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return true|mixed
	 */
	private function ensure_editable( int $attachment_id ) {
		if ( $this->runtime->current_user_can( 'edit_post', $attachment_id ) ) {
			return true;
		}

		return $this->errors->forbidden();
	}

	/**
	 * Convert an action result into a REST response.
	 *
	 * @param AttachmentActionResult $result Action result.
	 * @return mixed
	 */
	private function action_response( AttachmentActionResult $result ) {
		if ( $result->is_successful() ) {
			return $this->runtime->response( $result->to_array(), 200 );
		}

		return $this->runtime->error(
			(string) $result->error_code(),
			(string) $result->error_message(),
			$result->status_code(),
			$result->to_array()
		);
	}
}
