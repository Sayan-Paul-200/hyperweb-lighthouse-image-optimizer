<?php
/**
 * Tests for optimization jobs.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationJob;
use PHPUnit\Framework\TestCase;

/**
 * Verifies optimization-job normalization and equivalence.
 */
final class OptimizationJobTest extends TestCase {

	/**
	 * Test valid payload normalization.
	 *
	 * @return void
	 */
	public function test_valid_payload_is_normalized(): void {
		$job = new OptimizationJob(
			5,
			' WEBP ',
			3,
			true,
			'New Upload',
			'ABCDEF1234567890ABCD'
		);

		self::assertTrue( $job->is_valid() );
		self::assertSame( 5, $job->attachment_id() );
		self::assertSame( 'webp', $job->format() );
		self::assertSame( 3, $job->cursor() );
		self::assertTrue( $job->force() );
		self::assertSame( 'new_upload', $job->reason() );
		self::assertSame( 'abcdef1234567890abcd', $job->fingerprint() );
	}

	/**
	 * Test invalid attachment IDs produce invalid jobs.
	 *
	 * @return void
	 */
	public function test_invalid_attachment_id_produces_invalid_job(): void {
		$job = new OptimizationJob( 0, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) );

		self::assertFalse( $job->is_valid() );
	}

	/**
	 * Test invalid formats produce invalid jobs.
	 *
	 * @return void
	 */
	public function test_invalid_format_produces_invalid_job(): void {
		$job = new OptimizationJob( 5, 'jpeg', 0, false, 'manual', str_repeat( 'a', 20 ) );

		self::assertFalse( $job->is_valid() );
	}

	/**
	 * Test invalid fingerprints produce invalid jobs.
	 *
	 * @return void
	 */
	public function test_invalid_fingerprint_produces_invalid_job(): void {
		$job = new OptimizationJob( 5, 'webp', 0, false, 'manual', 'not-a-signature' );

		self::assertFalse( $job->is_valid() );
	}

	/**
	 * Test array round trip.
	 *
	 * @return void
	 */
	public function test_from_array_round_trip(): void {
		$payload = array(
			'attachment_id' => 12,
			'format'        => 'avif',
			'cursor'        => 7,
			'force'         => true,
			'reason'        => 'retry',
			'fingerprint'   => str_repeat( 'b', 20 ),
		);

		$job = OptimizationJob::from_array( $payload );

		self::assertInstanceOf( OptimizationJob::class, $job );
		self::assertSame( $payload, $job->to_array() );
	}

	/**
	 * Test callback arg reconstruction.
	 *
	 * @return void
	 */
	public function test_from_callback_args_round_trip(): void {
		$job = OptimizationJob::from_callback_args(
			12,
			'avif',
			7,
			true,
			'retry',
			str_repeat( 'b', 20 )
		);

		self::assertInstanceOf( OptimizationJob::class, $job );
		self::assertSame(
			array(
				'attachment_id' => 12,
				'format'        => 'avif',
				'cursor'        => 7,
				'force'         => true,
				'reason'        => 'retry',
				'fingerprint'   => str_repeat( 'b', 20 ),
			),
			$job->to_array()
		);
	}

	/**
	 * Test equivalence ignores reason.
	 *
	 * @return void
	 */
	public function test_equivalence_ignores_reason(): void {
		$left  = new OptimizationJob( 14, 'webp', 1, false, 'new_upload', str_repeat( 'c', 20 ) );
		$right = new OptimizationJob( 14, 'webp', 1, false, 'retry', str_repeat( 'c', 20 ) );

		self::assertTrue( $left->equivalent_to( $right ) );
	}

	/**
	 * Test equivalence changes when identity fields change.
	 *
	 * @return void
	 */
	public function test_equivalence_changes_when_identity_fields_change(): void {
		$job = new OptimizationJob( 14, 'webp', 1, false, 'new_upload', str_repeat( 'c', 20 ) );

		self::assertFalse(
			$job->equivalent_to(
				new OptimizationJob( 14, 'webp', 2, false, 'new_upload', str_repeat( 'c', 20 ) )
			)
		);
		self::assertFalse(
			$job->equivalent_to(
				new OptimizationJob( 14, 'webp', 1, true, 'new_upload', str_repeat( 'c', 20 ) )
			)
		);
		self::assertFalse(
			$job->equivalent_to(
				new OptimizationJob( 14, 'webp', 1, false, 'new_upload', str_repeat( 'd', 20 ) )
			)
		);
	}
}
