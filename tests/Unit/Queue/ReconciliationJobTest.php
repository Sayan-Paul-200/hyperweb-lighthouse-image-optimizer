<?php
/**
 * Reconciliation job tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationJob;
use PHPUnit\Framework\TestCase;

/**
 * Verifies reconciliation queue payload behavior.
 */
final class ReconciliationJobTest extends TestCase {

	/**
	 * Test valid payload normalization and serialization.
	 *
	 * @return void
	 */
	public function test_valid_payload_round_trips(): void {
		$job = new ReconciliationJob( 15, str_repeat( 'a', 20 ), 'metadata_update' );

		self::assertTrue( $job->is_valid() );
		self::assertSame(
			$job->to_array(),
			ReconciliationJob::from_array( $job->to_array() )->to_array()
		);
	}

	/**
	 * Test invalid fingerprint makes payload invalid.
	 *
	 * @return void
	 */
	public function test_invalid_fingerprint_is_rejected(): void {
		$job = new ReconciliationJob( 15, 'bad-fingerprint', 'metadata_update' );

		self::assertFalse( $job->is_valid() );
		self::assertNull(
			ReconciliationJob::from_array(
				array(
					'attachment_id' => 15,
					'fingerprint'   => 'bad-fingerprint',
				)
			)
		);
	}

	/**
	 * Test equivalence ignores reason.
	 *
	 * @return void
	 */
	public function test_equivalence_ignores_reason(): void {
		$left  = new ReconciliationJob( 88, str_repeat( 'b', 20 ), 'metadata_update' );
		$right = new ReconciliationJob( 88, str_repeat( 'b', 20 ), 'source_changed' );

		self::assertTrue( $left->equivalent_to( $right ) );
	}
}
