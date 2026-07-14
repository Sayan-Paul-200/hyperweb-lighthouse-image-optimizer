<?php
/**
 * REST error factory.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Builds stable error responses for the admin REST controllers.
 */
final class RestErrorFactory {

	/**
	 * REST runtime.
	 *
	 * @var RestRuntimeInterface
	 */
	private $runtime;

	/**
	 * Create the factory.
	 *
	 * @param RestRuntimeInterface $runtime REST runtime.
	 */
	public function __construct( RestRuntimeInterface $runtime ) {
		$this->runtime = $runtime;
	}

	/**
	 * Build a forbidden response.
	 *
	 * @param string $message Error message.
	 * @return mixed
	 */
	public function forbidden( string $message = 'You are not allowed to perform this action.' ) {
		return $this->runtime->error( 'rest_forbidden', $message, 403 );
	}

	/**
	 * Build an invalid attachment ID response.
	 *
	 * @return mixed
	 */
	public function invalid_attachment_id() {
		return $this->runtime->error( 'invalid_attachment_id', 'A valid attachment ID is required.', 400 );
	}

	/**
	 * Build an invalid content ID response.
	 *
	 * @return mixed
	 */
	public function invalid_content_id() {
		return $this->runtime->error( 'invalid_content_id', 'A valid content ID is required.', 400 );
	}

	/**
	 * Build a content-not-found response.
	 *
	 * @param int $content_id Content ID.
	 * @return mixed
	 */
	public function content_not_found( int $content_id ) {
		return $this->runtime->error(
			'content_not_found',
			'The requested content record could not be found.',
			404,
			array(
				'content_id' => $content_id,
			)
		);
	}

	/**
	 * Build a content-inventory unavailable response.
	 *
	 * @return mixed
	 */
	public function content_inventory_unavailable() {
		return $this->runtime->error(
			'content_inventory_unavailable',
			'The requested content inventory could not be built right now.',
			503
		);
	}

	/**
	 * Build an invalid PageSpeed strategy response.
	 *
	 * @return mixed
	 */
	public function invalid_pagespeed_strategy() {
		return $this->runtime->error(
			'invalid_pagespeed_strategy',
			'The requested PageSpeed Insights strategy must be mobile or desktop.',
			400
		);
	}

	/**
	 * Build a disabled PageSpeed response.
	 *
	 * @return mixed
	 */
	public function pagespeed_disabled() {
		return $this->runtime->error(
			'pagespeed_disabled',
			'PageSpeed Insights integration is disabled for this site.',
			409
		);
	}

	/**
	 * Build a missing-public-URL PageSpeed response.
	 *
	 * @return mixed
	 */
	public function pagespeed_public_url_unavailable() {
		return $this->runtime->error(
			'pagespeed_public_url_unavailable',
			'This content record does not currently have a safe public URL for PageSpeed Insights.',
			409
		);
	}

	/**
	 * Build a quota-exceeded PageSpeed response.
	 *
	 * @return mixed
	 */
	public function pagespeed_quota_exceeded() {
		return $this->runtime->error(
			'pagespeed_quota_exceeded',
			'The PageSpeed Insights request could not complete because the current quota is exhausted.',
			429
		);
	}

	/**
	 * Build a generic PageSpeed request failure response.
	 *
	 * @return mixed
	 */
	public function pagespeed_request_failed() {
		return $this->runtime->error(
			'pagespeed_request_failed',
			'The PageSpeed Insights request could not be completed right now.',
			503
		);
	}

	/**
	 * Build an attachment-not-found response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed
	 */
	public function attachment_not_found( int $attachment_id ) {
		return $this->runtime->error(
			'attachment_not_found',
			'The requested attachment could not be found.',
			404,
			array(
				'attachment_id' => $attachment_id,
			)
		);
	}

	/**
	 * Build a non-image attachment response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed
	 */
	public function attachment_not_image( int $attachment_id ) {
		return $this->runtime->error(
			'attachment_not_image',
			'The requested attachment is not an image.',
			400,
			array(
				'attachment_id' => $attachment_id,
			)
		);
	}

	/**
	 * Build an excluded-attachment response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed
	 */
	public function attachment_excluded( int $attachment_id ) {
		return $this->runtime->error(
			'attachment_excluded',
			'This attachment is excluded from optimization. Include it before queueing manual work.',
			409,
			array(
				'attachment_id' => $attachment_id,
			)
		);
	}

