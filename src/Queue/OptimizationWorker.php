<?php
/**
 * Optimization worker.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintComparison;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessorInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\SingleActionSchedulerInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\AttachmentSourceCollectorInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadAwareSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WordPressWpOffloadMediaRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Logging\Logger;
use HyperWeb\LighthouseImageOptimizer\Logging\LoggerInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Executes queued optimization jobs through the attachment processor.
 */
final class OptimizationWorker implements HookProviderInterface {

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
	 * @var AttachmentSourceCollectorInterface
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
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Retry policy.
	 *
	 * @var OptimizationRetryPolicy
	 */
	private $retry_policy;

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
	 * Offload support service.
	 *
	 * @var OffloadSupportService|null
	 */
	private $offload;

	/**
	 * Build the WordPress-backed worker.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$runtime = new WordPressWpOffloadMediaRuntime();
		$files   = new \HyperWeb\LighthouseImageOptimizer\Image\WordPressImageFileProbe();
		$adapter = new WpOffloadMediaAdapter( $runtime, $files, new \HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer() );
		$offload = new OffloadSupportService( $adapter );

		return new self(
			ActionSchedulerQueue::for_wordpress(),
			AttachmentLockManager::for_wordpress(),
			new OffloadAwareSourceCollector(
				new LocalAttachmentSourceCollector( SourceCollector::for_wordpress() ),
				$runtime,
				$adapter,
				$offload,
				$files,
				new \HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer()
			),
			new AttachmentFingerprintBuilder(),
			DerivativeRepository::for_wordpress(),
			AttachmentProcessor::for_wordpress(),
			SettingsRepository::for_wordpress(),
			Logger::for_wordpress(),
			new OptimizationRetryPolicy(),
			new SystemAttachmentClock(),
			QueueControlStateStore::for_wordpress(),
			new ActionSchedulerSingleActionScheduler(),
			$offload
		);
	}

	/**
	 * Create worker.
	 *
	 * @param QueueInterface                       $queue Queue adapter.
	 * @param AttachmentLockManager                $locks Lock manager.
	 * @param AttachmentSourceCollectorInterface   $collector Source collector.
	 * @param AttachmentFingerprintBuilder         $fingerprinter Fingerprint builder.
	 * @param DerivativeRepository                 $repository Derivative repository.
	 * @param AttachmentProcessorInterface         $processor Attachment processor.
	 * @param SettingsRepositoryInterface          $settings Settings repository.
	 * @param LoggerInterface                      $logger Logger.
	 * @param OptimizationRetryPolicy              $retry_policy Retry policy.
	 * @param AttachmentClockInterface             $clock Clock.
	 * @param QueueControlStateStoreInterface|null $controls Queue control state store.
	 * @param SingleActionSchedulerInterface|null  $single_actions Single action scheduler.
	 * @param OffloadSupportService|null           $offload Offload support service.
	 */
	public function __construct(
		QueueInterface $queue,
		AttachmentLockManager $locks,
		AttachmentSourceCollectorInterface $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		DerivativeRepository $repository,
		AttachmentProcessorInterface $processor,
		SettingsRepositoryInterface $settings,
		LoggerInterface $logger,
		OptimizationRetryPolicy $retry_policy,
		AttachmentClockInterface $clock,
		?QueueControlStateStoreInterface $controls = null,
		?SingleActionSchedulerInterface $single_actions = null,
		?OffloadSupportService $offload = null
	) {
		$this->queue          = $queue;
		$this->locks          = $locks;
		$this->collector      = $collector;
		$this->fingerprinter  = $fingerprinter;
		$this->repository     = $repository;
		$this->processor      = $processor;
		$this->settings       = $settings;
		$this->logger         = $logger;
		$this->retry_policy   = $retry_policy;
		$this->clock          = $clock;
		$this->controls       = $controls;
		$this->single_actions = $single_actions;
		$this->offload        = $offload;
	}

