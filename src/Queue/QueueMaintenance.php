<?php
/**
 * Queue maintenance hook provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerRecurringActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\RecurringActionSchedulerInterface;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Logging\Logger;
use HyperWeb\LighthouseImageOptimizer\Logging\LoggerInterface;

/**
 * Schedules and runs recurring stale-lock recovery and statistics reconciliation.
 */
final class QueueMaintenance implements HookProviderInterface {

	public const PRIORITY            = 10;
	public const STALE_LOCK_INTERVAL = 3600;
	public const STATISTICS_INTERVAL = 86400;

	/**
	 * Recurring action scheduler.
	 *
	 * @var RecurringActionSchedulerInterface
	 */
	private $scheduler;

	/**
	 * Attachment lock manager.
	 *
	 * @var AttachmentLockManager
	 */
	private $locks;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Statistics reconciler.
	 *
	 * @var StatisticsReconcilerInterface
	 */
	private $statistics;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Action Scheduler group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Clock callback returning a Unix timestamp.
	 *
	 * @var callable|null
	 */
	private $clock;

	/**
	 * Build the WordPress-backed provider.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new ActionSchedulerRecurringActionScheduler(),
			AttachmentLockManager::for_wordpress(),
			DerivativeRepository::for_wordpress(),
			StatisticsReconciler::for_wordpress(),
			Logger::for_wordpress()
		);
	}

	/**
	 * Create the provider.
	 *
	 * @param RecurringActionSchedulerInterface $scheduler Recurring action scheduler.
	 * @param AttachmentLockManager             $locks Attachment lock manager.
	 * @param DerivativeRepository              $repository Derivative repository.
	 * @param StatisticsReconcilerInterface     $statistics Statistics reconciler.
	 * @param LoggerInterface                   $logger Logger.
	 * @param string                            $group Action Scheduler group.
	 * @param callable|null                     $clock Optional clock returning a Unix timestamp.
	 */
	public function __construct(
		RecurringActionSchedulerInterface $scheduler,
		AttachmentLockManager $locks,
		DerivativeRepository $repository,
		StatisticsReconcilerInterface $statistics,
		LoggerInterface $logger,
		string $group = LifecyclePolicy::ACTION_GROUP,
		?callable $clock = null
	) {
		$this->scheduler  = $scheduler;
		$this->locks      = $locks;
		$this->repository = $repository;
		$this->statistics = $statistics;
		$this->logger     = $logger;
		$this->group      = $group;
		$this->clock      = $clock;
	}

	/**
	 * Register recurring maintenance hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'action_scheduler_init', array( $this, 'ensure_scheduled' ), self::PRIORITY, 0 );
		$hooks->add_action( LifecyclePolicy::ACTION_RECOVER_STALE_LOCKS, array( $this, 'run_stale_lock_recovery' ), self::PRIORITY, 0 );
		$hooks->add_action( LifecyclePolicy::ACTION_RECONCILE_STATISTICS, array( $this, 'run_statistics_reconciliation' ), self::PRIORITY, 0 );
	}

	/**
	 * Ensure recurring maintenance actions are scheduled uniquely.
	 *
	 * @return void
	 */
	public function ensure_scheduled(): void {
		try {
			$this->ensure_schedule( LifecyclePolicy::ACTION_RECOVER_STALE_LOCKS, self::STALE_LOCK_INTERVAL );
			$this->ensure_schedule( LifecyclePolicy::ACTION_RECONCILE_STATISTICS, self::STATISTICS_INTERVAL );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		}
	}

