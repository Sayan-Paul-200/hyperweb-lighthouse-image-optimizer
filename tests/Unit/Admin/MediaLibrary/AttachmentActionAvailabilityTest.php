<?php
/**
 * Tests for Media Library action availability.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentActionAvailability;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Media Library action mapping by lightweight attachment state.
 */
final class AttachmentActionAvailabilityTest extends TestCase {

	/**
	 * Test every important state maps to the expected action set.
	 *
	 * @return void
	 */
	public function test_actions_follow_the_media_library_state_matrix(): void {
		$availability = new AttachmentActionAvailability();

		self::assertSame(
			array( 'optimize', 'exclude', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_UNPROCESSED ), true, true )
		);
		self::assertSame(
			array( 'exclude', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_QUEUED ), true, true )
		);
		self::assertSame(
			array( 'retry', 'reoptimize', 'reconcile', 'exclude', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_FAILED ), true, true )
		);
		self::assertSame(
			array( 'reoptimize', 'reconcile', 'exclude', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_OPTIMIZED ), true, true )
		);
		self::assertSame(
			array( 'include', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array(), 0, null, true ), true, true )
		);
	}

	/**
	 * Test exclusion-disabled settings remove include/exclude actions.
	 *
	 * @return void
	 */
	public function test_exclusion_disabled_removes_exclusion_actions(): void {
		$availability = new AttachmentActionAvailability();

		self::assertSame(
			array( 'optimize', 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_UNPROCESSED ), true, false )
		);
		self::assertSame(
			array( 'view-details' ),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array(), 0, null, true ), true, false )
		);
	}

	/**
	 * Test unauthorized attachments expose no action triggers.
	 *
	 * @return void
	 */
	public function test_unauthorized_attachments_expose_no_action_triggers(): void {
		$availability = new AttachmentActionAvailability();

		self::assertSame(
			array(),
			$availability->actions_for( new AttachmentStatus( AttachmentStatus::STATE_PARTIAL ), false, true )
		);
	}
}
