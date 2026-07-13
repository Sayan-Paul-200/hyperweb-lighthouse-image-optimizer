<?php
/**
 * Reconciliation worker.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessorInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationDispatcherInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\SingleActionSchedulerInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressCacheInvalidationDispatcher;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressFilesystem;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Logging\Logger;
use HyperWeb\LighthouseImageOptimizer\Logging\LoggerInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Rebuilds stale derivatives after attachment metadata changes.
 */
final class ReconciliationWorker implements HookProviderInterface {

	private const PAUSE_DELAY_SECONDS = 60;

	/**
	 * Queue adapter.
	 *
	 * @var QueueInterface
	 */
	private $queue;

	/**
	 * Lock manager.
	 *
	 * @var AttachmentLockManager
	 */
	private $locks;

	/**
	 * Source collector.
	 *
	 * @var SourceCollector
	 */
	private $collector;

	/**
	 * Fingerprint builder.
	 *
	 * @var AttachmentFingerprintBuilder
	 */
	private $fingerprinter;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Attachment processor.
	 *
	 * @var AttachmentProcessorInterface
	 */
	private $processor;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Derivative file cleaner.
	 *
	 * @var DerivativeFileCleaner
	 */
	private $files;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Queue control state store.
	 *
	 * @var QueueControlStateStoreInterface|null
	 */
	private $controls;

	/**
	 * Single action scheduler.
	 *
	 * @var SingleActionSchedulerInterface|null
	 */
	private $single_actions;

	/**
	 * Cache invalidation dispatcher.
	 *
	 * @var CacheInvalidationDispatcherInterface|null
	 */
	private $cache_invalidation;

	/**
	 * Build the WordPress-backed worker.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			ActionSchedulerQueue::for_wordpress(),
			AttachmentLockManager::for_wordpress(),
			SourceCollector::for_wordpress(),
			new AttachmentFingerprintBuilder(),
			DerivativeRepository::for_wordpress(),
			AttachmentProcessor::for_wordpress(),
			SettingsRepository::for_wordpress(),
			new DerivativeFileCleaner( self::uploads_base_dir(), new WordPressFilesystem() ),
			Logger::for_wordpress(),
			new SystemAttachmentClock(),
			QueueControlStateStore::for_wordpress(),
			new ActionSchedulerSingleActionScheduler(),
			new WordPressCacheInvalidationDispatcher()
		);
	}

	/**
	 * Create worker.
	 *
	 * @param QueueInterface                            $queue Queue adapter.
	 * @param AttachmentLockManager                     $locks Lock manager.
	 * @param SourceCollector                           $collector Source collector.
	 * @param AttachmentFingerprintBuilder              $fingerprinter Fingerprint builder.
	 * @param DerivativeRepository                      $repository Derivative repository.
	 * @param AttachmentProcessorInterface              $processor Attachment processor.
	 * @param SettingsRepositoryInterface               $settings Settings repository.
	 * @param DerivativeFileCleaner                     $files Derivative file cleaner.
	 * @param LoggerInterface                           $logger Logger.
	 * @param AttachmentClockInterface                  $clock Clock.
	 * @param QueueControlStateStoreInterface|null      $controls Queue control state store.
	 * @param SingleActionSchedulerInterface|null       $single_actions Single action scheduler.
	 * @param CacheInvalidationDispatcherInterface|null $cache_invalidation Cache invalidation dispatcher.
	 */
	public function __construct(
		QueueInterface $queue,
		AttachmentLockManager $locks,
		SourceCollector $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		DerivativeRepository $repository,
		AttachmentProcessorInterface $processor,
		SettingsRepositoryInterface $settings,
		DerivativeFileCleaner $files,
		LoggerInterface $logger,
		AttachmentClockInterface $clock,
		?QueueControlStateStoreInterface $controls = null,
		?SingleActionSchedulerInterface $single_actions = null,
		?CacheInvalidationDispatcherInterface $cache_invalidation = null
	) {
		$this->queue              = $queue;
		$this->locks              = $locks;
		$this->collector          = $collector;
		$this->fingerprinter      = $fingerprinter;
		$this->repository         = $repository;
		$this->processor          = $processor;
		$this->settings           = $settings;
		$this->files              = $files;
		$this->logger             = $logger;
		$this->clock              = $clock;
		$this->controls           = $controls;
		$this->single_actions     = $single_actions;
		$this->cache_invalidation = $cache_invalidation;
	}

