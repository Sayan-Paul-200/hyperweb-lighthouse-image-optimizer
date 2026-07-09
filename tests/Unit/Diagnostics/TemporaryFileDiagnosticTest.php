<?php
/**
 * Tests for temporary file diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\TemporaryFileDiagnostic;
use PHPUnit\Framework\TestCase;

/**
 * Verifies temporary write/rename diagnostics.
 */
final class TemporaryFileDiagnosticTest extends TestCase {

	/**
	 * Test successful temporary write, rename, and cleanup.
	 *
	 * @return void
	 */
	public function test_run_passes_and_cleans_up_files(): void {
		$filesystem = new FakeDiagnosticFilesystem();
		$result     = ( new TemporaryFileDiagnostic( $filesystem, 'abc123' ) )->run( '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::PASS, $result->status() );
		self::assertSame( 'temporary_write_rename_succeeded', $result->code() );
		self::assertSame( array(), $filesystem->files );
		self::assertSame(
			array( '/tmp/uploads/hwlio-diagnostic-abc123.renamed.tmp' ),
			$filesystem->deleted
		);
	}

	/**
	 * Test write failure is reported safely.
	 *
	 * @return void
	 */
	public function test_run_reports_write_failure(): void {
		$filesystem              = new FakeDiagnosticFilesystem();
		$filesystem->write_fails = true;
		$result                  = ( new TemporaryFileDiagnostic( $filesystem, 'abc123' ) )->run( '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::FAIL, $result->status() );
		self::assertSame( 'temporary_write_failed', $result->code() );
		self::assertFalse( $result->details()['cleanup_failed'] );
		self::assertSame( array(), $filesystem->files );
	}

	/**
	 * Test rename failure cleans up the written source.
	 *
	 * @return void
	 */
	public function test_run_reports_rename_failure_and_cleans_source(): void {
		$filesystem               = new FakeDiagnosticFilesystem();
		$filesystem->rename_fails = true;
		$result                   = ( new TemporaryFileDiagnostic( $filesystem, 'abc123' ) )->run( '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::FAIL, $result->status() );
		self::assertSame( 'temporary_rename_failed', $result->code() );
		self::assertFalse( $result->details()['cleanup_failed'] );
		self::assertSame( array(), $filesystem->files );
		self::assertSame(
			array( '/tmp/uploads/hwlio-diagnostic-abc123.tmp' ),
			$filesystem->deleted
		);
	}

	/**
	 * Test cleanup failure is surfaced.
	 *
	 * @return void
	 */
	public function test_run_reports_cleanup_failure_without_exposing_path(): void {
		$filesystem               = new FakeDiagnosticFilesystem();
		$filesystem->delete_fails = true;
		$result                   = ( new TemporaryFileDiagnostic( $filesystem, 'abc123' ) )->run( '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 'temporary_cleanup_failed', $result->code() );
		self::assertTrue( $result->details()['cleanup_failed'] );
		self::assertStringNotContainsString( '/tmp/uploads', $result->message() );
	}

	/**
	 * Test missing uploads base fails before writing.
	 *
	 * @return void
	 */
	public function test_run_fails_when_uploads_base_cannot_be_resolved(): void {
		$filesystem = new FakeDiagnosticFilesystem( array() );
		$result     = ( new TemporaryFileDiagnostic( $filesystem, 'abc123' ) )->run( '/tmp/uploads' );

		self::assertSame( DiagnosticStatus::FAIL, $result->status() );
		self::assertSame( 'uploads_base_unavailable', $result->code() );
		self::assertSame( array(), $filesystem->written );
	}
}
