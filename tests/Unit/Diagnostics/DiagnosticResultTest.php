<?php
/**
 * Tests for diagnostic results.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies diagnostic result behavior.
 */
final class DiagnosticResultTest extends TestCase {

	/**
	 * Test status and key normalization.
	 *
	 * @return void
	 */
	public function test_normalizes_status_and_keys(): void {
		$result = new DiagnosticResult(
			'PHP Version!',
			'not-valid',
			'Bad Code!',
			'PHP version',
			'Message.',
			array( 'raw' => 'value' )
		);

		self::assertSame( 'php_version', $result->id() );
		self::assertSame( DiagnosticStatus::INFO, $result->status() );
		self::assertSame( 'bad_code', $result->code() );
	}

	/**
	 * Test serialization shape.
	 *
	 * @return void
	 */
	public function test_to_array_returns_rest_ready_shape(): void {
		$result = new DiagnosticResult(
			'php_version',
			DiagnosticStatus::PASS,
			'php_version_supported',
			'PHP version',
			'PHP meets the minimum version.',
			array( 'current' => '8.1' )
		);

		self::assertSame(
			array(
				'id'      => 'php_version',
				'status'  => 'pass',
				'code'    => 'php_version_supported',
				'label'   => 'PHP version',
				'message' => 'PHP meets the minimum version.',
				'details' => array( 'current' => '8.1' ),
			),
			$result->to_array()
		);
	}
}
