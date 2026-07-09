<?php
/**
 * Tests for log codes.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use PHPUnit\Framework\TestCase;

/**
 * Verifies stable code validation.
 */
final class LogCodeTest extends TestCase {

	/**
	 * Test stable machine-readable codes are valid.
	 *
	 * @return void
	 */
	public function test_stable_codes_are_valid(): void {
		self::assertTrue( LogCode::is_valid( 'optimized' ) );
		self::assertTrue( LogCode::is_valid( 'skipped_unsupported_source_mime' ) );
		self::assertTrue( LogCode::is_valid( 'metadata_write_failed' ) );
		self::assertSame( 'conversion_failed', LogCode::normalize( 'CONVERSION_FAILED' ) );
	}

	/**
	 * Test invalid codes normalize to unknown.
	 *
	 * @return void
	 */
	public function test_invalid_codes_normalize_to_unknown(): void {
		self::assertFalse( LogCode::is_valid( 'Invalid Code!' ) );
		self::assertSame( LogCode::UNKNOWN, LogCode::normalize( 'Invalid Code!' ) );
		self::assertSame( LogCode::UNKNOWN, LogCode::normalize( '' ) );
	}
}
