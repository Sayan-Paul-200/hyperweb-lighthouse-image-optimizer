<?php
/**
 * Tests for the Action Scheduler attachment job cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\ActionSchedulerAttachmentJobCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies pending attachment jobs are matched and cancelled conservatively.
 */
final class ActionSchedulerAttachmentJobCleanerTest extends TestCase {

	/**
	 * Test only the target attachment jobs are cancelled.
	 *
	 * @return void
	 */
	public function test_cancel_pending_actions_matches_attachment_id_and_leaves_other_actions_untouched(): void {
		$cancelled = array();
		$queried   = array();
		$cleaner   = new ActionSchedulerAttachmentJobCleaner(
			'hwlio',
			array( 'hwlio_optimize_attachment_format', 'hwlio_cleanup_attachment' ),
			static function (): bool {
				return true;
			},
			static function ( array $query ) use ( &$queried ): array {
				$queried[] = $query;

				if ( 'hwlio_optimize_attachment_format' !== $query['hook'] ) {
					return array();
				}

				return array(
					array(
						'args' => array(
							'attachment_id' => 15,
							'format'        => 'webp',
						),
					),
					array(
						'args' => array(
							'attachment_id' => 99,
							'format'        => 'webp',
						),
					),
				);
			},
			static function ( string $hook, array $args, string $group ) use ( &$cancelled ): int {
				$cancelled[] = array(
					'hook'  => $hook,
					'args'  => $args,
					'group' => $group,
				);

				return 123;
			}
		);

		$result = $cleaner->cancel_pending_actions( 15 );

		self::assertTrue( $result->is_successful() );
		self::assertFalse( $result->has_warnings() );
		self::assertSame( 1, $result->cancelled_actions() );
		self::assertTrue( $result->has_code( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_CANCELLED ) );
		self::assertCount( 1, $cancelled );
		self::assertSame( 'hwlio_optimize_attachment_format', $cancelled[0]['hook'] );
		self::assertSame( 15, $cancelled[0]['args']['attachment_id'] );
		self::assertSame( 'hwlio', $cancelled[0]['group'] );
		self::assertCount( 2, $queried );
	}

	/**
	 * Test unavailable Action Scheduler becomes a warning and does not fatal.
	 *
	 * @return void
	 */
	public function test_unavailable_action_scheduler_returns_warning(): void {
		$cleaner = new ActionSchedulerAttachmentJobCleaner(
			'hwlio',
			array( 'hwlio_optimize_attachment_format' ),
			static function (): bool {
				return false;
			},
			static function ( array $query ): array {
				unset( $query );
				return array();
			},
			static function ( string $hook, array $args, string $group ): int {
				unset( $hook, $args, $group );
				return 0;
			}
		);

		$result = $cleaner->cancel_pending_actions( 15 );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_UNAVAILABLE ) );
		self::assertSame( 0, $result->cancelled_actions() );
	}
}
