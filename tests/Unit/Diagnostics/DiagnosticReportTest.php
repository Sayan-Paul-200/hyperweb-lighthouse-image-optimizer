<?php
/**
 * Tests for diagnostic reports.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticReport;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies diagnostic report behavior.
 */
final class DiagnosticReportTest extends TestCase {

	/**
	 * Test summary counts.
	 *
	 * @return void
	 */
	public function test_summary_counts_statuses(): void {
		$report = new DiagnosticReport(
			array(
				new DiagnosticResult( 'a', DiagnosticStatus::PASS, 'ok', 'A', 'A' ),
				new DiagnosticResult( 'b', DiagnosticStatus::WARNING, 'warn', 'B', 'B' ),
				new DiagnosticResult( 'c', DiagnosticStatus::FAIL, 'fail', 'C', 'C' ),
				new DiagnosticResult( 'd', DiagnosticStatus::INFO, 'info', 'D', 'D' ),
			)
		);

		self::assertSame(
			array(
				'total'   => 4,
				'pass'    => 1,
				'warning' => 1,
				'fail'    => 1,
				'info'    => 1,
			),
			$report->summary()
		);
	}

	/**
	 * Test serialization.
	 *
	 * @return void
	 */
	public function test_to_array_serializes_results_and_summary(): void {
		$report = new DiagnosticReport(
			array(
				new DiagnosticResult( 'a', DiagnosticStatus::PASS, 'ok', 'A', 'A' ),
			)
		);

		$array = $report->to_array();

		self::assertSame( 1, $array['summary']['total'] );
		self::assertSame( 'a', $array['results'][0]['id'] );
		self::assertSame( 'pass', $array['results'][0]['status'] );
	}
}
