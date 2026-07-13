<?php
/**
 * Bulk queue service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueResult;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Queues persisted dry-run candidates in bounded chunks.
 */
final class BulkQueueService {

	public const PAGE_SIZE = 50;

	/**
	 * Session store.
	 *
	 * @var BulkScanSessionStoreInterface
	 */
	private $sessions;

	/**
	 * Bulk scan service.
	 *
	 * @var BulkScanService
	 */
	private $scans;

	/**
	 * Lightweight status reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $statuses;

	/**
	 * Attachment queue service.
	 *
	 * @var AttachmentQueueService
	 */
	private $queue;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Queue control state store.
	 *
	 * @var QueueControlStateStoreInterface
	 */
	private $controls;

	/**
	 * GMT clock callback.
	 *
	 * @var callable
	 */
	private $now_gmt;

	/**
	 * Create the service.
	 *
	 * @param BulkScanSessionStoreInterface $sessions Session store.
	 * @param BulkScanService               $scans Bulk scan service.
	 * @param AttachmentStatusReader        $statuses Status reader.
	 * @param AttachmentQueueService        $queue Attachment queue service.
	 * @param SettingsRepositoryInterface   $settings Settings repository.
	 * @param QueueControlStateStoreInterface $controls Queue control state store.
	 * @param callable|null                 $now_gmt Optional GMT clock callback.
	 */
	public function __construct(
		BulkScanSessionStoreInterface $sessions,
		BulkScanService $scans,
		AttachmentStatusReader $statuses,
		AttachmentQueueService $queue,
		SettingsRepositoryInterface $settings,
		QueueControlStateStoreInterface $controls,
		?callable $now_gmt = null
	) {
		$this->sessions = $sessions;
		$this->scans    = $scans;
		$this->statuses = $statuses;
		$this->queue    = $queue;
		$this->settings = $settings;
		$this->controls = $controls;
		$this->now_gmt  = $now_gmt ?? static function (): string {
			return gmdate( 'Y-m-d H:i:s' );
		};
	}

	/**
	 * Continue queueing a completed scan session in normal mode.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owner user ID.
	 * @return BulkScanSession
	 */
	public function queue( string $token, int $owner_user_id ): BulkScanSession {
		return $this->process( $token, $owner_user_id, BulkQueueProgress::MODE_QUEUE );
	}

	/**
	 * Continue queueing a completed scan session in retry mode.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owner user ID.
	 * @return BulkScanSession
	 */
	public function retry( string $token, int $owner_user_id ): BulkScanSession {
		return $this->process( $token, $owner_user_id, BulkQueueProgress::MODE_RETRY );
	}

	/**
	 * Process one bounded queue continuation page.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owner user ID.
	 * @param string $mode Queue mode.
	 * @return BulkScanSession
	 */
	private function process( string $token, int $owner_user_id, string $mode ): BulkScanSession {
		$session = $this->scans->load_owned_session( $token, $owner_user_id );

		if ( ! $session->progress()->complete() ) {
			throw new BulkScanSessionIncompleteException( 'Bulk scan session is not complete.' );
		}

		$updated_at = (string) call_user_func( $this->now_gmt );
		$progress   = $this->prepare_progress( $session->queue_progress(), $mode );
		$summary    = $this->prepare_summary( $session->queue_progress(), $session->queue_summary(), $mode );

		if ( $progress->complete() ) {
			return $session;
		}

		if ( $this->controls->read()->paused() ) {
			$session = $session->with_queue_state(
				$progress->with_status( BulkQueueProgress::STATUS_PAUSED ),
				$summary,
				$updated_at
			);
			$this->sessions->save( $session );

			return $session;
		}

		$ids = $this->sessions->read_candidate_page( $session, $progress->next_page(), self::PAGE_SIZE );

		if ( array() === $ids ) {
			$session = $session->with_queue_state(
				$progress->with_status( BulkQueueProgress::STATUS_COMPLETE ),
				$summary,
				$updated_at
			);
			$this->sessions->save( $session );

			return $session;
		}

		$delta = array(
			'queued'            => 0,
			'already_queued'    => 0,
			'already_optimized' => 0,
			'skipped'           => 0,
			'failed_to_queue'   => 0,
		);

		foreach ( $ids as $attachment_id ) {
			$status = $this->statuses->read( $attachment_id );

			if ( $status->excluded() ) {
				++$delta['skipped'];
				continue;
			}

			if ( in_array( $status->state(), array( AttachmentStatus::STATE_QUEUED, AttachmentStatus::STATE_PROCESSING ), true ) ) {
				++$delta['skipped'];
				continue;
			}

			if ( BulkQueueProgress::MODE_RETRY === $mode && ! in_array( $status->state(), array( AttachmentStatus::STATE_FAILED, AttachmentStatus::STATE_PARTIAL, AttachmentStatus::STATE_STALE ), true ) ) {
				++$delta['skipped'];
				continue;
			}

			$formats = $this->formats_for_status( $session->filters(), $status );

			if ( array() === $formats ) {
				++$delta['already_optimized'];
				continue;
			}

			$result = $this->queue->queue_selected_formats(
				$attachment_id,
				$formats,
				BulkQueueProgress::MODE_RETRY === $mode ? 'bulk_retry' : 'bulk_queue',
				false
			);

			if ( AttachmentQueueResult::CODE_QUEUE_PAUSED === $result->code() ) {
				$session = $session->with_queue_state(
					$progress->with_status( BulkQueueProgress::STATUS_PAUSED ),
					$summary->accumulate( $delta ),
					$updated_at
				);
				$this->sessions->save( $session );

				return $session;
			}

			if ( ! $result->is_successful() ) {
				++$delta['failed_to_queue'];
				continue;
			}

			if ( $result->all_already_queued() ) {
				++$delta['already_queued'];
				continue;
			}

			++$delta['queued'];
		}

		$summary  = $summary->accumulate( $delta );
		$progress = $progress->advance( count( $ids ) );

		$total_pages = (int) ceil( $session->progress()->candidate_total() / self::PAGE_SIZE );
		$status      = $progress->next_page() > max( 1, $total_pages ) ? BulkQueueProgress::STATUS_COMPLETE : BulkQueueProgress::STATUS_RUNNING;
		$session     = $session->with_queue_state( $progress->with_status( $status ), $summary, $updated_at );
		$this->sessions->save( $session );

		return $session;
	}

