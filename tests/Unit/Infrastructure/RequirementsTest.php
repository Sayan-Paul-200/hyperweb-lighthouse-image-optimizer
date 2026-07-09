<?php
/**
 * Tests for bootstrap requirement checks.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Requirements;
use PHPUnit\Framework\TestCase;

/**
 * Verifies pure bootstrap requirement checks.
 */
final class RequirementsTest extends TestCase {

	/**
	 * Test PHP version comparisons.
	 *
	 * @return void
	 */
	public function test_php_version_support_detection(): void {
		self::assertTrue( Requirements::supports_php( '7.4.0', '7.4' ) );
		self::assertTrue( Requirements::supports_php( '8.1.25', '7.4' ) );
		self::assertFalse( Requirements::supports_php( '7.3.33', '7.4' ) );
	}

	/**
	 * Test WordPress version comparisons.
	 *
	 * @return void
	 */
	public function test_wordpress_version_support_detection(): void {
		self::assertTrue( Requirements::supports_wordpress( '6.5', '6.5' ) );
		self::assertTrue( Requirements::supports_wordpress( '6.8.2', '6.5' ) );
		self::assertFalse( Requirements::supports_wordpress( '6.4.5', '6.5' ) );
		self::assertFalse( Requirements::supports_wordpress( null, '6.5' ) );
	}

	/**
	 * Test missing runtime file detection.
	 *
	 * @return void
	 */
	public function test_missing_runtime_file_detection(): void {
		$missing = Requirements::missing_files(
			array(
				__FILE__                              => 'existing-test-file.php',
				__DIR__ . '/missing-runtime-file.php' => 'missing-runtime-file.php',
			)
		);

		self::assertSame( array( 'missing-runtime-file.php' ), $missing );
	}

	/**
	 * Test combined failure messages.
	 *
	 * @return void
	 */
	public function test_evaluate_returns_user_safe_failure_messages(): void {
		$failures = Requirements::evaluate(
			'7.3.33',
			'6.4.5',
			'7.4',
			'6.5',
			array(
				__DIR__ . '/missing-autoload.php' => 'vendor/autoload.php',
			)
		);

		self::assertCount( 3, $failures );
		self::assertStringContainsString( 'requires PHP 7.4 or higher', $failures[0] );
		self::assertStringContainsString( 'requires WordPress 6.5 or higher', $failures[1] );
		self::assertStringContainsString( 'vendor/autoload.php', $failures[2] );
		self::assertStringNotContainsString( __DIR__, implode( ' ', $failures ) );
	}
}
