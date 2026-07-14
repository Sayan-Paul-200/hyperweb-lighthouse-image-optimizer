<?php
/**
 * CLI bulk operation orchestration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueProgress;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadUnsupportedException;

/**
 * Runs scan, queue, and retry-failure CLI flows using internal bulk sessions.
 */
final class CliBulkOperationsService {

	private const OWNER_USER_ID = 0;

	/**
	 * Scan service.
	 *
	 * @var BulkScanService
	 */
	private $scans;

	/**
	 * Queue continuation service.
	 *
	 * @var BulkQueueService
	 */
	private $queues;

	/**
	 * Session store.
	 *
	 * @var BulkScanSessionStoreInterface
	 */
	private $sessions;

	/**
	 * Create the service.
	 *
	 * @param BulkScanService               $scans Scan service.
	 * @param BulkQueueService              $queues Queue continuation service.
	 * @param BulkScanSessionStoreInterface $sessions Session store.
	 */
	public function __construct( BulkScanService $scans, BulkQueueService $queues, BulkScanSessionStoreInterface $sessions ) {
		$this->scans    = $scans;
		$this->queues   = $queues;
		$this->sessions = $sessions;
	}

	/**
	 * Run a dry-run scan to completion.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function scan( BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		$session = null;

		try {
			$session = $this->scan_to_completion( $filters, $progress );

			return CliOperationResult::success(
				'scan',
				array(
					'filters'  => $filters->to_array(),
					'progress' => $session->progress()->to_array(),
					'summary'  => $session->summary()->to_array(),
				)
			);
		} finally {
			$this->delete_session( $session );
		}
	}

	/**
	 * Run scan and queue flows to completion.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function queue( BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		return $this->queue_mode( 'queue', $filters, $progress );
	}

	/**
	 * Run failed-only retry queueing to completion.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function retry_failures( BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		return $this->queue_mode( 'retry-failures', $filters, $progress );
	}

	/**
	 * Run scan plus queue continuation for one mode.
	 *
	 * @param string          $operation Operation name.
	 * @param BulkScanFilters $filters Filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	private function queue_mode( string $operation, BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		$session = null;

		try {
			$session = $this->scan_to_completion( $filters, $progress );

			try {
				$session = $this->queue_to_completion( $session, 'retry-failures' === $operation, $progress );
			} catch ( OffloadUnsupportedException $error ) {
				return CliOperationResult::degraded(
					$operation,
					array(
						'filters'        => $filters->to_array(),
						'scan_progress'  => $session->progress()->to_array(),
						'scan_summary'   => $session->summary()->to_array(),
						'queue_progress' => $session->queue_progress()->to_array(),
						'queue_summary'  => $session->queue_summary()->to_array(),
					),
					array( 'offload_unsupported' ),
					array( $error->getMessage() )
				);
			}

			$payload  = array(
				'filters'        => $filters->to_array(),
				'scan_progress'  => $session->progress()->to_array(),
				'scan_summary'   => $session->summary()->to_array(),
				'queue_progress' => $session->queue_progress()->to_array(),
				'queue_summary'  => $session->queue_summary()->to_array(),
			);
			$codes    = array();
			$messages = array();
			$degraded = false;

			if ( BulkQueueProgress::STATUS_PAUSED === $session->queue_progress()->status() ) {
				$degraded   = true;
				$codes[]    = 'queue_paused';
				$messages[] = 'Attachment processing is currently paused.';
			}

			$queue_summary = $session->queue_summary()->to_array();

			if ( isset( $queue_summary['failed_to_queue'] ) && 0 < (int) $queue_summary['failed_to_queue'] ) {
				$degraded   = true;
				$codes[]    = 'failed_to_queue';
				$messages[] = 'One or more attachments could not be queued.';
			}

			return $degraded
				? CliOperationResult::degraded( $operation, $payload, $codes, $messages )
				: CliOperationResult::success( $operation, $payload );
		} finally {
			$this->delete_session( $session );
		}
	}

	/**
	 * Run the scan stage to completion.
	 *
	 * @param BulkScanFilters $filters Filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return BulkScanSession
	 */
	private function scan_to_completion( BulkScanFilters $filters, ?callable $progress = null ): BulkScanSession {
		$session = $this->scans->start_scan( $filters, self::OWNER_USER_ID );
		$this->emit_scan_progress( $session, $progress );

		while ( ! $session->progress()->complete() ) {
			$session = $this->scans->continue_scan( $session->token(), self::OWNER_USER_ID );
			$this->emit_scan_progress( $session, $progress );
		}

		return $session;
	}