	/**
	 * Register the reconciliation hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action(
			LifecyclePolicy::ACTION_RECONCILE_ATTACHMENT,
			array( $this, 'handle_reconciliation_job' ),
			10,
			3
		);
	}

	/**
	 * Execute a reconciliation callback payload.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @param mixed $fingerprint Fingerprint signature.
	 * @param mixed $reason Queueing reason.
	 * @return void
	 */
	public function handle_reconciliation_job( $attachment_id, $fingerprint, $reason ): void {
		$job = ReconciliationJob::from_callback_args( $attachment_id, $fingerprint, $reason );

		if ( ! $job instanceof ReconciliationJob ) {
			$valid_attachment_id = is_numeric( $attachment_id ) ? max( 0, (int) $attachment_id ) : 0;

			if ( 0 < $valid_attachment_id ) {
				$this->save_status(
					$valid_attachment_id,
					AttachmentStatus::STATE_FAILED,
					'invalid_job_payload',
					$this->current_status( $valid_attachment_id )
				);
			}

			$this->logger->error(
				LogCode::WORKER_INVALID_JOB_PAYLOAD,
				'Reconciliation worker received an invalid job payload.',
				array(
					'attachment_id' => $valid_attachment_id,
					'reason'        => is_scalar( $reason ) ? (string) $reason : null,
				),
				0 < $valid_attachment_id ? $valid_attachment_id : null
			);

			return;
		}

		$this->run_job( $job );
	}

