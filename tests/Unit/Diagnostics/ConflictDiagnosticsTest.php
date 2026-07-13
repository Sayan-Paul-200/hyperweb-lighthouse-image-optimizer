<?php
/**
 * Tests for conflict diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\ConflictDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictRuntimeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Verifies compatibility warnings are exposed through structured diagnostics.
 */
final class ConflictDiagnosticsTest extends TestCase {

	/**
	 * Test overlap conflicts become warning diagnostics with safe details.
	 *
	 * @return void
	 */
	public function test_run_converts_overlap_conflicts_into_diagnostic_results(): void {
		$results = ( new ConflictDiagnostics( $this->detector( array( 'jetpack/jetpack.php' ) ) ) )->run();

		self::assertCount( 2, $results );
		self::assertSame( DiagnosticStatus::WARNING, $results[0]->status() );
		self::assertSame( 'overlap_lazy_loading', $results[0]->code() );
		self::assertSame( 'lazy_loading', $results[0]->details()['capability'] );
		self::assertSame( array( 'Jetpack' ), $results[0]->details()['evidence_plugins'] );
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
