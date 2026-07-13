<?php
/**
 * Recurring action scheduler contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Small adapter for Action Scheduler recurring actions.
 */
interface RecurringActionSchedulerInterface {

	/**
	 * Determine whether an action is already scheduled.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, array $args, string $group ): bool;

	/**
	 * Schedule a recurring action.
	 *
	 * @param int          $timestamp First run timestamp.
	 * @param int          $interval Interval in seconds.
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @param bool         $unique Whether the action should be unique.
	 * @param int          $priority Action priority.
	 * @return bool
	 */
	public function schedule_recurring_action(
		int $timestamp,
		int $interval,
		string $hook,
		array $args,
		string $group,
		bool $unique,
		int $priority
	): bool;
}
