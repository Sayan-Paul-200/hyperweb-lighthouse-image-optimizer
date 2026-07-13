<?php
/**
 * Tests for dashboard environment summaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictRuntimeInterface;
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
			),
			$this->detector()
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
			),
			$this->detector()
		);

		$summary   = $service->build();
		$conflicts = $summary['conflicts'];

		self::assertSame( 'unsupported', $summary['environment']['formats']['avif']['status'] );
		self::assertCount( 2, $conflicts );
		self::assertSame( 'avif_encoding_not_supported', $conflicts[0]['code'] );
		self::assertSame( 'action_scheduler_not_initialized', $conflicts[1]['code'] );
	}

	/**
	 * Test overlap conflicts are merged into the existing dashboard conflict payload.
	 *
	 * @return void
	 */
	public function test_build_merges_overlap_conflicts_into_dashboard_conflicts(): void {
		$service = new DashboardEnvironmentSummaryService(
			new EnvironmentInspector( new FakeEnvironmentProbe(), '7.4', '6.5' ),
			new FakeSettingsRepository(),
			$this->detector(
				array(
					'shortpixel-adaptive-images/shortpixel-adaptive-images.php',
					'jetpack/jetpack.php',
				)
			)
		);

		$summary   = $service->build();
		$conflicts = $summary['conflicts'];

		self::assertCount( 3, $conflicts );
		self::assertSame( 'overlap_delivery', $conflicts[0]['code'] );
		self::assertSame( 'delivery', $conflicts[0]['capability'] );
		self::assertSame( array( 'ShortPixel Adaptive Images' ), $conflicts[0]['evidence_plugins'] );
		self::assertSame( 'overlap_lazy_loading', $conflicts[1]['code'] );
		self::assertSame( 'overlap_cdn_transformation', $conflicts[2]['code'] );
	}

	/**
	 * Build a detector around fake active-plugin state.
	 *
	 * @param string[] $active_plugins Active plugins.
	 * @return ConflictDetector
	 */
	private function detector( array $active_plugins = array() ): ConflictDetector {
		return new ConflictDetector(
			new class( $active_plugins ) implements ConflictRuntimeInterface {
				/**
				 * Active plugins.
				 *
				 * @var string[]
				 */
				private $active_plugins;

				/**
				 * Create runtime.
				 *
				 * @param string[] $active_plugins Active plugins.
				 */
				public function __construct( array $active_plugins ) {
					$this->active_plugins = $active_plugins;
				}

				/**
				 * Get active plugins.
				 *
				 * @return string[]
				 */
				public function active_plugin_basenames(): array {
					return $this->active_plugins;
				}

				/**
				 * Get network-active plugins.
				 *
				 * @return string[]
				 */
				public function network_active_plugin_basenames(): array {
					return array();
				}

				/**
				 * Whether multisite is active.
				 *
				 * @return bool
				 */
				public function is_multisite(): bool {
					return false;
				}
			}
		);
	}
}
