<?php
/**
 * Tests for Elementor scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies Elementor coupling stays isolated to the dedicated integration slice.
 */
final class ElementorScopePolicyTest extends TestCase {

	/**
	 * Test Elementor coupling is confined to the integration slice.
	 *
	 * @return void
	 */
	public function test_elementor_coupling_is_confined_to_the_integration_slice(): void {
		$forbidden_patterns = array(
			'Elementor namespace reference' => '/\bElementor\\\\/i',
			'Elementor token'               => '/\belementor(?:[-_][a-z0-9_]+)+/i',
			'Elementor data attribute'      => '/data-elementor/i',
			'Elementor gallery token'       => '/e-gallery-image/i',
			'Elementor carousel token'      => '/swiper-slide-image/i',
		);
		$allowed_files      = array(
			'src/Plugin.php',
			'src/Admin/SettingsPage.php',
			'src/Settings/SettingsSchema.php',
			'src/Settings/SettingsRepositoryInterface.php',
			'src/Settings/SettingsRepository.php',
			'src/Settings/StaticSettingsRepository.php',
			'src/Integration/Conflict/ConflictDetector.php',
			'src/Admin/PostEditor/ElementorHeroBackgroundMetaBox.php',
			'src/Integration/ElementorBackgroundDiscovery.php',
			'src/Integration/ElementorBackgroundBreakpointMap.php',
			'src/Integration/ElementorBackgroundDeliveryPlan.php',
			'src/Integration/ElementorBackgroundDeliveryPlanBuilder.php',
			'src/Integration/ElementorBackgroundDeliveryPlanResult.php',
			'src/Integration/ElementorBackgroundDeliveryVariant.php',
			'src/Integration/ElementorBackgroundStylesheetGenerator.php',
			'src/Integration/ElementorBackgroundPreloadLink.php',
			'src/Integration/ElementorBackgroundPreloadResult.php',
			'src/Integration/ElementorCriticalBackgroundPreloadManager.php',
			'src/Integration/ElementorBackgroundStylesheetManager.php',
			'src/Integration/ElementorBackgroundStylesheetResult.php',
			'src/Integration/ElementorBackgroundStylesheetRuntimeInterface.php',
			'src/Integration/ElementorBackgroundStylesheetStoreInterface.php',
			'src/Integration/ElementorBackgroundDiscoveryResult.php',
			'src/Integration/ElementorBackgroundSource.php',
			'src/Integration/ElementorDocumentData.php',
			'src/Integration/ElementorDocumentDataStoreInterface.php',
			'src/Integration/ElementorOversizedSelectionAnalyzer.php',
			'src/Integration/ElementorOversizedSelectionResult.php',
			'src/Integration/ElementorHeroBackgroundPostMetaStoreInterface.php',
			'src/Integration/ElementorHeroBackgroundTargetSelection.php',
			'src/Integration/ElementorIntegration.php',
			'src/Integration/ElementorRuntimeInterface.php',
			'src/Integration/ElementorUnsupportedBackgroundCase.php',
			'src/Integration/ElementorWidgetDeliveryBridge.php',
			'src/Integration/ElementorWidgetMatcher.php',
			'src/Integration/WordPressElementorBackgroundStylesheetRuntime.php',
			'src/Integration/WordPressElementorBackgroundStylesheetStore.php',
			'src/Integration/WordPressElementorDocumentDataStore.php',
			'src/Integration/WordPressElementorHeroBackgroundPostMetaStore.php',
			'src/Integration/WordPressElementorRuntime.php',
			'src/Reporting/ContentInventoryService.php',
			'src/Reporting/ContentInventorySnapshot.php',
			'src/Reporting/ContentIssueReportService.php',
			'src/Reporting/PageInventoryReport.php',
			'src/Reporting/UnsupportedInventoryCase.php',
		);

		foreach ( $this->source_files() as $file => $contents ) {
			foreach ( $forbidden_patterns as $label => $pattern ) {
				if ( in_array( $file, $allowed_files, true ) ) {
					continue;
				}

				self::assertDoesNotMatchRegularExpression(
					$pattern,
					$contents,
					sprintf( '%s found in %s.', $label, $file )
				);
			}
		}
	}

	/**
	 * Test Elementor runtime expansion stays limited to the new background stylesheet slice.
	 *
	 * @return void
	 */
	public function test_elementor_runtime_hooks_and_css_mutation_stay_narrowly_scoped(): void {
		$forbidden_patterns = array(
			'frontend enqueue hook'        => '/\bwp_enqueue_scripts\b/',
			'preload head hook'            => '/\bwp_head\b/',
			'Elementor widget render hook' => '/\belementor\/widget\/render_content\b/',
			'stylesheet enqueue'           => '/\bwp_enqueue_style\s*\(/',
			'content hook'                 => '/\bthe_content\b/',
			'output buffering'             => '/\bob_start\s*\(/',
			'REST route'                   => '/\bregister_rest_route\s*\(/',
			'Elementor meta write'         => '/\b(?:update_post_meta|add_post_meta|delete_post_meta)\s*\([^)]*_elementor_/i',
		);
		$allowed_patterns   = array(
			'src/Integration/ElementorBackgroundStylesheetManager.php'          => array( 'frontend enqueue hook' ),
			'src/Integration/ElementorCriticalBackgroundPreloadManager.php'     => array( 'preload head hook' ),
			'src/Integration/ElementorWidgetDeliveryBridge.php'                 => array( 'Elementor widget render hook' ),
			'src/Integration/WordPressElementorBackgroundStylesheetRuntime.php' => array( 'stylesheet enqueue' ),
		);

		foreach ( $this->source_files() as $file => $contents ) {
			if ( 0 !== strpos( $file, 'src/Integration/' ) && 'src/Plugin.php' !== $file ) {
				continue;
			}

			foreach ( $forbidden_patterns as $label => $pattern ) {
				if ( isset( $allowed_patterns[ $file ] ) && in_array( $label, $allowed_patterns[ $file ], true ) ) {
					continue;
				}

				self::assertDoesNotMatchRegularExpression(
					$pattern,
					$contents,
					sprintf( '%s found in %s.', $label, $file )
				);
			}
		}
	}

	/**
	 * Read plugin source files.
	 *
	 * @return array<string,string>
	 */
	private function source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src';
		$sources   = array();

		foreach ( $this->php_files_in_directory( $directory ) as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source files during tests.
			$contents = file_get_contents( $path );

			if ( false === $contents ) {
				continue;
			}

			$sources[ str_replace( '\\', '/', str_replace( $root . DIRECTORY_SEPARATOR, '', $path ) ) ] = $contents;
		}

		return $sources;
	}

	/**
	 * Get PHP files in one directory tree.
	 *
	 * @param string $directory Directory to scan.
	 * @return string[]
	 */
	private function php_files_in_directory( string $directory ): array {
		if ( ! is_dir( $directory ) ) {
			return array();
		}

		$files    = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) );

		foreach ( $iterator as $file ) {
			if ( $file instanceof SplFileInfo && $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}

		sort( $files );

		return $files;
	}
}
