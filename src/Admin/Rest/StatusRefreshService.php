<?php
/**
 * Status refresh service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\SingleActionSchedulerInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;

/**
 * Manages async statistics recalculation state for the dashboard.
 */
final class StatusRefreshService {

	/**
	 * Async action priority.
	 *
	 * @var int
	 */
	public const PRIORITY = 10;

	/**
	 * One-off action scheduler.
	 *
	 * @var SingleActionSchedulerInterface
	 */
	private $scheduler;

	/**
	 * Hook name.
	 *
	 * @var string
	 */
	private $hook;

	/**
	 * Action group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Create the service.
	 *
	 * @param SingleActionSchedulerInterface $scheduler One-off action scheduler.
	 * @param string                         $hook Statistics hook.
	 * @param string                         $group Action group.
	 */
	public function __construct(
		SingleActionSchedulerInterface $scheduler,
		string $hook = LifecyclePolicy::ACTION_RECONCILE_STATISTICS,
		string $group = LifecyclePolicy::ACTION_GROUP
	) {
		$this->scheduler = $scheduler;
		$this->hook      = $hook;
		$this->group     = $group;
	}

	/**
	 * Build the dashboard refresh summary.
	 *
	 * @param StatisticsCache $cache Statistics cache.
	 * @return array<string,mixed>
	 */
	public function summary( StatisticsCache $cache ): array {
		return array(
			'generated_at_gmt' => $cache->generated_at_gmt(),
			'pending'          => $this->is_pending(),
		);
	}

	/**
	 * Request one asynchronous statistics recalculation.
	 *
	 * @return StatusRefreshRequestResult
	 */
	public function request_recalculation(): StatusRefreshRequestResult {
		if ( $this->is_pending() ) {
			return StatusRefreshRequestResult::already_pending();
		}

		try {
			$queued = $this->scheduler->enqueue_async_action(
				$this->hook,
				array(),
				$this->group,
				true,
				self::PRIORITY
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return StatusRefreshRequestResult::unavailable();
		}

		return $queued
			? StatusRefreshRequestResult::queued()
			: StatusRefreshRequestResult::unavailable();
	}

	/**
	 * Determine whether recalculation is already pending.
	 *
	 * @return bool
	 */
	private function is_pending(): bool {
		try {
			return $this->scheduler->has_scheduled_action( $this->hook, array(), $this->group );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}
}
