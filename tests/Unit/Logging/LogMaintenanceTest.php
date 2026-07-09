<?php
/**
 * Tests for log maintenance hooks.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance;
use PHPUnit\Framework\TestCase;

/**
 * Verifies log retention scheduling.
 */
final class LogMaintenanceTest extends TestCase {

	/**
	 * Test hook registration.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_scheduler_and_cleanup_actions(): void {
		$maintenance = new LogMaintenance(
			new FakeLogPruner(),
			new FakeRecurringActionScheduler()
		);
		$hooks       = new HookRegistrar();

		$maintenance->register_hooks( $hooks );

		self::assertCount( 2, $hooks->actions() );
		self::assertSame( 'action_scheduler_init', $hooks->actions()[0]['hook'] );
		self::assertSame( LogMaintenance::CLEANUP_HOOK, $hooks->actions()[1]['hook'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
		self::assertSame( 0, $hooks->actions()[1]['accepted_args'] );
	}

	/**
	 * Test missing recurring cleanup is scheduled once.
	 *
	 * @return void
	 */
	public function test_ensure_scheduled_adds_unique_daily_cleanup(): void {
		$scheduler   = new FakeRecurringActionScheduler();
		$maintenance = new LogMaintenance(
			new FakeLogPruner(),
			$scheduler,
			LifecyclePolicy::ACTION_GROUP,
			static function (): int {
				return 1783555200;
			}
		);

		$maintenance->ensure_scheduled();

		self::assertSame( 1, $scheduler->schedule_calls );
		self::assertSame( 1783641600, $scheduler->scheduled['timestamp'] );
		self::assertSame( LogMaintenance::DAILY_INTERVAL, $scheduler->scheduled['interval'] );
		self::assertSame( LogMaintenance::CLEANUP_HOOK, $scheduler->scheduled['hook'] );
		self::assertSame( LifecyclePolicy::ACTION_GROUP, $scheduler->scheduled['group'] );
		self::assertTrue( $scheduler->scheduled['unique'] );
		self::assertSame( LogMaintenance::PRIORITY, $scheduler->scheduled['priority'] );
	}

	/**
	 * Test existing recurring cleanup is not duplicated.
	 *
	 * @return void
	 */
	public function test_ensure_scheduled_does_not_duplicate_existing_action(): void {
		$scheduler             = new FakeRecurringActionScheduler();
		$scheduler->has_action = true;
		$maintenance           = new LogMaintenance( new FakeLogPruner(), $scheduler );

		$maintenance->ensure_scheduled();

		self::assertSame( 0, $scheduler->schedule_calls );
	}

	/**
	 * Test cleanup callback runs the pruner without exposing return value.
	 *
	 * @return void
	 */
	public function test_cleanup_runs_pruner(): void {
		$pruner      = new FakeLogPruner();
		$maintenance = new LogMaintenance( $pruner, new FakeRecurringActionScheduler() );

		$maintenance->run_retention_cleanup();

		self::assertSame( 1, $pruner->calls );
	}
}
