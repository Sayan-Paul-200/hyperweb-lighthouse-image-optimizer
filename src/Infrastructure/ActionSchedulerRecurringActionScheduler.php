<?php
/**
 * Action Scheduler recurring action adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Wraps Action Scheduler functions behind a reusable recurring-action seam.
 */
final class ActionSchedulerRecurringActionScheduler implements RecurringActionSchedulerInterface {

	/**
	 * Determine whether an action is already scheduled.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, array $args, string $group ): bool {
		try {
			if ( function_exists( 'as_has_scheduled_action' ) ) {
				return (bool) \as_has_scheduled_action( $hook, $args, $group );
			}

			if ( function_exists( 'as_next_scheduled_action' ) ) {
				return false !== \as_next_scheduled_action( $hook, $args, $group );
			}
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return true;
		}

		return true;
	}

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
	): bool {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return false;
		}

		try {
			$action_id = \as_schedule_recurring_action(
				$timestamp,
				$interval,
				$hook,
				$args,
				$group,
				$unique,
				$priority
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}

		return false !== $action_id && null !== $action_id;
	}
}
