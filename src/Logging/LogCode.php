<?php
/**
 * Stable log codes.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Defines and validates machine-readable log codes.
 */
final class LogCode {

	public const UNKNOWN                                 = 'unknown';
	public const LOG_WRITE_FAILED                        = 'log_write_failed';
	public const LOG_TABLE_UNAVAILABLE                   = 'log_table_unavailable';
	public const LOG_CONTEXT_TRUNCATED                   = 'log_context_truncated';
	public const LOG_RETENTION_PRUNED                    = 'log_retention_pruned';
	public const LOG_RETENTION_FAILED                    = 'log_retention_failed';
	public const WORKER_INVALID_JOB_PAYLOAD              = 'worker_invalid_job_payload';
	public const WORKER_LOCK_UNAVAILABLE                 = 'worker_lock_unavailable';
	public const WORKER_LOCK_RELEASE_FAILED              = 'worker_lock_release_failed';
	public const WORKER_FINGERPRINT_STALE                = 'worker_fingerprint_stale';
	public const WORKER_FINGERPRINT_STALE_REQUEUED       = 'worker_fingerprint_stale_requeued';
	public const WORKER_CONTINUATION_QUEUED              = 'worker_continuation_queued';
	public const WORKER_CONTINUATION_QUEUE_FAILED        = 'worker_continuation_queue_failed';
	public const WORKER_RETRY_QUEUED                     = 'worker_retry_queued';
	public const WORKER_RETRY_QUEUE_FAILED               = 'worker_retry_queue_failed';
	public const WORKER_RESULT_COMPLETED                 = 'worker_result_completed';
	public const WORKER_RESULT_PARTIAL                   = 'worker_result_partial';
	public const WORKER_RESULT_FAILED                    = 'worker_result_failed';
	public const WORKER_UNEXPECTED_ERROR                 = 'worker_unexpected_error';
	public const NEW_UPLOAD_QUEUED                       = 'new_upload_queued';
	public const NEW_UPLOAD_EXCLUDED                     = 'new_upload_excluded';
	public const NEW_UPLOAD_AUTOMATION_DISABLED          = 'new_upload_automation_disabled';
	public const NEW_UPLOAD_QUEUE_FAILED                 = 'new_upload_queue_failed';
	public const NEW_UPLOAD_IGNORED                      = 'new_upload_ignored';
	public const RECONCILE_STALE_DETECTED                = 'reconcile_stale_detected';
	public const RECONCILE_QUEUED                        = 'reconcile_queued';
	public const RECONCILE_QUEUE_FAILED                  = 'reconcile_queue_failed';
	public const RECONCILE_SKIPPED                       = 'reconcile_skipped';
	public const RECONCILE_COMPLETED                     = 'reconcile_completed';
	public const RECONCILE_CLEANUP_WARNING               = 'reconcile_cleanup_warning';
	public const MAINTENANCE_STALE_LOCKS_RECOVERED       = 'maintenance_stale_locks_recovered';
	public const MAINTENANCE_STALE_LOCK_RECOVERY_FAILED  = 'maintenance_stale_lock_recovery_failed';
	public const MAINTENANCE_STATISTICS_RECONCILED       = 'maintenance_statistics_reconciled';
	public const MAINTENANCE_STATISTICS_RECONCILE_FAILED = 'maintenance_statistics_reconcile_failed';

	/**
	 * Normalize a code into the stable machine-readable shape.
	 *
	 * @param string $code Log code.
	 * @return string
	 */
	public static function normalize( string $code ): string {
		$code = strtolower( trim( $code ) );

		return self::is_valid( $code ) ? $code : self::UNKNOWN;
	}

	/**
	 * Determine whether a code is stable and machine-readable.
	 *
	 * @param string $code Log code.
	 * @return bool
	 */
	public static function is_valid( string $code ): bool {
		return 1 === preg_match( '/^[a-z][a-z0-9_]{0,63}$/', $code );
	}
}