	/**
	 * Run one reconciliation job.
	 *
	 * @param ReconciliationJob $job Job.
	 * @return void
	 */
	public function run_job( ReconciliationJob $job ): void {
		$attachment_id  = $job->attachment_id();
		$current_status = $this->current_status( $attachment_id );
		$job_id         = $this->job_id( $job );

		if ( $this->paused() ) {
			$this->requeue_paused_job( $job, $job_id );
			return;
		}

		$acquired = $this->locks->acquire( $attachment_id );

		if ( ! $acquired->is_successful() ) {
			$this->save_status( $attachment_id, AttachmentStatus::STATE_STALE, 'lock_unavailable', $current_status );

			$this->logger->warning(
				LogCode::WORKER_LOCK_UNAVAILABLE,
				'Reconciliation worker could not acquire the attachment lock.',
				array(
					'attachment_id' => $attachment_id,
					'reason'        => $job->reason(),
					'messages'      => $acquired->messages(),
				),
				$attachment_id,
				$job_id
			);

			return;
		}

		$lock = $acquired->lock();

		try {
			$collection  = $this->collector->collect( $attachment_id );
			$comparison  = $this->fingerprinter->compare_signature( $job->fingerprint(), $collection );
			$fingerprint = $comparison->current_fingerprint();

			if ( $comparison->is_stale() && $fingerprint instanceof AttachmentFingerprint ) {
				$queue_status = $this->queue->enqueue_reconciliation(
					new ReconciliationJob( $attachment_id, $fingerprint->signature(), 'source_changed' )
				);

				if ( $queue_status->is_successful() ) {
					$this->save_status( $attachment_id, AttachmentStatus::STATE_STALE, null, $current_status );

					$this->logger->info(
						LogCode::RECONCILE_QUEUED,
						'Reconciliation worker re-queued stale attachment reconciliation with a fresh fingerprint.',
						array(
							'attachment_id'   => $attachment_id,
							'comparison_code' => $comparison->code(),
							'queue_codes'     => $queue_status->codes(),
						),
						$attachment_id,
						$job_id
					);
				} else {
					$this->save_status( $attachment_id, AttachmentStatus::STATE_STALE, $this->primary_code( $queue_status ), $current_status );

					$this->logger->warning(
						LogCode::RECONCILE_QUEUE_FAILED,
						'Reconciliation worker could not re-queue stale attachment reconciliation.',
						array(
							'attachment_id'   => $attachment_id,
							'comparison_code' => $comparison->code(),
							'queue_codes'     => $queue_status->codes(),
						),
						$attachment_id,
						$job_id
					);
				}

				return;
			}

			if ( ! $comparison->is_match() || ! $fingerprint instanceof AttachmentFingerprint ) {
				$this->save_status( $attachment_id, AttachmentStatus::STATE_FAILED, $comparison->code(), $current_status );

				$this->logger->warning(
					LogCode::RECONCILE_SKIPPED,
					'Reconciliation worker could not build a trustworthy current fingerprint.',
					array(
						'attachment_id'   => $attachment_id,
						'comparison_code' => $comparison->code(),
						'status'          => $comparison->status(),
					),
					$attachment_id,
					$job_id
				);

				return;
			}

			$read     = $this->repository->read( $attachment_id );
			$manifest = $read->manifest();

			if ( ! $manifest->has_derivatives() ) {
				$this->logger->info(
					LogCode::RECONCILE_SKIPPED,
					'Reconciliation worker found no stored derivatives to reconcile.',
					array(
						'attachment_id' => $attachment_id,
					),
					$attachment_id,
					$job_id
				);

				return;
			}

			$stored = $manifest->fingerprint();
			if ( $stored instanceof AttachmentFingerprint && $stored->signature() === $fingerprint->signature() ) {
				$this->logger->info(
					LogCode::RECONCILE_SKIPPED,
					'Reconciliation worker found the manifest already current for this attachment.',
					array(
						'attachment_id' => $attachment_id,
					),
					$attachment_id,
					$job_id
				);

				return;
			}

			$old_derivative_files = DerivativeFileCleaner::derivative_files_from_manifest( $manifest );
			$source_files         = DerivativeFileCleaner::source_files_from_manifest( $manifest );
			$source_files         = array_merge( $source_files, $this->source_files_from_collection( $collection ) );

			$begin = $this->repository->begin_reconciliation( $attachment_id, $fingerprint );
			if ( ! $begin->is_successful() ) {
				$this->save_status( $attachment_id, AttachmentStatus::STATE_FAILED, 'metadata_write_failed', $current_status );

				$this->logger->error(
					LogCode::RECONCILE_QUEUE_FAILED,
					'Reconciliation worker could not reset the active derivative manifest safely.',
					array(
						'attachment_id' => $attachment_id,
						'codes'         => $begin->codes(),
					),
					$attachment_id,
					$job_id
				);

				return;
			}

			$results = array();
			foreach ( $this->settings->enabled_formats() as $format ) {
				$results[] = $this->processor->process_request(
					new AttachmentProcessRequest(
						$attachment_id,
						$format,
						0,
						0,
						true,
						$collection,
						$fingerprint
					)
				);
			}

			$final_read               = $this->repository->read( $attachment_id );
			$current_manifest         = $final_read->manifest();
			$current_derivative_files = DerivativeFileCleaner::derivative_files_from_manifest( $current_manifest );
			$obsolete_derivatives     = array_values( array_diff( $old_derivative_files, $current_derivative_files ) );
			$cleanup_warning          = false;

			if ( array() !== $obsolete_derivatives ) {
				$cleanup         = $this->files->cleanup_files( $source_files, $obsolete_derivatives );
				$cleanup_warning = AttachmentCleanupResult::SEVERITY_FAILURE === $cleanup->severity()
					|| AttachmentCleanupResult::SEVERITY_WARNING === $cleanup->severity();
				$this->dispatch_derivatives_deleted( $attachment_id, $cleanup, 'reconciliation_obsolete_derivatives' );
				if ( $cleanup_warning ) {
					$this->logger->warning(
						LogCode::RECONCILE_CLEANUP_WARNING,
						'Reconciliation worker left obsolete derivative files behind after replacing manifest state.',
						array(
							'attachment_id' => $attachment_id,
							'codes'         => $cleanup->codes(),
							'samples'       => $cleanup->deleted_file_samples(),
						),
						$attachment_id,
						$job_id
					);
				}
			}

			$final_state  = $this->determine_final_state( $results, $current_manifest, $cleanup_warning );
			$error_code   = $this->aggregate_error_code( $results );
			$final_status = new AttachmentStatus(
				$final_state,
				$current_manifest->ready_formats(),
				$this->clock->now(),
				$error_code,
				$current_status->excluded()
			);

			$this->repository->save_status( $attachment_id, $final_status );

			$this->logger->info(
				LogCode::RECONCILE_COMPLETED,
				'Reconciliation worker completed attachment reconciliation.',
				array(
					'attachment_id' => $attachment_id,
					'state'         => $final_state,
					'formats'       => $current_manifest->ready_formats(),
					'result_codes'  => $this->aggregate_result_codes( $results ),
				),
				$attachment_id,
				$job_id
			);
		} catch ( \Throwable $throwable ) {
			$this->save_status( $attachment_id, AttachmentStatus::STATE_FAILED, AttachmentProcessResult::CODE_UNEXPECTED_ERROR, $current_status );

			$this->logger->error(
				LogCode::WORKER_UNEXPECTED_ERROR,
				'Reconciliation worker encountered an unexpected error.',
				array(
					'attachment_id' => $attachment_id,
					'reason'        => $job->reason(),
					'message'       => $throwable->getMessage(),
				),
				$attachment_id,
				$job_id
			);
		} finally {
			if ( null !== $lock ) {
				$release = $this->locks->release( $attachment_id, $lock->token() );

				if ( ! $release->is_successful() ) {
					$this->logger->warning(
						LogCode::WORKER_LOCK_RELEASE_FAILED,
						'Reconciliation worker could not release the attachment lock cleanly.',
						array(
							'attachment_id' => $attachment_id,
							'codes'         => $release->codes(),
						),
						$attachment_id,
						$job_id
					);
				}
			}
		}
	}

