<?php
/**
 * One-off action scheduler contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Small adapter for unique Action Scheduler single or async actions.
 */
interface SingleActionSchedulerInterface {

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
	 * Enqueue one async action.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @param bool         $unique Whether the action should be unique.
	 * @param int          $priority Action priority.
	 * @return bool
	 */
	public function enqueue_async_action( string $hook, array $args, string $group, bool $unique, int $priority ): bool;

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
	public function schedule_single_action( int $timestamp, string $hook, array $args, string $group, bool $unique, int $priority ): bool;
}