	/**
	 * Prepare one queue progress snapshot for the requested mode.
	 *
	 * @param BulkQueueProgress $progress Current progress.
	 * @param string            $mode Requested mode.
	 * @return BulkQueueProgress
	 */
	private function prepare_progress( BulkQueueProgress $progress, string $mode ): BulkQueueProgress {
		if ( $progress->mode() !== $mode ) {
			return BulkQueueProgress::reset( $mode );
		}

		if ( BulkQueueProgress::STATUS_IDLE === $progress->status() ) {
			return BulkQueueProgress::reset( $mode );
		}

		if ( BulkQueueProgress::STATUS_PAUSED === $progress->status() ) {
			return $progress->with_status( BulkQueueProgress::STATUS_RUNNING );
		}

		return $progress;
	}

	/**
	 * Prepare one queue summary snapshot for the requested mode.
	 *
	 * @param BulkQueueProgress $progress Current progress.
	 * @param BulkQueueSummary  $summary Current summary.
	 * @param string            $mode Requested mode.
	 * @return BulkQueueSummary
	 */
	private function prepare_summary( BulkQueueProgress $progress, BulkQueueSummary $summary, string $mode ): BulkQueueSummary {
		if ( $progress->mode() !== $mode || BulkQueueProgress::STATUS_IDLE === $progress->status() ) {
			return new BulkQueueSummary();
		}

		return $summary;
	}

	/**
	 * Resolve the selected queue formats for the current attachment state.
	 *
	 * @param BulkScanFilters   $filters Stored scan filters.
	 * @param AttachmentStatus  $status Current status.
	 * @return string[]
	 */
	private function formats_for_status( BulkScanFilters $filters, AttachmentStatus $status ): array {
		$targeted = $this->target_formats( $filters );

		if ( array() === $targeted ) {
			return array();
		}

		if ( in_array( $status->state(), array( AttachmentStatus::STATE_FAILED, AttachmentStatus::STATE_STALE ), true ) ) {
			return $targeted;
		}

		return array_values( array_diff( $targeted, $status->formats_ready() ) );
	}

	/**
	 * Resolve the targeted formats from stored scan filters.
	 *
	 * @param BulkScanFilters $filters Stored filters.
	 * @return string[]
	 */
	private function target_formats( BulkScanFilters $filters ): array {
		if ( BulkScanFilters::TARGET_WEBP === $filters->target_format() ) {
			return array( AttachmentStatus::FORMAT_WEBP );
		}

		if ( BulkScanFilters::TARGET_AVIF === $filters->target_format() ) {
			return array( AttachmentStatus::FORMAT_AVIF );
		}

		return AttachmentStatus::normalize_formats( $this->settings->enabled_formats() );
	}
}
