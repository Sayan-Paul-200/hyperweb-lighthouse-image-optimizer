<?php
/**
 * Fake recurring action scheduler.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\RecurringActionSchedulerInterface;

/**
 * Captures recurring schedule calls.
 */
final class FakeRecurringActionScheduler implements RecurringActionSchedulerInterface {

	/**
	 * Whether the action already exists.
	 *
	 * @var bool
	 */
	public $has_action = false;

	/**
	 * Schedule calls.
	 *
	 * @var int
	 */
	public $schedule_calls = 0;

	/**
	 * Last scheduled payload.
	 *
	 * @var array<string,mixed>|null
	 */
	public $scheduled;

	/**
	 * Determine whether an action is already scheduled.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, array $args, string $group ): bool {
		unset( $hook, $args, $group );

		return $this->has_action;
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
		++$this->schedule_calls;

		$this->scheduled = array(
			'timestamp' => $timestamp,
			'interval'  => $interval,
			'hook'      => $hook,
			'args'      => $args,
			'group'     => $group,
			'unique'    => $unique,
			'priority'  => $priority,
		);

		return true;
	}
}
