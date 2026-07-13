<?php
/**
 * Tests for bounded log deletion.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogDeletionService;
use PHPUnit\Framework\TestCase;

/**
 * Verifies clear-all log deletion remains bounded and resumable.
 */
final class LogDeletionServiceTest extends TestCase {

	/**
	 * Test clear_all deletes one bounded batch and reports continuation state.
	 *
	 * @return void
	 */
	public function test_clear_all_reports_incomplete_when_full_batch_deleted(): void {
		$database                      = new FakeLogDatabase();
		$database->delete_batch_result = LogDeletionService::BATCH_SIZE;
		$service                       = new LogDeletionService( $database, 'wp_hwlio_logs' );

		$result = $service->clear_all()->to_array();

		self::assertSame( 'wp_hwlio_logs', $database->delete_table );
		self::assertSame( LogDeletionService::BATCH_SIZE, $database->delete_limit );
		self::assertFalse( $result['complete'] );
		self::assertSame( LogDeletionService::BATCH_SIZE, $result['deletedCount'] );
	}

	/**
	 * Test an empty or short batch completes safely.
	 *
	 * @return void
	 */
	public function test_clear_all_reports_complete_for_short_batch(): void {
		$database                      = new FakeLogDatabase();
		$database->delete_batch_result = 12;
		$service                       = new LogDeletionService( $database, 'wp_hwlio_logs' );

		$result = $service->clear_all()->to_array();

		self::assertTrue( $result['complete'] );
		self::assertSame( 12, $result['deletedCount'] );
	}
}
