<?php
/**
 * Tests for queue status values.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies queue-status behavior.
 */
final class QueueStatusTest extends TestCase {

	/**
	 * Test queued status is successful.
	 *
	 * @return void
	 */
	public function test_queued_status_is_successful(): void {
		$status = QueueStatus::queued( 55, true, null, array( 'Queued.' ) );

		self::assertTrue( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_QUEUED ) );
		self::assertSame( 55, $status->action_id() );
		self::assertTrue( $status->is_async() );
	}

	/**
	 * Test already-queued status is successful.
	 *
	 * @return void
	 */
	public function test_already_queued_status_is_successful(): void {
		$status = QueueStatus::already_queued( array( 'Already queued.' ) );

		self::assertTrue( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) );
		self::assertNull( $status->action_id() );
	}

	/**
	 * Test queue unavailable status is unsuccessful.
	 *
	 * @return void
	 */
	public function test_queue_unavailable_status_is_unsuccessful(): void {
		$status = QueueStatus::queue_unavailable( array( 'Unavailable.' ) );

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_QUEUE_UNAVAILABLE ) );
	}

	/**
	 * Test enqueue failed status is unsuccessful.
	 *
	 * @return void
	 */
	public function test_enqueue_failed_status_is_unsuccessful(): void {
		$status = QueueStatus::enqueue_failed( array( 'Failed.' ) );

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_ENQUEUE_FAILED ) );
	}

	/**
	 * Test invalid job payload status is unsuccessful.
	 *
	 * @return void
	 */
	public function test_invalid_job_payload_status_is_unsuccessful(): void {
		$status = QueueStatus::invalid_job_payload( array( 'Invalid.' ) );

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_INVALID_JOB_PAYLOAD ) );
	}

	/**
	 * Test queue status serialization is safe.
	 *
	 * @return void
	 */
	public function test_queue_status_serialization_is_safe(): void {
		$status = QueueStatus::queued( 66, false, 1234567890, array( 'Queued.' ) );

		self::assertSame(
			array(
				'successful'          => true,
				'action_id'           => 66,
				'async'               => false,
				'scheduled_timestamp' => 1234567890,
				'codes'               => array( QueueStatus::CODE_QUEUED ),
				'messages'            => array( 'Queued.' ),
			),
			$status->to_array()
		);
	}
}
