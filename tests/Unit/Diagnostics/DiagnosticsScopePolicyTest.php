<?php
/**
 * Tests for diagnostics scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies Subphase 2.4 does not introduce later-phase behavior.
 */
final class DiagnosticsScopePolicyTest extends TestCase {

	/**
	 * Test no UI, REST, media, queue, metadata, or delivery behavior is introduced.
	 *
	 * @return void
	 */
	public function test_diagnostics_framework_does_not_introduce_later_phase_behavior(): void {
		$forbidden_patterns = array(
			'REST route registration'      => '/\bregister_rest_route\s*\(/',
			'REST API hook'                => '/\brest_api_init\b/',
			'admin menu page'              => '/\badd_menu_page\s*\(/',
			'admin submenu page'           => '/\badd_submenu_page\s*\(/',
			'global admin asset hook'      => '/\badmin_enqueue_scripts\b/',
			'stylesheet enqueue'           => '/\bwp_enqueue_style\s*\(/',
			'script enqueue'               => '/\bwp_enqueue_script\s*\(/',
			'new-upload media hook'        => '/\bwp_generate_attachment_metadata\b/',
			'attachment metadata write'    => '/\b(?:add|update|delete)_post_meta\s*\(/',
			'attachment metadata update'   => '/\bwp_update_attachment_metadata\s*\(/',
			'frontend image hook'          => '/\bwp_get_attachment_image\b/',
			'frontend content hook'        => '/\bwp_content_img_tag\b/',
			'responsive srcset hook'       => '/\bwp_calculate_image_srcset\b/',
			'delivery loading hook'        => '/\bwp_get_loading_optimization_attributes\b/',
			'optimization queue action'    => '/\bhwlio_optimize_attachment_format\b/',
			'async queue scheduling'       => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling'      => '/\bas_schedule_single_action\s*\(/',
			'automatic memory limit raise' => '/\bini_set\s*\(/',
		);

		$allowed_patterns = array(
			'src/Attachment/WordPressAttachmentMetaStore.php' => array(
				'attachment metadata write',
			),
		);

		foreach ( $this->runtime_source_files() as $file => $contents ) {
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

			if ( 1 === preg_match( '/\bwp_get_image_editor\s*\(/', $contents ) ) {
				self::assertContains(
					$file,
					array(
						'src/Diagnostics/WordPressSampleConversionProbe.php',
						'src/Image/WordPressConversionEditor.php',
					),
					sprintf( 'wp_get_image_editor() is only allowed in conversion probes/adapters, found in %s.', $file )
				);
			}
		}
	}

	/**
	 * Read plugin-owned runtime source files.
	 *
	 * @return array<string,string>
	 */
	private function runtime_source_files(): array {
		$root  = dirname( __DIR__, 3 );
		$files = array(
			$root . DIRECTORY_SEPARATOR . 'hyperweb-lighthouse-image-optimizer.php',
		);

		foreach ( array( 'admin', 'includes', 'public', 'src' ) as $directory ) {
			$files = array_merge(
				$files,
				$this->source_files_in_directory( $root . DIRECTORY_SEPARATOR . $directory )
			);
		}

		$sources = array();

		foreach ( $files as $path ) {
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
	 * Get PHP, CSS, and JavaScript files in a runtime directory.
	 *
	 * @param string $directory Directory to scan.
	 * @return string[]
	 */
	private function source_files_in_directory( string $directory ): array {
		if ( ! is_dir( $directory ) ) {
			return array();
		}

		$files     = array();
		$iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) );
		$allowlist = array(
			'css' => true,
			'js'  => true,
			'php' => true,
		);

		foreach ( $iterator as $file ) {
			if ( ! $file instanceof SplFileInfo || ! $file->isFile() ) {
				continue;
			}

			$extension = strtolower( $file->getExtension() );

			if ( isset( $allowlist[ $extension ] ) ) {
				$files[] = $file->getPathname();
			}
		}

		sort( $files );

		return $files;
	}
}
