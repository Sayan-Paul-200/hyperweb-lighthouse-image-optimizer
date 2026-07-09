<?php
/**
 * Tests for log sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogEntry;
use HyperWeb\LighthouseImageOptimizer\Logging\LogSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies redaction and bounding before storage.
 */
final class LogSanitizerTest extends TestCase {

	/**
	 * Test sensitive context keys are redacted.
	 *
	 * @return void
	 */
	public function test_sensitive_context_keys_are_redacted(): void {
		$sanitizer = new LogSanitizer();

		$context = $sanitizer->sanitize_context(
			array(
				'password' => 'plain-text',
				'api_key'  => 'abc123',
				'nested'   => array(
					'auth_token' => 'secret-token',
					'safe'       => 'visible',
				),
			)
		);

		self::assertSame( LogSanitizer::REDACTED, $context['password'] );
		self::assertSame( LogSanitizer::REDACTED, $context['api_key'] );
		self::assertSame( LogSanitizer::REDACTED, $context['nested']['auth_token'] );
		self::assertSame( 'visible', $context['nested']['safe'] );
	}

	/**
	 * Test absolute paths are redacted from messages and context.
	 *
	 * @return void
	 */
	public function test_absolute_paths_are_redacted(): void {
		$sanitizer = new LogSanitizer();
		$entry     = new LogEntry(
			'2026-07-09 00:00:00',
			'info',
			'optimized',
			'Failed at /var/www/site/wp-content/uploads/2026/hero.jpg and C:\\inetpub\\wwwroot\\hero.jpg',
			array(
				'unix'    => '/var/www/site/wp-content/uploads/2026/hero.jpg',
				'windows' => 'C:\\inetpub\\wwwroot\\hero.jpg',
			)
		);

		$sanitized = $sanitizer->sanitize_entry( $entry );

		self::assertStringContainsString( LogSanitizer::REDACTED_PATH, $sanitized->message() );
		self::assertStringNotContainsString( '/var/www/site', $sanitized->message() );
		self::assertStringNotContainsString( 'C:\\inetpub', $sanitized->message() );
		self::assertSame( LogSanitizer::REDACTED_PATH, $sanitized->context()['unix'] );
		self::assertSame( LogSanitizer::REDACTED_PATH, $sanitized->context()['windows'] );
	}

	/**
	 * Test context size is bounded.
	 *
	 * @return void
	 */
	public function test_context_is_bounded_and_marked_when_truncated(): void {
		$sanitizer = new LogSanitizer();
		$context   = array();

		for ( $i = 0; $i < 60; ++$i ) {
			$context[ 'item_' . $i ] = 'value';
		}

		$sanitized = $sanitizer->sanitize_context( $context );

		self::assertTrue( $sanitized[ LogSanitizer::TRUNCATED_KEY ] );
		self::assertLessThanOrEqual( 51, count( $sanitized ) );
		self::assertSame( 'value', $sanitized['item_0'] );
	}

	/**
	 * Test long strings are truncated.
	 *
	 * @return void
	 */
	public function test_long_context_strings_are_truncated(): void {
		$sanitizer = new LogSanitizer();
		$sanitized = $sanitizer->sanitize_context(
			array(
				'long' => str_repeat( 'x', 700 ),
			)
		);

		self::assertStringContainsString( '[truncated]', $sanitized['long'] );
	}
}
