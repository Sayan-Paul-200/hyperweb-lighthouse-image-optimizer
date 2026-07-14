<?php
/**
 * Bulk scan service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\AttachmentSourceCollectorInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Runs bounded dry-run scan pages and persists resumable session state.
 */
final class BulkScanService {

	public const SCAN_PAGE_SIZE = 100;

	/**
	 * Scanner runtime.
	 *
	 * @var BulkScannerRuntimeInterface
	 */
	private $runtime;

	/**
	 * Session store.
	 *
	 * @var BulkScanSessionStoreInterface
	 */
	private $sessions;

	/**
	 * Lightweight status reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $statuses;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Optional queue-source collector used to keep dry-run candidates queueable.
	 *
	 * @var AttachmentSourceCollectorInterface|null
	 */
	private $source_collector;

	/**
	 * Optional fingerprint builder used to verify the current source can be queued.
	 *
	 * @var AttachmentFingerprintBuilder|null
	 */
	private $fingerprinter;

	/**
	 * GMT clock callback.
	 *
	 * @var callable
	 */
	private $now_gmt;

	/**
	 * Token generator.
	 *
	 * @var callable
	 */
	private $token_generator;

	/**
	 * Create the service.
	 *
	 * @param BulkScannerRuntimeInterface             $runtime Scanner runtime.
	 * @param BulkScanSessionStoreInterface           $sessions Session store.
	 * @param AttachmentStatusReader                  $statuses Lightweight status reader.
	 * @param SettingsRepositoryInterface             $settings Settings repository.
	 * @param callable|null                           $now_gmt Optional GMT clock callback.
	 * @param callable|null                           $token_generator Optional token generator.
	 * @param AttachmentSourceCollectorInterface|null $source_collector Optional source collector for queueability checks.
	 * @param AttachmentFingerprintBuilder|null       $fingerprinter Optional fingerprint builder.
	 */
	public function __construct(
		BulkScannerRuntimeInterface $runtime,
		BulkScanSessionStoreInterface $sessions,
		AttachmentStatusReader $statuses,
		SettingsRepositoryInterface $settings,
		?callable $now_gmt = null,
		?callable $token_generator = null,
		?AttachmentSourceCollectorInterface $source_collector = null,
		?AttachmentFingerprintBuilder $fingerprinter = null
	) {
		$this->runtime          = $runtime;
		$this->sessions         = $sessions;
		$this->statuses         = $statuses;
		$this->settings         = $settings;
		$this->source_collector = $source_collector;
		$this->fingerprinter    = $fingerprinter;
		$this->now_gmt          = $now_gmt ?? static function (): string {
			return gmdate( 'Y-m-d H:i:s' );
		};
		$this->token_generator  = $token_generator ?? static function (): string {
			return bin2hex( random_bytes( 16 ) );
		};
	}

	/**
	 * Start a new dry-run scan and process its first bounded page.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param int             $owner_user_id Owning user ID.
	 * @throws \RuntimeException When the session cannot be created or persisted.
	 * @return BulkScanSession
	 */
	public function start_scan( BulkScanFilters $filters, int $owner_user_id ): BulkScanSession {
		$session = BulkScanSession::start(
			BulkScanSession::normalize_token( (string) call_user_func( $this->token_generator ) ),
			$owner_user_id,
			(string) call_user_func( $this->now_gmt ),
			$filters
		);

		if ( ! $this->sessions->save( $session ) ) {
			throw new \RuntimeException( 'Bulk scan session could not be created.' );
		}

		return $this->process_page( $session );
	}

	/**
	 * Continue an existing owned scan session.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owning user ID.
	 * @return BulkScanSession
	 */
	public function continue_scan( string $token, int $owner_user_id ): BulkScanSession {
		$session = $this->load_owned_session( $token, $owner_user_id );

		if ( $session->progress()->complete() ) {
			return $session;
		}

		return $this->process_page( $session );
	}

	/**
	 * Load one owned session.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owning user ID.
	 * @throws BulkScanSessionNotFoundException When the session does not exist.
	 * @throws BulkScanSessionAccessDeniedException When the current user does not own the session.
	 * @return BulkScanSession
	 */
	public function load_owned_session( string $token, int $owner_user_id ): BulkScanSession {
		$session = $this->sessions->load( $token );

		if ( ! $session instanceof BulkScanSession ) {
			throw new BulkScanSessionNotFoundException( 'Bulk scan session not found.' );
		}

		if ( $session->owner_user_id() !== max( 0, $owner_user_id ) ) {
			throw new BulkScanSessionAccessDeniedException( 'Bulk scan session access denied.' );
		}

		return $session;
	}

