<?php
/**
 * Tests for the queue control state store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies pause/resume state persists in a plugin-owned option.
 */
final class QueueControlStateStoreTest extends TestCase {

	/**
	 * Test pause creates the owned option with autoload disabled.
	 *
	 * @return void
	 */
	public function test_pause_adds_option_with_autoload_disabled(): void {
		$options = new FakeOptionStore();
		$store   = new QueueControlStateStore(
			$options,
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);

		$state = $store->pause( 7 );

		self::assertTrue( $state->paused() );
		self::assertSame( '2026-07-12 00:00:00', $state->updated_at_gmt() );
		self::assertSame( 7, $state->updated_by_user_id() );
		self::assertSame( 'no', $options->autoload[ LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE ] );
		self::assertTrue( $options->options[ LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE ]['paused'] );
	}

	/**
	 * Test resume updates an existing option and keeps autoload disabled.
	 *
	 * @return void
	 */
	public function test_resume_updates_existing_option_with_autoload_disabled(): void {
		$options = new FakeOptionStore(
			array(
				LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE => array(
					'paused'             => true,
					'updated_at_gmt'     => '2026-07-11 23:00:00',
					'updated_by_user_id' => 3,
				),
			)
		);
		$store   = new QueueControlStateStore(
			$options,
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 01:00:00';
			}
		);

		$state = $store->resume( 11 );

		self::assertFalse( $state->paused() );
		self::assertSame( '2026-07-12 01:00:00', $state->updated_at_gmt() );
		self::assertSame( 11, $state->updated_by_user_id() );
		self::assertSame( 'no', $options->autoload[ LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE ] );
		self::assertFalse( $options->options[ LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE ]['paused'] );
	}
}
