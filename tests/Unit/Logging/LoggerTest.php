<?php
/**
 * Tests for the logger.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Logging\Logger;
use HyperWeb\LighthouseImageOptimizer\Logging\LogLevel;
use HyperWeb\LighthouseImageOptimizer\Logging\LogSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies logger behavior.
 */
final class LoggerTest extends TestCase {

	/**
	 * Test entries are normalized and sanitized before write.
	 *
	 * @return void
	 */
	public function test_log_normalizes_and_sanitizes_before_write(): void {
		$writer = new FakeLogWriter();
		$logger = new Logger(
			$writer,
			new LogSanitizer(),
			static function (): string {
				return '2026-07-09 00:00:00';
			}
		);

		$result = $logger->log(
			'notice',
			'Bad Code!',
			'Failed at /var/www/site/file.jpg',
			array( 'nonce' => 'abc' ),
			123,
			'job-1'
		);

		self::assertTrue( $result );
		self::assertNotNull( $writer->entry );
		self::assertSame( '2026-07-09 00:00:00', $writer->entry->created_at_gmt() );
		self::assertSame( LogLevel::ERROR, $writer->entry->level() );
		self::assertSame( LogCode::UNKNOWN, $writer->entry->code() );
		self::assertStringContainsString( LogSanitizer::REDACTED_PATH, $writer->entry->message() );
		self::assertSame( LogSanitizer::REDACTED, $writer->entry->context()['nonce'] );
		self::assertSame( 123, $writer->entry->attachment_id() );
		self::assertSame( 'job-1', $writer->entry->job_id() );
	}

	/**
	 * Test convenience methods use expected levels.
	 *
	 * @return void
	 */
	public function test_convenience_methods_wrap_levels(): void {
		$writer = new FakeLogWriter();
		$logger = new Logger( $writer, new LogSanitizer() );

		self::assertTrue( $logger->warning( 'metadata_write_failed', 'Unable to update metadata.' ) );
		self::assertNotNull( $writer->entry );
		self::assertSame( LogLevel::WARNING, $writer->entry->level() );
		self::assertSame( 'metadata_write_failed', $writer->entry->code() );
	}

	/**
	 * Test writer failure returns false and does not throw.
	 *
	 * @return void
	 */
	public function test_writer_failure_returns_false(): void {
		$writer         = new FakeLogWriter();
		$writer->throw  = true;
		$writer->result = false;
		$logger         = new Logger( $writer, new LogSanitizer() );

		self::assertFalse( $logger->error( 'log_write_failed', 'Unable to write log.' ) );
	}
}