	/**
	 * Register the optimization hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action(
			LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT,
			array( $this, 'handle_optimization_job' ),
			10,
			6
		);
	}

	/**
	 * Execute an optimization callback payload.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @param mixed $format Target format.
	 * @param mixed $cursor Source cursor.
	 * @param mixed $force Force flag.
	 * @param mixed $reason Queueing reason.
	 * @param mixed $fingerprint Attachment fingerprint signature.
	 * @return void
	 */
	public function handle_optimization_job( $attachment_id, $format, $cursor, $force, $reason, $fingerprint ): void {
		$job = OptimizationJob::from_callback_args( $attachment_id, $format, $cursor, $force, $reason, $fingerprint );

		if ( ! $job instanceof OptimizationJob ) {
			$valid_attachment_id = is_numeric( $attachment_id ) ? max( 0, (int) $attachment_id ) : 0;

			if ( 0 < $valid_attachment_id ) {
				$this->save_status(
					$valid_attachment_id,
					AttachmentStatus::STATE_FAILED,
					ConversionResultCode::INVALID_JOB_PAYLOAD,
					$this->current_status( $valid_attachment_id )
				);
			}

			$this->logger->error(
				LogCode::WORKER_INVALID_JOB_PAYLOAD,
				'Optimization worker received an invalid job payload.',
				array(
					'attachment_id' => $valid_attachment_id,
					'format'        => is_scalar( $format ) ? (string) $format : null,
					'cursor'        => is_numeric( $cursor ) ? (int) $cursor : null,
					'force'         => (bool) $force,
					'reason'        => is_scalar( $reason ) ? (string) $reason : null,
				),
				0 < $valid_attachment_id ? $valid_attachment_id : null
			);

			return;
		}

		$this->run_job( $job );
	}

