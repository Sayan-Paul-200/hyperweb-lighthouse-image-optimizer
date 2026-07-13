<?php
/**
 * Tests for recent failure log summaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;
use PHPUnit\Framework\TestCase;

/**
 * Verifies bounded read-only log summaries for the dashboard.
 */
final class RecentFailureLogReaderTest extends TestCase {

	/**
	 * Test the reader returns only safe summary fields.
	 *
	 * @return void
	 */
	public function test_read_returns_bounded_safe_failure_summaries(): void {
		$database       = new FakeLogReadDatabase();
		$database->rows = array(
			array(
				'created_at_gmt' => '2026-07-12 09:00:00',
				'level'          => 'warning',
				'code'           => 'maintenance_statistics_reconcile_failed',
				'message'        => 'Statistics reconciliation could not be saved.',
				'attachment_id'  => 45,
				'context_json'   => '{"path":"D:\\\\secret.txt"}',
			),
		);
		$reader         = new RecentFailureLogReader( $database, 'wp_hwlio_logs', 3 );

		$entries = $reader->read();

		self::assertCount( 1, $entries );
		self::assertSame( 'wp_hwlio_logs', $database->calls[0]['table'] );
		self::assertSame( 3, $database->calls[0]['limit'] );
		self::assertSame( '2026-07-12 09:00:00', $entries[0]['created_at_gmt'] );
		self::assertSame( 'warning', $entries[0]['level'] );
		self::assertSame( 'maintenance_statistics_reconcile_failed', $entries[0]['code'] );
		self::assertSame( 'Statistics reconciliation could not be saved.', $entries[0]['message'] );
		self::assertSame( 45, $entries[0]['attachment_id'] );
		self::assertArrayNotHasKey( 'context_json', $entries[0] );
	}

	/**
	 * Test invalid attachment IDs are normalized away.
	 *
	 * @return void
	 */
	public function test_read_normalizes_invalid_attachment_ids_to_null(): void {
		$database       = new FakeLogReadDatabase();
		$database->rows = array(
			array(
				'created_at_gmt' => '2026-07-12 09:00:00',
				'level'          => 'error',
				'code'           => 'unknown',
				'message'        => 'Unexpected failure.',
				'attachment_id'  => 0,
			),
		);
		$reader         = new RecentFailureLogReader( $database, 'wp_hwlio_logs' );

		$entries = $reader->read();

		self::assertNull( $entries[0]['attachment_id'] );
	}
}
