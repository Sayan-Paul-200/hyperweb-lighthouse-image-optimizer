<?php
/**
 * Tests for the composite diagnostics service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\CompositeDiagnosticsService;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\ConflictDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticSanitizer;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\EnvironmentDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionDiagnostic;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\TemporaryFileDiagnostic;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics\FakeDiagnosticFilesystem;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics\FakeSampleConversionProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies composite diagnostics include environment, conflict, and derivative checks.
 */
final class CompositeDiagnosticsServiceTest extends TestCase {

	/**
	 * Test the composite report includes compatibility conflict diagnostics.
	 *
	 * @return void
	 */
	public function test_report_includes_conflict_diagnostics(): void {
		$service = new CompositeDiagnosticsService(
			$this->environment_diagnostics(),
			$this->derivative_diagnostics(),
			new ConflictDiagnostics( $this->detector( array( 'jetpack/jetpack.php' ) ) )
		);

		$report = $service->report();
		$ids    = array_column( $report['results'], 'id' );

		self::assertContains( 'php_version', $ids );
		self::assertContains( 'delivery_derivative_files', $ids );
		self::assertContains( 'conflict_lazy_loading', $ids );
		self::assertContains( 'conflict_cdn_transformation', $ids );
	}

	/**
	 * Build environment diagnostics with fake dependencies.
	 *
	 * @return EnvironmentDiagnostics
	 */
	private function environment_diagnostics(): EnvironmentDiagnostics {
		$filesystem = new FakeDiagnosticFilesystem();

		return new EnvironmentDiagnostics(
			new EnvironmentInspector( new FakeEnvironmentProbe(), '7.4', '6.5' ),
			new FakeSettingsRepository(),
			new DiagnosticSanitizer(),
			new TemporaryFileDiagnostic( $filesystem, 'diag123' ),
			new SampleConversionDiagnostic(
				$filesystem,
				new FakeSampleConversionProbe( SampleConversionResult::success(), $filesystem ),
				'diag123'
			)
		);
	}

	/**
	 * Build derivative diagnostics with fake dependencies.
	 *
	 * @return DerivativeHealthDiagnostics
	 */
	private function derivative_diagnostics(): DerivativeHealthDiagnostics {
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_dir = 'C:/site/wp-content/uploads';

		return new DerivativeHealthDiagnostics(
			new class() implements DerivativeHealthRuntimeInterface {
				/**
				 * Read attachment IDs after one cursor.
				 *
				 * @param int $after_id Cursor.
				 * @param int $limit Limit.
				 * @return int[]
				 */
				public function attachment_ids_after( int $after_id, int $limit ): array {
					unset( $after_id, $limit );

					return array();
				}
			},
			new DerivativeRepository( new FakeAttachmentMetaStore(), new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			$uploads,
			new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) ),
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Build a detector with fake active plugins.
	 *
	 * @param string[] $active_plugins Active plugins.
	 * @return ConflictDetector
	 */
	private function detector( array $active_plugins ): ConflictDetector {
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
