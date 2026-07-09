<?php
/**
 * Tests for diagnostic sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticSanitizer;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies diagnostic output sanitization.
 */
final class DiagnosticSanitizerTest extends TestCase {

	/**
	 * Test paths and sensitive values are redacted.
	 *
	 * @return void
	 */
	public function test_redacts_paths_and_sensitive_details(): void {
		$sanitizer = new DiagnosticSanitizer();
		$result    = $sanitizer->sanitize_result(
			new DiagnosticResult(
				'upload_path',
				DiagnosticStatus::WARNING,
				'path_warning',
				'Upload path',
				'Path is D:\Sites\wp-content\uploads and /var/www/uploads.',
				array(
					'basedir'       => '/var/www/uploads',
					'Authorization' => 'Bearer secret',
					'nested'        => array(
						'api_key' => 'secret-key',
						'file'    => 'C:\Sites\file.jpg',
					),
				)
			)
		);

		self::assertStringNotContainsString( 'D:\Sites', $result->message() );
		self::assertStringNotContainsString( '/var/www', $result->message() );
		self::assertSame( DiagnosticSanitizer::REDACTED_PATH, $result->details()['basedir'] );
		self::assertSame( DiagnosticSanitizer::REDACTED, $result->details()['authorization'] );
		self::assertSame( DiagnosticSanitizer::REDACTED, $result->details()['nested']['api_key'] );
		self::assertSame( DiagnosticSanitizer::REDACTED_PATH, $result->details()['nested']['file'] );
	}
}
