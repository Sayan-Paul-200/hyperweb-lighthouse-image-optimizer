<?php
/**
 * Tests for offload integration scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the offload slice stays isolated from unrelated runtime surfaces.
 */
final class OffloadScopePolicyTest extends TestCase {

	/**
	 * Test the offload slice stays product-isolated and does not widen runtime behavior beyond 11.3.
	 *
	 * @return void
	 */
	public function test_offload_slice_stays_isolated_from_unrelated_runtime_surfaces(): void {
		$forbidden_patterns = array(
			'REST route registration' => '/\bregister_rest_route\s*\(/',
			'REST API hook'           => '/\brest_api_init\b/',
			'admin menu page'         => '/\badd_menu_page\s*\(/',
			'admin submenu page'      => '/\badd_submenu_page\s*\(/',
			'global admin asset hook' => '/\badmin_enqueue_scripts\b/',
			'global frontend hook'    => '/\bwp_enqueue_scripts\b/',
			'frontend image hook'     => '/\bwp_get_attachment_image\b/',
			'frontend content hook'   => '/\bwp_content_img_tag\b/',
			'loading hook'            => '/\bwp_get_loading_optimization_attributes\b/',
			'output buffering'        => '/\bob_start\s*\(/',
			'async queue scheduling'  => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling' => '/\bas_schedule_single_action\s*\(/',
			'runtime hook registration' => '/\b(?:add_action|add_filter)\s*\(/',
			'plugin deactivation'     => '/\bdeactivate_plugins\s*\(/',
			'theme file mutation'     => '/\b(?:switch_theme|wp_update_theme)\s*\(/',
		);
		$allowed_patterns   = array(
			'src/Integration/Offload/OffloadDeliveryAdapter.php' => array(
				'runtime hook registration',
			),
		);

		foreach ( $this->source_files() as $file => $contents ) {
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
	 * Read offload-slice source files.
	 *
	 * @return array<string,string>
	 */
	private function source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Integration' . DIRECTORY_SEPARATOR . 'Offload';
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
