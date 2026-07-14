<?php
/**
 * CLI stale reconciliation runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScannerRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationResult;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;

/**
 * Queues stale attachments for reconciliation in bounded attachment pages.
 */
final class CliReconcileStaleService {

	/**
	 * Bulk scanner runtime.
	 *
	 * @var BulkScannerRuntimeInterface
	 */
	private $runtime;

	/**
	 * Status reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $statuses;

	/**
	 * Shared reconciliation queue service.
	 *
	 * @var AttachmentReconciliationService
	 */
	private $reconciliation;

	/**
	 * Create the service.
	 *
	 * @param BulkScannerRuntimeInterface     $runtime Scanner runtime.
	 * @param AttachmentStatusReader          $statuses Status reader.
	 * @param AttachmentReconciliationService $reconciliation Reconciliation queue service.
	 */
	public function __construct(
		BulkScannerRuntimeInterface $runtime,
		AttachmentStatusReader $statuses,
		AttachmentReconciliationService $reconciliation
	) {
		$this->runtime         = $runtime;
		$this->statuses        = $statuses;
		$this->reconciliation  = $reconciliation;
	}

	/**
	 * Queue stale attachments for reconciliation.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function reconcile( BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		$cursor   = 0;
		$pages    = 0;
		$summary  = array(
			'scanned'         => 0,
			'stale_candidates'=> 0,
			'queued'          => 0,
			'already_queued'  => 0,
			'skipped'         => 0,
			'failed_to_queue' => 0,
		);
		$codes    = array();
		$messages = array();

		while ( true ) {
			$ids = $this->runtime->scan_page( $filters, $cursor, BulkScanService::SCAN_PAGE_SIZE );

			if ( array() === $ids ) {
				break;
			}

			++$pages;
			$cursor            = max( $ids );
			$summary['scanned'] += count( $ids );

			foreach ( $ids as $attachment_id ) {
				if ( ! $this->runtime->attachment_is_image( $attachment_id ) ) {
					++$summary['skipped'];
					continue;
				}

				$status = $this->statuses->read( $attachment_id );

				if (
					$status->excluded()
					|| in_array( $status->state(), array( AttachmentStatus::STATE_QUEUED, AttachmentStatus::STATE_PROCESSING ), true )
					|| AttachmentStatus::STATE_STALE !== $status->state()
				) {
					++$summary['skipped'];
					continue;
				}

				++$summary['stale_candidates'];
				$result = $this->reconciliation->reconcile( $attachment_id, 'cli_reconcile_stale' );

				if ( $result->is_successful() ) {
					if ( AttachmentReconciliationResult::CODE_ALREADY_QUEUED === $result->code() ) {
						++$summary['already_queued'];
					} else {
						++$summary['queued'];
					}

					continue;
				}

				++$summary['failed_to_queue'];
				$codes[]    = $result->code();
				$messages[] = $result->message();

				if ( in_array( $result->code(), array( AttachmentReconciliationResult::CODE_QUEUE_PAUSED, AttachmentReconciliationResult::CODE_QUEUE_UNAVAILABLE, AttachmentReconciliationResult::CODE_OFFLOAD_UNSUPPORTED ), true ) ) {
					if ( null !== $progress ) {
						call_user_func( $progress, $this->progress_line( $summary, $pages, 'blocked' ) );
					}

					return CliOperationResult::degraded(
						'reconcile-stale',
						array(
							'filters'  => $filters->to_array(),
							'progress' => array(
								'pages'     => $pages,
								'cursor'    => $cursor,
								'complete'  => false,
								'status'    => 'blocked',
							),
							'summary'  => $summary,
						),
						$codes,
						$messages
					);
				}
			}

			if ( null !== $progress ) {
				call_user_func( $progress, $this->progress_line( $summary, $pages, 'running' ) );
			}
		}

		$result = array(
			'filters'  => $filters->to_array(),
			'progress' => array(
				'pages'    => $pages,
				'cursor'   => $cursor,
				'complete' => true,
				'status'   => 'complete',
			),
			'summary'  => $summary,
		);

		return 0 < $summary['failed_to_queue']
			? CliOperationResult::degraded( 'reconcile-stale', $result, $codes, $messages )
			: CliOperationResult::success( 'reconcile-stale', $result );
	}

	/**
	 * Build one progress line.
	 *
	 * @param array<string,int> $summary Summary counts.
	 * @param int               $pages Processed pages.
	 * @param string            $status Status label.
	 * @return string
	 */
	private function progress_line( array $summary, int $pages, string $status ): string {
		return sprintf(
			'Reconcile progress: pages=%d scanned=%d stale=%d queued=%d already_queued=%d skipped=%d failed_to_queue=%d status=%s',
			$pages,
			$summary['scanned'],
			$summary['stale_candidates'],
			$summary['queued'],
			$summary['already_queued'],
			$summary['skipped'],
			$summary['failed_to_queue'],
			$status
		);
	}
}
