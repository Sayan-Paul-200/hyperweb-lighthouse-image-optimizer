<?php
/**
 * Tests for ActionSchedulerAttachmentJobControl.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\ActionSchedulerAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentJobControlResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies counting and cancellation stay limited to plugin-owned attachment jobs.
 */
final class ActionSchedulerAttachmentJobControlTest extends TestCase {

	/**
	 * Test pending and in-progress counts sum matching hook batches only.
	 *
	 * @return void
	 */
	public function test_counts_pending_and_in_progress_jobs_by_hook_and_status(): void {
		$queries = array();
		$control = new ActionSchedulerAttachmentJobControl(
			'hwlio',
			array( 'optimize', 'reconcile' ),
			static function (): bool {
				return true;
			},
			static function ( array $query ) use ( &$queries ): array {
				$queries[] = $query;

				if ( 'optimize' === $query['hook'] && 'pending' === $query['status'] ) {
					return array(
						array( 'args' => array( 'attachment_id' => 10 ) ),
						array( 'args' => array( 'attachment_id' => 11 ) ),
					);
				}

				if ( 'reconcile' === $query['hook'] && 'pending' === $query['status'] ) {
					return array(
						array( 'args' => array( 'attachment_id' => 12 ) ),
					);
				}

				if ( 'optimize' === $query['hook'] && 'in-progress' === $query['status'] ) {
					return array(
						array( 'args' => array( 'attachment_id' => 20 ) ),
					);
				}

				return array();
			},
			static function ( string $hook, array $args, string $group ) {
				unset( $hook, $args, $group );
				return 0;
			}
		);

		self::assertSame( 3, $control->pending_count() );
		self::assertSame( 1, $control->in_progress_count() );
		self::assertNotEmpty( $queries );
	}

	/**
	 * Test pending cancellations report partial failures without touching history.
	 *
	 * @return void
	 */
	public function test_cancel_pending_reports_partial_failures(): void {
		$cancelled = array();
		$control   = new ActionSchedulerAttachmentJobControl(
			'hwlio',
			array( 'optimize', 'reconcile' ),
			static function (): bool {
				return true;
			},
			static function ( array $query ): array {
				if ( 'optimize' === $query['hook'] ) {
					return array(
						array(
							'args' => array(
								'attachment_id' => 10,
								'format'        => 'webp',
							),
						),
					);
				}

				if ( 'reconcile' === $query['hook'] ) {
					return array(
						array(
							'args' => array(
								'attachment_id' => 11,
								'fingerprint'   => 'abc',
							),
						),
					);
				}

				return array();
			},
			static function ( string $hook, array $args, string $group ) use ( &$cancelled ) {
				$cancelled[] = array(
					'hook'  => $hook,
					'args'  => $args,
					'group' => $group,
				);

				return 'optimize' === $hook ? 44 : false;
			}
		);

		$result = $control->cancel_pending();

		self::assertFalse( $result->is_successful() );
		self::assertContains( AttachmentJobControlResult::CODE_CANCEL_FAILED, $result->codes() );
		self::assertSame( 1, $result->cancelled_actions() );
		self::assertCount( 2, $cancelled );
	}

	/**
	 * Test unavailable Action Scheduler returns a stable unavailable result.
	 *
	 * @return void
	 */
	public function test_cancel_pending_returns_unavailable_when_scheduler_not_ready(): void {
		$control = new ActionSchedulerAttachmentJobControl(
			'hwlio',
			array( 'optimize' ),
			static function (): bool {
				return false;
			},
			static function ( array $query ): array {
				unset( $query );
				return array();
			},
			static function ( string $hook, array $args, string $group ) {
				unset( $hook, $args, $group );
				return 0;
			}
		);

		$result = $control->cancel_pending();

		self::assertFalse( $result->is_successful() );
		self::assertContains( AttachmentJobControlResult::CODE_UNAVAILABLE, $result->codes() );
		self::assertSame( 0, $result->cancelled_actions() );
	}
}
