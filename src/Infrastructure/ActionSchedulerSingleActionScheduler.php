<?php
/**
 * Action Scheduler one-off action adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Wraps Action Scheduler async action functions behind a reusable seam.
 */
final class ActionSchedulerSingleActionScheduler implements SingleActionSchedulerInterface {

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
	 * Enqueue one async action.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @param bool         $unique Whether the action should be unique.
	 * @param int          $priority Action priority.
	 * @return bool
	 */
	public function enqueue_async_action( string $hook, array $args, string $group, bool $unique, int $priority ): bool {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		try {
			$action_id = \as_enqueue_async_action( $hook, $args, $group, $unique, $priority );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}

		return false !== $action_id && null !== $action_id;
	}

	/**
	 * Schedule one delayed single action.
	 *
	 * @param int          $timestamp Scheduled timestamp.
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @param bool         $unique Whether the action should be unique.
	 * @param int          $priority Action priority.
	 * @return bool
	 */
	public function schedule_single_action( int $timestamp, string $hook, array $args, string $group, bool $unique, int $priority ): bool {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return false;
		}

		try {
			$action_id = \as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}

		return false !== $action_id && null !== $action_id;
	}
}
