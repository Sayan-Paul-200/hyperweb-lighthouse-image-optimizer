<?php
/**
 * Tests for CLI scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the CLI slice stays read-only and isolated.
 */
final class CliScopePolicyTest extends TestCase {

	/**
	 * Test CLI files do not introduce non-CLI runtime behavior.
	 *
	 * @return void
	 */
	public function test_cli_slice_stays_read_only_and_provider_scoped(): void {
		$forbidden_patterns = array(
			'REST route registration'   => '/\bregister_rest_route\s*\(/',
			'REST API hook'             => '/\brest_api_init\b/',
			'admin menu page'           => '/\badd_menu_page\s*\(/',
			'admin submenu page'        => '/\badd_submenu_page\s*\(/',
			'global admin asset hook'   => '/\badmin_enqueue_scripts\b/',
			'global frontend asset hook' => '/\bwp_enqueue_scripts\b/',
			'frontend image hook'       => '/\bwp_get_attachment_image\b/',
			'frontend content hook'     => '/\bwp_content_img_tag\b/',
			'responsive srcset hook'    => '/\bwp_calculate_image_srcset\b/',
			'delivery loading hook'     => '/\bwp_get_loading_optimization_attributes\b/',
			'new-upload media hook'     => '/\bwp_generate_attachment_metadata\b/',
			'async queue scheduling'    => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling'   => '/\bas_schedule_single_action\s*\(/',
			'output buffering'          => '/\bob_start\s*\(/',
			'attachment meta write'     => '/\b(?:add|update|delete)_post_meta\s*\(/',
			'cli init hook'             => '/\bcli_init\b/',
			'WP_CLI static runtime'     => '/\bWP_CLI\b/',
		);
		$allowed_patterns   = array(
			'src/Cli/CliCommands.php'      => array(
				'cli init hook',
			),
			'src/Cli/WordPressCliRuntime.php' => array(
				'WP_CLI static runtime',
			),
		);

		foreach ( $this->cli_source_files() as $file => $contents ) {
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
	 * Read CLI source files.
	 *
	 * @return array<string,string>
	 */
	private function cli_source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Cli';
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
