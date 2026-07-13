<?php
/**
 * Log maintenance hook provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerRecurringActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\RecurringActionSchedulerInterface;

/**
 * Schedules and runs bounded log retention cleanup.
 */
final class LogMaintenance implements HookProviderInterface {

	public const CLEANUP_HOOK   = LifecyclePolicy::ACTION_CLEANUP_LOGS;
	public const DAILY_INTERVAL = 86400;
	public const PRIORITY       = 10;

	/**
	 * Log pruner.
	 *
	 * @var LogPrunerInterface
	 */
	private $pruner;

	/**
	 * Recurring action scheduler.
	 *
	 * @var RecurringActionSchedulerInterface
	 */
	private $scheduler;

	/**
	 * Action group.
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
	 * Create a WordPress-backed provider.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			LogPruner::for_wordpress(),
			new ActionSchedulerRecurringActionScheduler()
		);
	}

	/**
	 * Create the provider.
	 *
	 * @param LogPrunerInterface                $pruner Log pruner.
	 * @param RecurringActionSchedulerInterface $scheduler Recurring action scheduler.
	 * @param string                            $group Action Scheduler group.
	 * @param callable|null                     $clock Optional clock returning a Unix timestamp.
	 */
	public function __construct(
		LogPrunerInterface $pruner,
		RecurringActionSchedulerInterface $scheduler,
		string $group = LifecyclePolicy::ACTION_GROUP,
		?callable $clock = null
	) {
		$this->pruner    = $pruner;
		$this->scheduler = $scheduler;
		$this->group     = $group;
		$this->clock     = $clock;
	}

	/**
	 * Register log maintenance hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'action_scheduler_init', array( $this, 'ensure_scheduled' ), self::PRIORITY, 0 );
		$hooks->add_action( self::CLEANUP_HOOK, array( $this, 'run_retention_cleanup' ), self::PRIORITY, 0 );
	}

	/**
	 * Ensure recurring log cleanup is scheduled after Action Scheduler initializes.
	 *
	 * @return void
	 */
	public function ensure_scheduled(): void {
		try {
			if ( $this->scheduler->has_scheduled_action( self::CLEANUP_HOOK, array(), $this->group ) ) {
				return;
			}

			$this->scheduler->schedule_recurring_action(
				$this->now() + self::DAILY_INTERVAL,
				self::DAILY_INTERVAL,
				self::CLEANUP_HOOK,
				array(),
				$this->group,
				true,
				self::PRIORITY
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		}
	}

	/**
	 * Run bounded log retention cleanup.
	 *
	 * @return void
	 */
	public function run_retention_cleanup(): void {
		try {
			$this->pruner->prune();
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		}
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
