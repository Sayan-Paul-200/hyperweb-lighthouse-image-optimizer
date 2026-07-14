<?php
// phpcs:ignoreFile -- Test file intentionally reads local source files directly for policy assertions.
/**
 * Tests for reporting scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the reporting slice stays read-only and provider-free across Phase 12 reporting work.
 */
final class ReportingScopePolicyTest extends TestCase {

	/**
	 * Test reporting files do not introduce runtime hooks or queue work.
	 *
	 * @return void
	 */
	public function test_reporting_slice_stays_read_only_and_provider_free(): void {
		$forbidden_patterns = array(
			'REST route registration' => '/\bregister_rest_route\s*\(/',
			'REST API hook'           => '/\brest_api_init\b/',
			'admin menu page'         => '/\badd_menu_page\s*\(/',
			'admin submenu page'      => '/\badd_submenu_page\s*\(/',
			'global admin asset hook' => '/\badmin_enqueue_scripts\b/',
			'frontend image hook'     => '/\bwp_get_attachment_image\b/',
			'frontend content hook'   => '/\bwp_content_img_tag\b/',
			'output buffering'        => '/\bob_start\s*\(/',
			'async queue scheduling'  => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling' => '/\bas_schedule_single_action\s*\(/',
		);

		foreach ( $this->source_files() as $file => $contents ) {
			foreach ( $forbidden_patterns as $label => $pattern ) {
				self::assertDoesNotMatchRegularExpression(
					$pattern,
					$contents,
					sprintf( '%s found in %s.', $label, $file )
				);
			}
		}
	}

	/**
	 * Test PSI HTTP usage stays confined to the dedicated client/runtime files.
	 *
	 * @return void
	 */
	public function test_pagespeed_http_usage_is_confined_to_dedicated_reporting_files(): void {
		$allowed_http     = array(
			'src/Reporting/WordPressPageSpeedHttpRuntime.php',
		);
		$allowed_endpoint = array(
			'src/Reporting/WordPressPageSpeedInsightsClient.php',
		);

		foreach ( $this->source_files() as $file => $contents ) {
			if ( 1 === preg_match( '/\bwp_safe_remote_get\s*\(/', $contents ) ) {
				self::assertContains(
					$file,
					$allowed_http,
					sprintf( 'wp_safe_remote_get() is only allowed in the PSI HTTP runtime, found in %s.', $file )
				);
			}

			if ( false !== strpos( $contents, 'pagespeedonline/v5/runPagespeed' ) ) {
				self::assertContains(
					$file,
					$allowed_endpoint,
					sprintf( 'The PSI endpoint should only appear in the dedicated PSI client, found in %s.', $file )
				);
			}
		}
	}

	/**
	 * Read reporting source files.
	 *
	 * @return array<string,string>
	 */
	private function source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Reporting';
		$sources   = array();

		foreach ( $this->php_files_in_directory( $directory ) as $path ) {
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
