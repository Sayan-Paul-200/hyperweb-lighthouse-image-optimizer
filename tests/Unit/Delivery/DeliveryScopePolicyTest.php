<?php
/**
 * Tests for delivery subphase scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies delivery work stays narrowly scoped to the active attachment and post-content hooks.
 */
final class DeliveryScopePolicyTest extends TestCase {

	/**
	 * Test delivery source remains narrowly scoped and does not introduce later runtime behavior.
	 *
	 * @return void
	 */
	public function test_delivery_foundation_does_not_introduce_later_runtime_behavior(): void {
		$forbidden_patterns = array(
			'REST route registration'    => '/\bregister_rest_route\s*\(/',
			'REST API hook'              => '/\brest_api_init\b/',
			'admin menu page'            => '/\badd_menu_page\s*\(/',
			'admin submenu page'         => '/\badd_submenu_page\s*\(/',
			'global frontend asset hook' => '/\bwp_enqueue_scripts\b/',
			'global admin asset hook'    => '/\badmin_enqueue_scripts\b/',
			'stylesheet enqueue'         => '/\bwp_enqueue_style\s*\(/',
			'script enqueue'             => '/\bwp_enqueue_script\s*\(/',
			'media modal hook'           => '/\bwp_enqueue_media\b/',
			'media attachment payload'   => '/\bwp_prepare_attachment_for_js\b/',
			'media list columns'         => '/\bmanage_media_columns\b/',
			'media row actions'          => '/\bmedia_row_actions\b/',
			'media attachment fields'    => '/\battachment_fields_to_edit\b/',
			'new-upload media hook'      => '/\bwp_generate_attachment_metadata\b/',
			'frontend image hook'        => '/\bwp_get_attachment_image\b/',
			'frontend content hook'      => '/\bwp_content_img_tag\b/',
			'responsive srcset hook'     => '/\bwp_calculate_image_srcset\b/',
			'delivery loading hook'      => '/\bwp_get_loading_optimization_attributes\b/',
			'preload head hook'          => '/\bwp_head\b/',
			'runtime hook registration'  => '/\b(?:add_action|add_filter)\s*\(/',
			'async queue scheduling'     => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling'    => '/\bas_schedule_single_action\s*\(/',
			'output buffering'           => '/\bob_start\s*\(/',
		);
		$allowed_patterns   = array(
			'src/Delivery/DeliveryManager.php'          => array(
				'frontend image hook',
				'frontend content hook',
				'runtime hook registration',
			),
			'src/Delivery/LoadingAttributeManager.php'  => array(
				'delivery loading hook',
				'runtime hook registration',
			),
			'src/Delivery/ResponsivePreloadManager.php' => array(
				'preload head hook',
				'runtime hook registration',
			),
		);

		foreach ( $this->delivery_source_files() as $file => $contents ) {
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
	 * Read delivery runtime source files.
	 *
	 * @return array<string,string>
	 */
	private function delivery_source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Delivery';
		$sources   = array();

		foreach ( $this->source_files_in_directory( $directory ) as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local test source files.
			$contents = file_get_contents( $path );

			if ( false === $contents ) {
				continue;
			}

			$sources[ str_replace( '\\', '/', str_replace( $root . DIRECTORY_SEPARATOR, '', $path ) ) ] = $contents;
		}

		return $sources;
	}

	/**
	 * Get PHP files in a runtime directory.
	 *
	 * @param string $directory Directory to scan.
	 * @return string[]
	 */
	private function source_files_in_directory( string $directory ): array {
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