	/**
	 * Recover stale attachment locks and repair obviously stuck statuses.
	 *
	 * @return void
	 */
	public function run_stale_lock_recovery(): void {
		try {
			$result = $this->locks->recover_stale();
		} catch ( \Throwable $throwable ) {
			$this->logger->warning(
				LogCode::MAINTENANCE_STALE_LOCK_RECOVERY_FAILED,
				'Queue maintenance could not complete stale-lock recovery.',
				array(
					'messages' => array( $throwable->getMessage() ),
				)
			);

			return;
		}

		$repaired = 0;
		$repair_failures = 0;

		foreach ( $result->recovered_attachment_ids() as $attachment_id ) {
			$read   = $this->repository->read( $attachment_id );
			$status = $read->status();

			if ( AttachmentStatus::STATE_PROCESSING !== $status->state() ) {
				continue;
			}

			$save = $this->repository->save_status(
				$attachment_id,
				new AttachmentStatus(
					AttachmentStatus::STATE_STALE,
					$status->formats_ready(),
					$this->now(),
					$status->error_code(),
					$status->excluded()
				)
			);

			if ( $save->is_successful() ) {
				++$repaired;
				continue;
			}

			++$repair_failures;
		}

		if ( 0 === $result->stale_recovered() && 0 === $result->invalid_recovered() && 0 === $result->failed() && 0 === $repair_failures ) {
			return;
		}

		$context = array(
			'codes'                   => $result->codes(),
			'scanned'                 => $result->scanned(),
			'active'                  => $result->active(),
			'stale_recovered'         => $result->stale_recovered(),
			'invalid_recovered'       => $result->invalid_recovered(),
			'failed'                  => $result->failed(),
			'status_repairs'          => $repaired,
			'status_repair_failures'  => $repair_failures,
			'sample_attachment_ids'   => $result->sample_attachment_ids(),
			'recovered_attachment_ids' => $result->recovered_attachment_ids(),
		);

		if ( 0 < $result->failed() || 0 < $repair_failures ) {
			$this->logger->warning(
				LogCode::MAINTENANCE_STALE_LOCK_RECOVERY_FAILED,
				'Queue maintenance recovered stale locks with warnings.',
				$context
			);

			return;
		}

		$this->logger->info(
			LogCode::MAINTENANCE_STALE_LOCKS_RECOVERED,
			'Queue maintenance recovered stale or invalid attachment locks.',
			$context
		);
	}

	/**
	 * Recalculate and persist the internal statistics cache.
	 *
	 * @return void
	 */
	public function run_statistics_reconciliation(): void {
		try {
			$result = $this->statistics->reconcile();
		} catch ( \Throwable $throwable ) {
			$this->logger->warning(
				LogCode::MAINTENANCE_STATISTICS_RECONCILE_FAILED,
				'Queue maintenance could not reconcile the statistics cache.',
				array(
					'messages' => array( $throwable->getMessage() ),
				)
			);

			return;
		}

		if ( ! $result->is_successful() ) {
			$this->logger->warning(
				LogCode::MAINTENANCE_STATISTICS_RECONCILE_FAILED,
				'Queue maintenance could not reconcile the statistics cache.',
				array(
					'codes'    => $result->codes(),
					'messages' => $result->messages(),
				)
			);

			return;
		}

		$this->logger->info(
			LogCode::MAINTENANCE_STATISTICS_RECONCILED,
			'Queue maintenance reconciled the internal statistics cache.',
			array(
				'codes'    => $result->codes(),
				'messages' => $result->messages(),
				'totals'   => $result->cache()->totals(),
			)
		);
	}

	/**
	 * Ensure one recurring action is scheduled uniquely.
	 *
	 * @param string $hook Hook.
	 * @param int    $interval Interval in seconds.
	 * @return void
	 */
	private function ensure_schedule( string $hook, int $interval ): void {
		if ( $this->scheduler->has_scheduled_action( $hook, array(), $this->group ) ) {
			return;
		}

		$this->scheduler->schedule_recurring_action(
			$this->now() + $interval,
			$interval,
			$hook,
			array(),
			$this->group,
			true,
			self::PRIORITY
		);
	}

	/**
	 * Get current Unix timestamp.
	 *
	 * @return int
	 */
	private function now(): int {
		if ( null !== $this->clock ) {
			return (int) call_user_func( $this->clock );
		}

		return time();
	}
}