	/**
	 * Determine final attachment state after reconciliation.
	 *
	 * @param AttachmentProcessResult[]                                        $results Process results.
	 * @param \HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest $manifest Current manifest.
	 * @param bool                                                             $cleanup_warning Whether cleanup warned.
	 * @return string
	 */
	private function determine_final_state( array $results, \HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest $manifest, bool $cleanup_warning ): string {
		if ( array() === $results ) {
			return AttachmentStatus::STATE_SKIPPED;
		}

		$has_failures = false;
		$has_partial  = false;
		$has_success  = false;
		$has_skips    = false;

		foreach ( $results as $result ) {
			if ( ! $result instanceof AttachmentProcessResult ) {
				continue;
			}

			if ( ! $result->is_successful() ) {
				$has_failures = true;
				continue;
			}

			if ( ! $result->is_complete() ) {
				$has_partial = true;
			}

			if ( $result->results() instanceof \HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection ) {
				$has_success  = array() !== $result->results()->successful() || $has_success;
				$has_failures = array() !== $result->results()->failed() || $has_failures;
				$has_skips    = array() !== $result->results()->skipped() || $has_skips;
			} else {
				$has_skips = true;
			}
		}

		if ( $cleanup_warning ) {
			$has_partial = true;
		}

		if ( ! $manifest->has_derivatives() && ! $has_success ) {
			return $has_failures ? AttachmentStatus::STATE_FAILED : AttachmentStatus::STATE_SKIPPED;
		}

		if ( $has_failures || $has_partial || $has_skips ) {
			return AttachmentStatus::STATE_PARTIAL;
		}

		return AttachmentStatus::STATE_OPTIMIZED;
	}

	/**
	 * Aggregate the first relevant error code from process results.
	 *
	 * @param AttachmentProcessResult[] $results Process results.
	 * @return string|null
	 */
	private function aggregate_error_code( array $results ): ?string {
		foreach ( $results as $result ) {
			if ( ! $result instanceof AttachmentProcessResult ) {
				continue;
			}

			if ( $result->results() instanceof \HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection ) {
				foreach ( $result->results()->failed() as $failed ) {
					if ( $failed instanceof ConversionResult ) {
						return $failed->code();
					}
				}
			}

			$codes = $result->codes();
			if ( isset( $codes[0] ) ) {
				return $codes[0];
			}
		}

		return null;
	}

	/**
	 * Aggregate result codes for logging.
	 *
	 * @param AttachmentProcessResult[] $results Process results.
	 * @return array<string,string[]>
	 */
	private function aggregate_result_codes( array $results ): array {
		$codes = array();

		foreach ( $results as $result ) {
			if ( ! $result instanceof AttachmentProcessResult || null === $result->target_format() ) {
				continue;
			}

			$codes[ $result->target_format() ] = $result->codes();
		}

		return $codes;
	}

	/**
	 * Map collected sources into a preserve list for cleanup.
	 *
	 * @param SourceImageCollection $collection Source collection.
	 * @return array<string,bool>
	 */
	private function source_files_from_collection( SourceImageCollection $collection ): array {
		$files = array();

		foreach ( $collection->sources() as $source ) {
			$files[ $source->relative_path() ] = true;
		}

		return $files;
	}

	/**
	 * Save a lightweight attachment status while preserving ready formats and exclusion.
	 *
	 * @param int              $attachment_id Attachment ID.
	 * @param string           $state State.
	 * @param string|null      $error_code Error code.
	 * @param AttachmentStatus $current_status Current status.
	 * @return void
	 */
	private function save_status( int $attachment_id, string $state, ?string $error_code, AttachmentStatus $current_status ): void {
		$this->repository->save_status(
			$attachment_id,
			new AttachmentStatus(
				$state,
				$current_status->formats_ready(),
				$this->clock->now(),
				$error_code,
				$current_status->excluded()
			)
		);
	}

