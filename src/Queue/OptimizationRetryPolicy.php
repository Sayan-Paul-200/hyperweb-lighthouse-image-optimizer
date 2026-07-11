<?php
/**
 * Optimization retry policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;

/**
 * Decides whether optimization work should be retried.
 */
final class OptimizationRetryPolicy {

	/**
	 * Backoff delays in seconds keyed by retry attempt number.
	 *
	 * @var array<int,int>
	 */
	private $backoff_delays;

	/**
	 * Create policy.
	 *
	 * @param array<int,int> $backoff_delays Retry delay map keyed by attempt number.
	 */
	public function __construct(
		array $backoff_delays = array(
			1 => 60,
			2 => 120,
			3 => 240,
		)
	) {
		$this->backoff_delays = $backoff_delays;
	}

	/**
	 * Parse the retry attempt number from a job reason.
	 *
	 * @param string $reason Queueing reason.
	 * @return int
	 */
	public function retry_attempt_from_reason( string $reason ): int {
		$reason = strtolower( trim( $reason ) );

		if ( 1 === preg_match( '/^retry_(\d{1,3})$/', $reason, $matches ) ) {
			return max( 0, (int) $matches[1] );
		}

		return 0;
	}

	/**
	 * Determine whether another retry is allowed.
	 *
	 * @param OptimizationJob $job Job.
	 * @param int             $max_retries Maximum retries.
	 * @return bool
	 */
	public function can_retry( OptimizationJob $job, int $max_retries ): bool {
		return $this->next_retry_attempt( $job ) <= max( 0, $max_retries );
	}

	/**
	 * Get the next retry attempt number.
	 *
	 * @param OptimizationJob $job Job.
	 * @return int
	 */
	public function next_retry_attempt( OptimizationJob $job ): int {
		return $this->retry_attempt_from_reason( $job->reason() ) + 1;
	}

	/**
	 * Get the queue reason for the next retry.
	 *
	 * @param OptimizationJob $job Job.
	 * @return string
	 */
	public function next_retry_reason( OptimizationJob $job ): string {
		return 'retry_' . $this->next_retry_attempt( $job );
	}

	/**
	 * Get the retry delay for a job.
	 *
	 * @param OptimizationJob $job Job.
	 * @return int
	 */
	public function retry_delay_seconds( OptimizationJob $job ): int {
		$attempt  = $this->next_retry_attempt( $job );
		$fallback = end( $this->backoff_delays );

		if ( false === $fallback ) {
			$fallback = 240;
		}

		return $this->backoff_delays[ $attempt ] ?? $fallback;
	}

	/**
	 * Determine whether a lock collision should be retried.
	 *
	 * @param OptimizationJob $job Job.
	 * @param int             $max_retries Maximum retries.
	 * @return bool
	 */
	public function should_retry_lock_collision( OptimizationJob $job, int $max_retries ): bool {
		return $this->can_retry( $job, $max_retries );
	}

	/**
	 * Determine whether a processing result should be retried.
	 *
	 * @param OptimizationJob         $job Job.
	 * @param AttachmentProcessResult $result Processing result.
	 * @param int                     $max_retries Maximum retries.
	 * @return bool
	 */
	public function should_retry_result( OptimizationJob $job, AttachmentProcessResult $result, int $max_retries ): bool {
		if ( ! $this->can_retry( $job, $max_retries ) ) {
			return false;
		}

		if ( $result->is_locked() ) {
			return true;
		}

		$results = $result->results();

		if ( ! $results instanceof ConversionResultCollection ) {
			return false;
		}

		$failed = $results->failed();

		if ( array() === $failed ) {
			return false;
		}

		foreach ( $failed as $failure ) {
			if ( ! $this->is_retryable_conversion_result( $failure ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine whether a conversion result is retryable.
	 *
	 * @param ConversionResult $result Conversion result.
	 * @return bool
	 */
	public function is_retryable_conversion_result( ConversionResult $result ): bool {
		return in_array(
			$result->code(),
			array(
				ConversionResultCode::TEMPORARY_WRITE_FAILED,
			),
			true
		);
	}
}
