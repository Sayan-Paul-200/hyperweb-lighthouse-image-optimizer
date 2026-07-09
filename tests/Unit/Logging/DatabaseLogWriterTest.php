<?php
/**
 * Tests for database log writer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\DatabaseLogWriter;
use HyperWeb\LighthouseImageOptimizer\Logging\LogEntry;
use PHPUnit\Framework\TestCase;

/**
 * Verifies database writer shape and safe failure behavior.
 */
final class DatabaseLogWriterTest extends TestCase {

	/**
	 * Test writer sends the expected row shape to the database adapter.
	 *
	 * @return void
	 */
	public function test_write_uses_expected_table_data_and_formats(): void {
		$database = new FakeLogDatabase();
		$writer   = new DatabaseLogWriter( $database, 'wp_hwlio_logs' );
		$entry    = new LogEntry(
			'2026-07-09 00:00:00',
			'error',
			'metadata_write_failed',
			'Unable to update metadata.',
			array( 'attachment' => 123 ),
			123,
			'job-1'
		);

		self::assertTrue( $writer->write( $entry ) );
		self::assertSame( 'wp_hwlio_logs', $database->insert_table );
		self::assertSame(
			array(
				'created_at_gmt' => '2026-07-09 00:00:00',
				'level'          => 'error',
				'code'           => 'metadata_write_failed',
				'message'        => 'Unable to update metadata.',
				'attachment_id'  => 123,
				'job_id'         => 'job-1',
				'context_json'   => '{"attachment":123}',
			),
			$database->insert_data
		);
		self::assertSame(
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
			$database->insert_formats
		);
	}

	/**
	 * Test unsafe table names do not reach the database adapter.
	 *
	 * @return void
	 */
	public function test_unsafe_table_name_returns_false_before_insert(): void {
		$database = new FakeLogDatabase();
		$writer   = new DatabaseLogWriter( $database, 'wp_hwlio_logs; DROP TABLE wp_posts' );
		$entry    = new LogEntry( '2026-07-09 00:00:00', 'error', 'unknown', 'Nope.' );

		self::assertFalse( $writer->write( $entry ) );
		self::assertNull( $database->insert_table );
	}
}