	/**
	 * Read current attachment status.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentStatus
	 */
	private function current_status( int $attachment_id ): AttachmentStatus {
		return $this->repository->read( $attachment_id )->status();
	}

	/**
	 * Read the first machine-readable queue code.
	 *
	 * @param QueueStatus $status Queue status.
	 * @return string|null
	 */
	private function primary_code( QueueStatus $status ): ?string {
		$codes = $status->codes();

		return $codes[0] ?? null;
	}

	/**
	 * Build a safe queue job identifier for logs.
	 *
	 * @param ReconciliationJob $job Job.
	 * @return string
	 */
	private function job_id( ReconciliationJob $job ): string {
		return substr(
			sprintf(
				'%d:%s:%s',
				$job->attachment_id(),
				$job->fingerprint(),
				$job->reason()
			),
			0,
			191
		);
	}

	/**
	 * Resolve the uploads base directory.
	 *
	 * @return string
	 */
	private static function uploads_base_dir(): string {
		if ( function_exists( 'wp_get_upload_dir' ) ) {
			$uploads = wp_get_upload_dir();
			if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) && is_string( $uploads['basedir'] ) ) {
				return $uploads['basedir'];
			}
		}

		return '';
	}

	/**
	 * Dispatch cache invalidation for deleted obsolete derivative files.
	 *
	 * @param int                     $attachment_id Attachment ID.
	 * @param AttachmentCleanupResult $cleanup Cleanup result.
	 * @param string                  $reason Reason.
	 * @return void
	 */
	private function dispatch_derivatives_deleted( int $attachment_id, AttachmentCleanupResult $cleanup, string $reason ): void {
		if (
			! $this->cache_invalidation instanceof CacheInvalidationDispatcherInterface
			|| 0 >= $cleanup->deleted_files()
			|| array() === $cleanup->deleted_relative_paths()
		) {
			return;
		}

		$this->cache_invalidation->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_DELETED,
				$attachment_id,
				$reason,
				$cleanup->deleted_relative_paths(),
				$this->formats_from_paths( $cleanup->deleted_relative_paths() ),
				gmdate( 'Y-m-d H:i:s', $this->clock->now() )
			)
		);
	}

	/**
	 * Extract known derivative formats from relative paths.
	 *
	 * @param string[] $paths Relative paths.
	 * @return string[]
	 */
	private function formats_from_paths( array $paths ): array {
		$formats = array();

		foreach ( $paths as $path ) {
			if ( 1 === preg_match( '/\.hwlio\.(webp|avif)$/', (string) $path, $matches ) ) {
				$formats[] = $matches[1];
			}
		}

		return array_values( array_unique( $formats ) );
	}

	/**
	 * Determine whether queue execution is paused globally.
	 *
	 * @return bool
	 */
	private function paused(): bool {
		return null !== $this->controls && $this->controls->read()->paused();
	}

	/**
	 * Requeue one paused reconciliation job without consuming work.
	 *
	 * @param ReconciliationJob $job Job.
	 * @param string            $job_id Job identifier.
	 * @return void
	 */
	private function requeue_paused_job( ReconciliationJob $job, string $job_id ): void {
		$scheduled = null !== $this->single_actions
			&& $this->single_actions->schedule_single_action(
				$this->clock->now() + self::PAUSE_DELAY_SECONDS,
				LifecyclePolicy::ACTION_RECONCILE_ATTACHMENT,
				$job->to_array(),
				LifecyclePolicy::ACTION_GROUP,
				false,
				10
			);

		if ( $scheduled ) {
			$this->logger->info(
				LogCode::RECONCILE_QUEUED,
				'Reconciliation worker re-queued a paused job without consuming work.',
				array(
					'attachment_id' => $job->attachment_id(),
					'reason'        => $job->reason(),
					'delay_seconds' => self::PAUSE_DELAY_SECONDS,
				),
				$job->attachment_id(),
				$job_id
			);

			return;
		}

		$this->logger->warning(
			LogCode::RECONCILE_QUEUE_FAILED,
			'Reconciliation worker could not re-queue a paused job.',
			array(
				'attachment_id' => $job->attachment_id(),
				'reason'        => $job->reason(),
			),
			$job->attachment_id(),
			$job_id
		);
	}
}
