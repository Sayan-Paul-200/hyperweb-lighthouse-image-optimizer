<?php
/**
 * Tests for sample conversion diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionDiagnostic;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies sample conversion diagnostics.
 */
final class SampleConversionDiagnosticTest extends TestCase {

	/**
	 * Test successful sample conversion cleans source and output.
	 *
	 * @return void
	 */
	public function test_run_passes_and_cleans_up_source_and_output(): void {
		$filesystem = new FakeDiagnosticFilesystem();
		$probe      = new FakeSampleConversionProbe( SampleConversionResult::success(), $filesystem );
		$result     = ( new SampleConversionDiagnostic( $filesystem, $probe, 'abc123' ) )
			->run( FormatSupportResult::FORMAT_WEBP, '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::PASS, $result->status() );
		self::assertSame( 'sample_conversion_succeeded', $result->code() );
		self::assertSame( array(), $filesystem->files );
		self::assertSame( 'image/webp', $probe->calls[0]['mime_type'] );
		self::assertContains( '/tmp/uploads/hwlio-diagnostic-sample-abc123.png', $filesystem->deleted );
		self::assertContains( '/tmp/uploads/hwlio-diagnostic-sample-abc123.webp', $filesystem->deleted );
	}

	/**
	 * Test conversion failure cleans the source fixture.
	 *
	 * @return void
	 */
	public function test_run_reports_conversion_failure_and_cleans_source(): void {
		$filesystem = new FakeDiagnosticFilesystem();
		$probe      = new FakeSampleConversionProbe(
			SampleConversionResult::failure( 'editor_load_failed', 'The sample image could not be loaded.' ),
			$filesystem
		);
		$result     = ( new SampleConversionDiagnostic( $filesystem, $probe, 'abc123' ) )
			->run( FormatSupportResult::FORMAT_WEBP, '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::FAIL, $result->status() );
		self::assertSame( 'editor_load_failed', $result->code() );
		self::assertFalse( $result->details()['cleanup_failed'] );
		self::assertSame( array(), $filesystem->files );
		self::assertSame( array( '/tmp/uploads/hwlio-diagnostic-sample-abc123.png' ), $filesystem->deleted );
	}

	/**
	 * Test output validation failure is reported and cleaned.
	 *
	 * @return void
	 */
	public function test_run_reports_missing_output(): void {
		$filesystem          = new FakeDiagnosticFilesystem();
		$probe               = new FakeSampleConversionProbe( SampleConversionResult::success(), $filesystem );
		$probe->write_output = false;
		$result              = ( new SampleConversionDiagnostic( $filesystem, $probe, 'abc123' ) )
			->run( FormatSupportResult::FORMAT_AVIF, '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::FAIL, $result->status() );
		self::assertSame( 'output_validation_failed', $result->code() );
		self::assertSame( array(), $filesystem->files );
		self::assertSame( 'avif', $result->details()['format'] );
	}

	/**
	 * Test cleanup failure is surfaced without raw paths in message.
	 *
	 * @return void
	 */
	public function test_run_reports_cleanup_failure_without_exposing_path(): void {
		$filesystem               = new FakeDiagnosticFilesystem();
		$probe                    = new FakeSampleConversionProbe( SampleConversionResult::success(), $filesystem );
		$filesystem->delete_fails = true;
		$result                   = ( new SampleConversionDiagnostic( $filesystem, $probe, 'abc123' ) )
			->run( FormatSupportResult::FORMAT_WEBP, '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 'sample_conversion_cleanup_failed', $result->code() );
		self::assertTrue( $result->details()['cleanup_failed'] );
		self::assertStringNotContainsString( '/tmp/uploads', $result->message() );
	}
}
