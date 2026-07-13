<?php
/**
 * Fake one-off action scheduler.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\SingleActionSchedulerInterface;

/**
 * Records unique one-off scheduling interactions for tests.
 */
final class FakeSingleActionScheduler implements SingleActionSchedulerInterface {

	/**
	 * Whether the action is already scheduled.
	 *
	 * @var bool
	 */
	public $scheduled = false;

	/**
	 * Whether enqueue should succeed.
	 *
	 * @var bool
	 */
	public $enqueue_result = true;

	/**
	 * Has-scheduled-action() calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $has_calls = array();

	/**
	 * Enqueue-async-action() calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $enqueue_calls = array();

	/**
	 * Schedule-single-action() calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $single_calls = array();

	/**
	 * Determine whether an action is already scheduled.
	 *
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, array $args, string $group ): bool {
		$this->has_calls[] = array(
			'hook'  => $hook,
			'args'  => $args,
			'group' => $group,
		);

		return $this->scheduled;
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
		$this->enqueue_calls[] = array(
			'hook'     => $hook,
			'args'     => $args,
			'group'    => $group,
			'unique'   => $unique,
			'priority' => $priority,
		);

		if ( $this->enqueue_result ) {
			$this->scheduled = true;
		}

		return $this->enqueue_result;
	}

	/**
	 * Record one delayed single action scheduling call.
	 *
	 * @param int          $timestamp Scheduled timestamp.
	 * @param string       $hook Action hook.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @param bool         $unique Whether unique.
	 * @param int          $priority Action priority.
	 * @return bool
	 */
	public function schedule_single_action( int $timestamp, string $hook, array $args, string $group, bool $unique, int $priority ): bool {
		$this->single_calls[] = array(
			'timestamp' => $timestamp,
			'hook'      => $hook,
			'args'      => $args,
			'group'     => $group,
			'unique'    => $unique,
			'priority'  => $priority,
		);

		if ( $this->enqueue_result ) {
			$this->scheduled = true;
		}

		return $this->enqueue_result;
	}
}