	/**
	 * Run the queue stage to completion.
	 *
	 * @param BulkScanSession $session Stored session.
	 * @param bool            $retry_mode Whether retry mode should run.
	 * @param callable|null   $progress Optional progress callback.
	 * @return BulkScanSession
	 */
	private function queue_to_completion( BulkScanSession $session, bool $retry_mode, ?callable $progress = null ): BulkScanSession {
		$session = $retry_mode
			? $this->queues->retry( $session->token(), self::OWNER_USER_ID )
			: $this->queues->queue( $session->token(), self::OWNER_USER_ID );
		$this->emit_queue_progress( $session, $progress, $retry_mode );

		while (
			! $session->queue_progress()->complete()
			&& BulkQueueProgress::STATUS_PAUSED !== $session->queue_progress()->status()
		) {
			$session = $retry_mode
				? $this->queues->retry( $session->token(), self::OWNER_USER_ID )
				: $this->queues->queue( $session->token(), self::OWNER_USER_ID );
			$this->emit_queue_progress( $session, $progress, $retry_mode );
		}

		return $session;
	}

	/**
	 * Emit one scan progress line.
	 *
	 * @param BulkScanSession $session Session.
	 * @param callable|null   $progress Callback.
	 * @return void
	 */
	private function emit_scan_progress( BulkScanSession $session, ?callable $progress = null ): void {
		if ( null === $progress ) {
			return;
		}

		$summary = $session->summary()->to_array();

		call_user_func(
			$progress,
			sprintf(
				'Scan progress: scanned=%d eligible=%d excluded=%d active=%d already_optimized=%d skipped=%d candidates=%d status=%s',
				(int) $summary['scanned'],
				(int) $summary['eligible'],
				(int) $summary['excluded'],
				(int) $summary['active'],
				(int) $summary['already_optimized'],
				(int) $summary['skipped'],
				$session->progress()->candidate_total(),
				$session->progress()->status()
			)
		);
	}

	/**
	 * Emit one queue progress line.
	 *
	 * @param BulkScanSession $session Session.
	 * @param callable|null   $progress Callback.
	 * @param bool            $retry_mode Whether retry mode is running.
	 * @return void
	 */
	private function emit_queue_progress( BulkScanSession $session, ?callable $progress = null, bool $retry_mode = false ): void {
		if ( null === $progress ) {
			return;
		}

		$summary = $session->queue_summary()->to_array();

		call_user_func(
			$progress,
			sprintf(
				'%s progress: processed=%d queued=%d already_queued=%d already_optimized=%d skipped=%d failed_to_queue=%d status=%s',
				$retry_mode ? 'Retry queue' : 'Queue',
				$session->queue_progress()->processed_candidates(),
				(int) $summary['queued'],
				(int) $summary['already_queued'],
				(int) $summary['already_optimized'],
				(int) $summary['skipped'],
				(int) $summary['failed_to_queue'],
				$session->queue_progress()->status()
			)
		);
	}

	/**
	 * Delete one internal session when present.
	 *
	 * @param BulkScanSession|null $session Session.
	 * @return void
	 */
	private function delete_session( ?BulkScanSession $session ): void {
		if ( $session instanceof BulkScanSession ) {
			$this->sessions->delete( $session->token() );
		}
	}
}