	/**
	 * Run one optimization job.
	 *
	 * @param OptimizationJob $job Job.
	 * @return void
	 */
	public function run_job( OptimizationJob $job ): void {
		$attachment_id  = $job->attachment_id();
		$current_status = $this->current_status( $attachment_id );
		$job_id         = $this->job_id( $job );
		$max_retries    = $this->settings->max_retries();
		$worker_budget  = $this->settings->worker_time_budget();

		if ( $this->paused() ) {
			$this->requeue_paused_job( $job, $job_id );
			return;
		}

		$acquired = $this->locks->acquire( $attachment_id );

		if ( ! $acquired->is_successful() ) {
			$this->handle_lock_unavailable( $job, $current_status, $job_id, $max_retries, $acquired->messages() );
			return;
		}

		$lock = $acquired->lock();

		try {
			if ( null !== $this->offload && ! $this->offload->attachment_support( $attachment_id )->is_supported() ) {
				$this->save_status(
					$attachment_id,
					AttachmentStatus::STATE_FAILED,
					'offload_unsupported',
					$current_status
				);

				$this->logger->warning(
					LogCode::WORKER_RESULT_FAILED,
					'Optimization worker skipped an attachment because the current offload state is unsupported.',
					array(
						'attachment_id' => $attachment_id,
						'format'        => $job->format(),
						'reason'        => $job->reason(),
					),
					$attachment_id,
					$job_id
				);

				return;
			}

			$collected = $this->collector->collect( $attachment_id );

			try {
				$collection = $collected->collection();
				$comparison = $this->fingerprinter->compare_signature( $job->fingerprint(), $collection );

				if ( $comparison->is_stale() ) {
					$this->handle_stale_fingerprint( $job, $current_status, $comparison, $job_id );
					return;
				}

				$fingerprint = $comparison->is_match() ? $comparison->current_fingerprint() : null;

				$this->save_status(
					$attachment_id,
					AttachmentStatus::STATE_PROCESSING,
					null,
					$current_status
				);

				$result = $this->processor->process_request(
					new AttachmentProcessRequest(
						$attachment_id,
						$job->format(),
						$job->cursor(),
						$worker_budget,
						$job->force(),
						$collection,
						$fingerprint
					)
				);
			} finally {
				$collected->release();
			}

			if ( ! $result->is_complete() ) {
				$continuation = new OptimizationJob(
					$attachment_id,
					$job->format(),
					$result->next_cursor(),
					$job->force(),
					'continuation',
					$fingerprint instanceof AttachmentFingerprint ? $fingerprint->signature() : $job->fingerprint()
				);

				$queue_status = $this->queue->enqueue_optimization( $continuation );
				if ( $queue_status->is_successful() ) {
					$this->save_status(
						$attachment_id,
						AttachmentStatus::STATE_PROCESSING,
						null,
						$this->current_status( $attachment_id )
					);

					$this->logger->info(
						LogCode::WORKER_CONTINUATION_QUEUED,
						'Optimization worker queued a continuation job.',
						$this->job_context( $job, $result, $queue_status->codes() ),
						$attachment_id,
						$job_id
					);
				} else {
					$this->logger->warning(
						LogCode::WORKER_CONTINUATION_QUEUE_FAILED,
						'Optimization worker could not queue a continuation job.',
						$this->job_context( $job, $result, $queue_status->codes() ),
						$attachment_id,
						$job_id
					);
				}
			}

			if ( $this->retry_policy->should_retry_result( $job, $result, $max_retries ) ) {
				$retry_job    = new OptimizationJob(
					$attachment_id,
					$job->format(),
					$job->cursor(),
					$job->force(),
					$this->retry_policy->next_retry_reason( $job ),
					$job->fingerprint()
				);
				$retry_status = $this->queue->enqueue_optimization(
					$retry_job,
					$this->retry_policy->retry_delay_seconds( $job )
				);

				if ( $retry_status->is_successful() ) {
					$this->save_status(
						$attachment_id,
						AttachmentStatus::STATE_QUEUED,
						null,
						$this->current_status( $attachment_id )
					);

					$this->logger->warning(
						LogCode::WORKER_RETRY_QUEUED,
						'Optimization worker queued a retry for transient processing failures.',
						$this->job_context( $job, $result, $retry_status->codes() ),
						$attachment_id,
						$job_id
					);

					return;
				}

				$this->logger->warning(
					LogCode::WORKER_RETRY_QUEUE_FAILED,
					'Optimization worker could not queue a retry for transient processing failures.',
					$this->job_context( $job, $result, $retry_status->codes() ),
					$attachment_id,
					$job_id
				);
			}

			$this->log_result( $job, $result, $job_id );
		} catch ( \Throwable $throwable ) {
			$this->save_status(
				$attachment_id,
				AttachmentStatus::STATE_FAILED,
				AttachmentProcessResult::CODE_UNEXPECTED_ERROR,
				$this->current_status( $attachment_id )
			);

			$this->logger->error(
				LogCode::WORKER_UNEXPECTED_ERROR,
				'Optimization worker encountered an unexpected error.',
				array(
					'attachment_id' => $attachment_id,
					'format'        => $job->format(),
					'cursor'        => $job->cursor(),
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
						'Optimization worker could not release the attachment lock cleanly.',
						array(
							'attachment_id' => $attachment_id,
							'format'        => $job->format(),
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
	 * Handle a lock collision before processing.
	 *
	 * @param OptimizationJob  $job Job.
	 * @param AttachmentStatus $current_status Current status.
	 * @param string           $job_id Job ID.
	 * @param int              $max_retries Max retries.
	 * @param string[]         $messages Lock messages.
	 * @return void
	 */
	private function handle_lock_unavailable(
		OptimizationJob $job,
		AttachmentStatus $current_status,
		string $job_id,
		int $max_retries,
		array $messages
	): void {
		if ( $this->retry_policy->should_retry_lock_collision( $job, $max_retries ) ) {
			$retry_job    = new OptimizationJob(
				$job->attachment_id(),
				$job->format(),
				$job->cursor(),
				$job->force(),
				$this->retry_policy->next_retry_reason( $job ),
				$job->fingerprint()
			);
			$retry_status = $this->queue->enqueue_optimization(
				$retry_job,
				$this->retry_policy->retry_delay_seconds( $job )
			);

			if ( $retry_status->is_successful() ) {
				$this->save_status( $job->attachment_id(), AttachmentStatus::STATE_QUEUED, null, $current_status );

				$this->logger->warning(
					LogCode::WORKER_RETRY_QUEUED,
					'Optimization worker re-queued a locked attachment for retry.',
					array(
						'attachment_id' => $job->attachment_id(),
						'format'        => $job->format(),
						'cursor'        => $job->cursor(),
						'retry_attempt' => $this->retry_policy->next_retry_attempt( $job ),
						'queue_codes'   => $retry_status->codes(),
						'lock_messages' => $messages,
					),
					$job->attachment_id(),
					$job_id
				);

				return;
			}
		}

		$this->save_status(
			$job->attachment_id(),
			AttachmentStatus::STATE_FAILED,
			ConversionResultCode::LOCK_UNAVAILABLE,
			$current_status
		);

		$this->logger->warning(
			LogCode::WORKER_LOCK_UNAVAILABLE,
			'Optimization worker could not acquire the attachment lock.',
			array(
				'attachment_id' => $job->attachment_id(),
				'format'        => $job->format(),
				'cursor'        => $job->cursor(),
				'messages'      => $messages,
			),
			$job->attachment_id(),
			$job_id
		);
	}

	/**
	 * Handle a stale queued fingerprint.
	 *
	 * @param OptimizationJob                 $job Job.
	 * @param AttachmentStatus                $current_status Current status.
	 * @param AttachmentFingerprintComparison $comparison Fingerprint comparison.
	 * @param string                          $job_id Job ID.
	 * @return void
	 */
	private function handle_stale_fingerprint(
		OptimizationJob $job,
		AttachmentStatus $current_status,
		AttachmentFingerprintComparison $comparison,
		string $job_id
	): void {
		$current = $comparison->current_fingerprint();

		if ( null === $current ) {
			$this->save_status(
				$job->attachment_id(),
				AttachmentStatus::STATE_STALE,
				$comparison->code(),
				$current_status
			);

			$this->logger->warning(
				LogCode::WORKER_FINGERPRINT_STALE,
				'Optimization worker detected a stale queued fingerprint but could not build a fresh one.',
				array(
					'attachment_id'      => $job->attachment_id(),
					'format'             => $job->format(),
					'cursor'             => $job->cursor(),
					'comparison_code'    => $comparison->code(),
					'comparison_details' => $comparison->details(),
				),
				$job->attachment_id(),
				$job_id
			);

			return;
		}

		$fresh_job = new OptimizationJob(
			$job->attachment_id(),
			$job->format(),
			0,
			$job->force(),
			'source_changed',
			$current->signature()
		);

		$queue_status = $this->queue->enqueue_optimization( $fresh_job );

		if ( $queue_status->is_successful() ) {
			$this->save_status(
				$job->attachment_id(),
				AttachmentStatus::STATE_QUEUED,
				null,
				$current_status
			);

			$this->logger->info(
				LogCode::WORKER_FINGERPRINT_STALE_REQUEUED,
				'Optimization worker re-queued the attachment because the source fingerprint changed.',
				array(
					'attachment_id'   => $job->attachment_id(),
					'format'          => $job->format(),
					'comparison_code' => $comparison->code(),
					'queue_codes'     => $queue_status->codes(),
				),
				$job->attachment_id(),
				$job_id
			);

			return;
		}

		$this->save_status(
			$job->attachment_id(),
			AttachmentStatus::STATE_STALE,
			$comparison->code(),
			$current_status
		);

		$this->logger->warning(
			LogCode::WORKER_FINGERPRINT_STALE,
			'Optimization worker detected a stale queued fingerprint and could not re-queue fresh work.',
			array(
				'attachment_id'   => $job->attachment_id(),
				'format'          => $job->format(),
				'comparison_code' => $comparison->code(),
				'queue_codes'     => $queue_status->codes(),
			),
			$job->attachment_id(),
			$job_id
		);
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
	 * Read the current attachment status.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentStatus
	 */
	private function current_status( int $attachment_id ): AttachmentStatus {
		return $this->repository->read( $attachment_id )->status();
	}

	/**
	 * Build safe worker log context.
	 *
	 * @param OptimizationJob         $job Job.
	 * @param AttachmentProcessResult $result Result.
	 * @param string[]                $queue_codes Queue result codes.
	 * @return array<string,mixed>
	 */
	private function job_context( OptimizationJob $job, AttachmentProcessResult $result, array $queue_codes = array() ): array {
		return array(
			'attachment_id' => $job->attachment_id(),
			'format'        => $job->format(),
			'cursor'        => $job->cursor(),
			'next_cursor'   => $result->next_cursor(),
			'complete'      => $result->is_complete(),
			'reason'        => $job->reason(),
			'retry_attempt' => $this->retry_policy->retry_attempt_from_reason( $job->reason() ),
			'result_codes'  => $result->codes(),
			'queue_codes'   => $queue_codes,
		);
	}

	/**
	 * Log the final result severity.
	 *
	 * @param OptimizationJob         $job Job.
	 * @param AttachmentProcessResult $result Result.
	 * @param string                  $job_id Job ID.
	 * @return void
	 */
	private function log_result( OptimizationJob $job, AttachmentProcessResult $result, string $job_id ): void {
		$context = $this->job_context( $job, $result );

		if ( ! $result->is_successful() ) {
			$this->logger->error(
				LogCode::WORKER_RESULT_FAILED,
				'Optimization worker finished with a failed processing result.',
				$context,
				$job->attachment_id(),
				$job_id
			);
			return;
		}

		if ( $result->results() instanceof \HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection
			&& $result->results()->has_failures() ) {
			$this->logger->warning(
				LogCode::WORKER_RESULT_PARTIAL,
				'Optimization worker finished with partial processing failures.',
				$context,
				$job->attachment_id(),
				$job_id
			);
			return;
		}

		if ( ! $result->is_complete() ) {
			$this->logger->info(
				LogCode::WORKER_CONTINUATION_QUEUED,
				'Optimization worker paused for continuation within the configured time budget.',
				$context,
				$job->attachment_id(),
				$job_id
			);
			return;
		}

		$this->logger->info(
			LogCode::WORKER_RESULT_COMPLETED,
			'Optimization worker completed processing successfully.',
			$context,
			$job->attachment_id(),
			$job_id
		);
	}

	/**
	 * Build a safe queue job identifier for logs.
	 *
	 * @param OptimizationJob $job Job.
	 * @return string
	 */
	private function job_id( OptimizationJob $job ): string {
		return substr(
			sprintf(
				'%d:%s:%d:%s:%s',
				$job->attachment_id(),
				$job->format(),
				$job->cursor(),
				$job->reason(),
				$job->fingerprint()
			),
			0,
			191
		);
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
	 * Requeue one paused optimization job without consuming work.
	 *
	 * @param OptimizationJob $job Job.
	 * @param string          $job_id Job identifier.
	 * @return void
	 */
	private function requeue_paused_job( OptimizationJob $job, string $job_id ): void {
		$scheduled = null !== $this->single_actions
			&& $this->single_actions->schedule_single_action(
				$this->clock->now() + self::PAUSE_DELAY_SECONDS,
				LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT,
				$job->to_array(),
				LifecyclePolicy::ACTION_GROUP,
				false,
				10
			);

		if ( $scheduled ) {
			$this->logger->info(
				LogCode::WORKER_CONTINUATION_QUEUED,
				'Optimization worker re-queued a paused job without consuming work.',
				array(
					'attachment_id' => $job->attachment_id(),
					'format'        => $job->format(),
					'reason'        => $job->reason(),
					'delay_seconds' => self::PAUSE_DELAY_SECONDS,
				),
				$job->attachment_id(),
				$job_id
			);

			return;
		}

		$this->logger->warning(
			LogCode::WORKER_LOCK_UNAVAILABLE,
			'Optimization worker could not re-queue a paused job.',
			array(
				'attachment_id' => $job->attachment_id(),
				'format'        => $job->format(),
				'reason'        => $job->reason(),
			),
			$job->attachment_id(),
			$job_id
		);
	}
}
