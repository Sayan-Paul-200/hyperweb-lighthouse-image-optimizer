<?php
/**
 * Tests for dashboard environment summaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies lightweight environment and conflict summaries for the dashboard.
 */
final class DashboardEnvironmentSummaryServiceTest extends TestCase {

	/**
	 * Test a healthy environment builds a usable summary without conflicts.
	 *
	 * @return void
	 */
	public function test_build_returns_environment_summary_without_conflicts_in_healthy_state(): void {
		$probe   = new FakeEnvironmentProbe();
		$service = new DashboardEnvironmentSummaryService(
			new EnvironmentInspector( $probe, '7.4', '6.5' ),
			new FakeSettingsRepository(
				array(
					'automatic_optimization' => true,
					'delivery_enabled'       => true,
					'enabled_formats'        => array( 'webp', 'avif' ),
				)
			)
		);

		$summary = $service->build();

		self::assertTrue( $summary['environment']['automatic_optimization'] );
		self::assertTrue( $summary['environment']['delivery_enabled'] );
		self::assertSame( 'supported', $summary['environment']['formats']['webp']['status'] );
		self::assertSame( array(), $summary['conflicts'] );
	}

	/**
	 * Test enabled unsupported formats and queue readiness become conflicts.
	 *
	 * @return void
	 */
	public function test_build_returns_conservative_conflicts_for_enabled_unsupported_formats(): void {
		$probe                               = new FakeEnvironmentProbe();
		$probe->mime_support['image/avif']   = false;
		$probe->action_scheduler_initialized = false;
		$service                             = new DashboardEnvironmentSummaryService(
			new EnvironmentInspector( $probe, '7.4', '6.5' ),
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'avif' ),
				)
			)
		);

		$summary   = $service->build();
		$conflicts = $summary['conflicts'];

		self::assertSame( 'unsupported', $summary['environment']['formats']['avif']['status'] );
		self::assertCount( 2, $conflicts );
		self::assertSame( 'avif_encoding_not_supported', $conflicts[0]['code'] );
		self::assertSame( 'action_scheduler_not_initialized', $conflicts[1]['code'] );
	}
}
