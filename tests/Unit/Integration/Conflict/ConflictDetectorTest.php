<?php
/**
 * Tests for the conflict detector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Conflict;

use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictRuntimeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Verifies capability-first overlap detection from current-site plugin signatures.
 */
final class ConflictDetectorTest extends TestCase {

	/**
	 * Test no active overlap plugins returns an empty report.
	 *
	 * @return void
	 */
	public function test_no_active_overlap_plugins_returns_empty_report(): void {
		$report = $this->detector()->detect();

		self::assertTrue( $report->is_empty() );
		self::assertSame( array(), $report->to_array() );
	}

	/**
	 * Test multiple matching plugins in one capability are aggregated into one result.
	 *
	 * @return void
	 */
	public function test_multiple_matching_plugins_in_one_capability_are_aggregated(): void {
		$result = $this->detector(
			array(
				'a3-lazy-load/a3-lazy-load.php',
				'jetpack/jetpack.php',
			)
		)->detect()->results()[0];

		self::assertSame( 'overlap_lazy_loading', $result->code() );
		self::assertSame( 'lazy_loading', $result->capability() );
		self::assertSame(
			array( 'a3 Lazy Load', 'Jetpack' ),
			$result->evidence_plugins()
		);
		self::assertSame(
			array(
				'loading_attribute_overrides_enabled',
				'responsive_preload_enabled',
				'critical_background_preload_enabled',
			),
			$result->setting_keys()
		);
	}

	/**
	 * Test one plugin spanning multiple capabilities produces separate capability results.
	 *
	 * @return void
	 */
	public function test_one_plugin_spanning_multiple_capabilities_produces_separate_results(): void {
		$results = $this->detector(
			array(
				'optimole-wp/optimole-wp.php',
			)
		)->detect()->to_array();

		self::assertCount( 3, $results );
		self::assertSame(
			array(
				'overlap_generation',
				'overlap_delivery',
				'overlap_cdn_transformation',
			),
			array_column( $results, 'code' )
		);
	}

	/**
	 * Test warning payload identifies the overlapping capability first.
	 *
	 * @return void
	 */
	public function test_warning_payload_is_capability_first(): void {
		$result = $this->detector(
			array(
				'media-cloud/media-cloud.php',
			)
		)->detect()->results()[0];

		self::assertStringContainsString( 'media offload', strtolower( $result->label() ) );
		self::assertStringContainsString( 'media offload', strtolower( $result->message() ) );
		self::assertSame( array( 'Media Cloud' ), $result->evidence_plugins() );
	}

	/**
	 * Test multisite detection includes network-active plugins without extra site scanning.
	 *
	 * @return void
	 */
	public function test_multisite_detection_includes_network_active_plugins(): void {
		$results = $this->detector(
			array(),
			array(
				'wp-stateless/wp-stateless.php',
			),
			true
		)->detect()->results();

		self::assertCount( 1, $results );
		self::assertSame( 'overlap_media_offload', $results[0]->code() );
		self::assertSame( array( 'WP Stateless' ), $results[0]->evidence_plugins() );
	}

	/**
	 * Build a detector around fake runtime state.
	 *
	 * @param string[] $active_plugins Active plugins.
	 * @param string[] $network_plugins Network-active plugins.
	 * @param bool     $multisite Whether multisite is active.
	 * @return ConflictDetector
	 */
	private function detector(
		array $active_plugins = array(),
		array $network_plugins = array(),
		bool $multisite = false
	): ConflictDetector {
		return new ConflictDetector(
			new class( $active_plugins, $network_plugins, $multisite ) implements ConflictRuntimeInterface {
				/**
				 * Active plugins.
				 *
				 * @var string[]
				 */
				private $active_plugins;

				/**
				 * Network-active plugins.
				 *
				 * @var string[]
				 */
				private $network_plugins;

				/**
				 * Multisite state.
				 *
				 * @var bool
				 */
				private $multisite;

				/**
				 * Create runtime.
				 *
				 * @param string[] $active_plugins Active plugins.
				 * @param string[] $network_plugins Network-active plugins.
				 * @param bool     $multisite Multisite state.
				 */
				public function __construct( array $active_plugins, array $network_plugins, bool $multisite ) {
					$this->active_plugins  = $active_plugins;
					$this->network_plugins = $network_plugins;
					$this->multisite       = $multisite;
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
					return $this->network_plugins;
				}

				/**
				 * Whether multisite is active.
				 *
				 * @return bool
				 */
				public function is_multisite(): bool {
					return $this->multisite;
				}
			}
		);
	}
}