	/**
	 * Process one bounded scan page.
	 *
	 * @param BulkScanSession $session Stored session.
	 * @throws \RuntimeException When session completion or persistence fails.
	 * @return BulkScanSession
	 */
	private function process_page( BulkScanSession $session ): BulkScanSession {
		$ids           = $this->runtime->scan_page(
			$session->filters(),
			$session->progress()->last_processed_id(),
			self::SCAN_PAGE_SIZE
		);
		$updated_at    = (string) call_user_func( $this->now_gmt );
		$summary       = $session->summary();
		$progress      = $session->progress();
		$candidate_ids = array();
		$delta         = array(
			'scanned'           => count( $ids ),
			'eligible'          => 0,
			'excluded'          => 0,
			'active'            => 0,
			'already_optimized' => 0,
			'skipped'           => 0,
		);

		if ( array() === $ids ) {
			$session = $session->with_state(
				$progress->with_status( BulkScanProgress::STATUS_COMPLETE ),
				$summary,
				$updated_at
			);

			if ( ! $this->sessions->save( $session ) ) {
				throw new \RuntimeException( 'Bulk scan completion could not be persisted.' );
			}

			return $session;
		}

		$target_formats = $this->target_formats( $session->filters() );

		foreach ( $ids as $attachment_id ) {
			if ( ! $this->runtime->attachment_is_image( $attachment_id ) ) {
				++$delta['skipped'];
				continue;
			}

			$status = $this->statuses->read( $attachment_id );

			if ( $status->excluded() ) {
				++$delta['excluded'];
				continue;
			}

			if ( in_array( $status->state(), array( AttachmentStatus::STATE_QUEUED, AttachmentStatus::STATE_PROCESSING ), true ) ) {
				++$delta['active'];
				continue;
			}

			if ( $this->is_eligible( $status, $session->filters()->scan_scope(), $target_formats ) ) {
				if ( ! $this->attachment_has_queueable_source( $attachment_id ) ) {
					++$delta['skipped'];
					continue;
				}

				$candidate_ids[] = $attachment_id;
				++$delta['eligible'];
				continue;
			}

			if ( $this->all_target_formats_ready( $status, $target_formats ) ) {
				++$delta['already_optimized'];
				continue;
			}

			++$delta['skipped'];
		}

		$summary  = $summary->accumulate( $delta );
		$progress = $progress->with_cursor( max( $ids ) );
		$session  = $session->with_state( $progress, $summary, $updated_at );
		$session  = $this->sessions->append_candidate_ids( $session, $candidate_ids );
		$progress = $session->progress();

		if ( count( $ids ) < self::SCAN_PAGE_SIZE ) {
			$session = $session->with_state(
				$progress->with_status( BulkScanProgress::STATUS_COMPLETE ),
				$summary,
				$updated_at
			);
		}

		if ( ! $this->sessions->save( $session ) ) {
			throw new \RuntimeException( 'Bulk scan session could not be persisted.' );
		}

		return $session;
	}

	/**
	 * Determine whether one status is eligible for the current scan scope.
	 *
	 * @param AttachmentStatus $status Attachment status.
	 * @param string           $scan_scope Scan scope.
	 * @param string[]         $target_formats Target formats.
	 * @return bool
	 */
	private function is_eligible( AttachmentStatus $status, string $scan_scope, array $target_formats ): bool {
		$ready       = array_values( array_intersect( $target_formats, $status->formats_ready() ) );
		$all_ready   = $this->all_target_formats_ready( $status, $target_formats );
		$some_ready  = array() !== $ready;
		$missing_any = array() !== $target_formats && count( $ready ) < count( $target_formats );

		switch ( $scan_scope ) {
			case BulkScanFilters::SCOPE_MISSING_ONLY:
				return $missing_any && $some_ready;

			case BulkScanFilters::SCOPE_FAILED_ONLY:
				return AttachmentStatus::STATE_FAILED === $status->state();

			case BulkScanFilters::SCOPE_STALE_ONLY:
				return AttachmentStatus::STATE_STALE === $status->state();

			case BulkScanFilters::SCOPE_ALL_ELIGIBLE:
			default:
				return ! $all_ready && $missing_any;
		}
	}

	/**
	 * Determine whether all targeted formats are ready.
	 *
	 * @param AttachmentStatus $status Attachment status.
	 * @param string[]         $target_formats Target formats.
	 * @return bool
	 */
	private function all_target_formats_ready( AttachmentStatus $status, array $target_formats ): bool {
		if ( array() === $target_formats ) {
			return false;
		}

		return count( array_intersect( $target_formats, $status->formats_ready() ) ) === count( $target_formats );
	}

	/**
	 * Determine whether an attachment has the source facts required for queueing.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private function attachment_has_queueable_source( int $attachment_id ): bool {
		if ( ! $this->source_collector instanceof AttachmentSourceCollectorInterface ) {
			return true;
		}

		try {
			$collected = $this->source_collector->collect( $attachment_id );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}

		try {
			$collection = $collected->collection();

			if ( array() === $collection->sources() ) {
				return false;
			}

			if ( ! $this->fingerprinter instanceof AttachmentFingerprintBuilder ) {
				return true;
			}

			return null !== $this->fingerprinter->build( $collection );
		} finally {
			$collected->release();
		}
	}

	/**
	 * Resolve the targeted format set for the current scan.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
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
