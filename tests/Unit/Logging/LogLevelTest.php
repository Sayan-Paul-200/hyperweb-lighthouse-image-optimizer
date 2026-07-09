<?php
/**
 * Tests for log levels.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * Verifies level normalization.
 */
final class LogLevelTest extends TestCase {

	/**
	 * Test supported levels remain stable.
	 *
	 * @return void
	 */
	public function test_supported_levels_are_valid(): void {
		self::assertSame(
			array( 'debug', 'info', 'warning', 'error' ),
			LogLevel::all()
		);

		self::assertTrue( LogLevel::is_valid( 'debug' ) );
		self::assertTrue( LogLevel::is_valid( 'INFO' ) );
	}

	/**
	 * Test invalid levels normalize to error.
	 *
	 * @return void
	 */
	public function test_invalid_level_normalizes_to_error(): void {
		self::assertSame( LogLevel::ERROR, LogLevel::normalize( 'notice' ) );
		self::assertSame( LogLevel::ERROR, LogLevel::normalize( '' ) );
	}
}