	/**
	 * Build an invalid retry-state response.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $state Current state.
	 * @return mixed
	 */
	public function attachment_not_retryable( int $attachment_id, string $state ) {
		return $this->runtime->error(
			'attachment_not_retryable',
			'Only failed, partial, or stale attachments can be retried.',
			409,
			array(
				'attachment_id' => $attachment_id,
				'state'         => $state,
			)
		);
	}

	/**
	 * Build a queue-unavailable response.
	 *
	 * @return mixed
	 */
	public function queue_unavailable() {
		return $this->runtime->error(
			'queue_unavailable',
			'The background queue is unavailable right now.',
			503
		);
	}

	/**
	 * Build a statistics-recalculate unavailable response.
	 *
	 * @return mixed
	 */
	public function status_recalculate_unavailable() {
		return $this->runtime->error(
			'status_recalculate_unavailable',
			'The statistics recalculation request could not be queued right now.',
			503
		);
	}

	/**
	 * Build an invalid-force response.
	 *
	 * @return mixed
	 */
	public function invalid_force_flag() {
		return $this->runtime->error(
			'invalid_force_flag',
			'The force flag must be a boolean value.',
			400
		);
	}

	/**
	 * Build an invalid scan-token response.
	 *
	 * @return mixed
	 */
	public function invalid_scan_token() {
		return $this->runtime->error(
			'invalid_scan_token',
			'A valid bulk scan token is required.',
			400
		);
	}

	/**
	 * Build an invalid scan-scope response.
	 *
	 * @return mixed
	 */
	public function invalid_scan_scope() {
		return $this->runtime->error(
			'invalid_scan_scope',
			'The requested bulk scan scope is invalid.',
			400
		);
	}

	/**
	 * Build an invalid target-format response.
	 *
	 * @return mixed
	 */
	public function invalid_target_format() {
		return $this->runtime->error(
			'invalid_target_format',
			'The requested bulk target format is invalid.',
			400
		);
	}

	/**
	 * Build an invalid date-filter response.
	 *
	 * @return mixed
	 */
	public function invalid_date_filter() {
		return $this->runtime->error(
			'invalid_date_filter',
			'Bulk scan dates must use the YYYY-MM-DD format.',
			400
		);
	}

	/**
	 * Build an invalid date-range response.
	 *
	 * @return mixed
	 */
	public function invalid_date_range() {
		return $this->runtime->error(
			'invalid_date_range',
			'The bulk scan date range is invalid.',
			400
		);
	}

	/**
	 * Build an invalid attachment-ID filter response.
	 *
	 * @return mixed
	 */
	public function invalid_attachment_ids() {
		return $this->runtime->error(
			'invalid_attachment_ids',
			'The attachment ID filter must contain positive integers only.',
			400
		);
	}

	/**
	 * Build a missing-session response.
	 *
	 * @return mixed
	 */
	public function bulk_scan_session_not_found() {
		return $this->runtime->error(
			'bulk_scan_session_not_found',
			'The requested bulk scan session could not be found.',
			404
		);
	}

	/**
	 * Build a session-access-denied response.
	 *
	 * @return mixed
	 */
	public function bulk_scan_session_forbidden() {
		return $this->runtime->error(
			'bulk_scan_session_forbidden',
			'You are not allowed to access this bulk scan session.',
			403
		);
	}

	/**
	 * Build an incomplete-session response.
	 *
	 * @return mixed
	 */
	public function bulk_scan_not_complete() {
		return $this->runtime->error(
			'bulk_scan_not_complete',
			'The requested bulk scan must finish before queue controls can run.',
			409
		);
	}

	/**
	 * Build a bulk-scan unavailable response.
	 *
	 * @return mixed
	 */
	public function bulk_scan_unavailable() {
		return $this->runtime->error(
			'bulk_scan_unavailable',
			'The bulk scan request could not be completed right now.',
			503
		);
	}

	/**
	 * Build a bulk queue unavailable response.
	 *
	 * @return mixed
	 */
	public function bulk_queue_unavailable() {
		return $this->runtime->error(
			'bulk_queue_unavailable',
			'The bulk queue request could not be completed right now.',
			503
		);
	}

	/**
	 * Build an unsupported-offload response.
	 *
	 * @param string $message Optional user-safe message.
	 * @return mixed
	 */
	public function offload_unsupported( string $message = 'The current media offload environment is not supported safely for this operation.' ) {
		return $this->runtime->error( 'offload_unsupported', $message, 409 );
	}

	/**
	 * Build an invalid preview-page response.
	 *
	 * @return mixed
	 */
	public function invalid_preview_page() {
		return $this->runtime->error(
			'invalid_preview_page',
			'A valid preview page number is required.',
			400
		);
	}

	/**
	 * Build an invalid preview page-size response.
	 *
	 * @return mixed
	 */
	public function invalid_preview_per_page() {
		return $this->runtime->error(
			'invalid_preview_per_page',
			'A valid preview page size is required.',
			400
		);
	}

	/**
	 * Build a no-enabled-formats response.
	 *
	 * @return mixed
	 */
	public function no_enabled_formats() {
		return $this->runtime->error(
			'no_enabled_formats',
			'No enabled output formats are available for this action.',
			409
		);
	}

	/**
	 * Build a source-unavailable response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed
	 */
	public function attachment_source_unavailable( int $attachment_id ) {
		return $this->runtime->error(
			'attachment_source_unavailable',
			'This attachment does not currently have a valid source fingerprint for queueing.',
			409,
			array(
				'attachment_id' => $attachment_id,
			)
		);
	}

	/**
	 * Build a state-update failure response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return mixed
	 */
	public function attachment_state_update_failed( int $attachment_id ) {
		return $this->runtime->error(
			'attachment_state_update_failed',
			'The attachment state could not be updated safely.',
			500,
			array(
				'attachment_id' => $attachment_id,
			)
		);
	}

	/**
	 * Build a queue-enqueue failure response.
	 *
	 * @param string $message Optional user-safe message.
	 * @return mixed
	 */
	public function queue_enqueue_failed( string $message = 'The requested job could not be queued.' ) {
		return $this->runtime->error( 'queue_enqueue_failed', $message, 500 );
	}

	/**
	 * Build an invalid-log-level response.
	 *
	 * @return mixed
	 */
	public function invalid_log_level() {
		return $this->runtime->error(
			'invalid_log_level',
			'The requested log level filter is invalid.',
			400
		);
	}

	/**
	 * Build an invalid-log-code response.
	 *
	 * @return mixed
	 */
	public function invalid_log_code() {
		return $this->runtime->error(
			'invalid_log_code',
			'The requested log code filter is invalid.',
			400
		);
	}

	/**
	 * Build an invalid-log-attachment response.
	 *
	 * @return mixed
	 */
	public function invalid_log_attachment_id() {
		return $this->runtime->error(
			'invalid_log_attachment_id',
			'The requested log attachment filter must be a positive integer.',
			400
		);
	}

	/**
	 * Build an invalid-log-page response.
	 *
	 * @return mixed
	 */
	public function invalid_log_page() {
		return $this->runtime->error(
			'invalid_log_page',
			'A valid logs page number is required.',
			400
		);
	}

	/**
	 * Build an invalid-log-page-size response.
	 *
	 * @return mixed
	 */
	public function invalid_log_per_page() {
		return $this->runtime->error(
			'invalid_log_per_page',
			'A valid logs page size is required.',
			400
		);
	}

	/**
	 * Build an invalid-retention-days response.
	 *
	 * @return mixed
	 */
	public function invalid_log_retention_days() {
		return $this->runtime->error(
			'invalid_log_retention_days',
			'A valid positive log retention value is required.',
			400
		);
	}

	/**
	 * Build a logs-read unavailable response.
	 *
	 * @return mixed
	 */
	public function logs_unavailable() {
		return $this->runtime->error(
			'logs_unavailable',
			'The requested logs could not be loaded right now.',
			503
		);
	}

	/**
	 * Build a logs-delete unavailable response.
	 *
	 * @return mixed
	 */
	public function logs_delete_unavailable() {
		return $this->runtime->error(
			'logs_delete_unavailable',
			'The plugin logs could not be deleted right now.',
			503
		);
	}

	/**
	 * Build a retention-save unavailable response.
	 *
	 * @return mixed
	 */
	public function logs_retention_unavailable() {
		return $this->runtime->error(
			'logs_retention_unavailable',
			'The log retention setting could not be saved right now.',
			503
		);
	}

	/**
	 * Build a generic unexpected-error response.
	 *
	 * @return mixed
	 */
	public function unexpected() {
		return $this->runtime->error(
			'internal_server_error',
			'An unexpected error occurred while handling this request.',
			500
		);
	}
}
