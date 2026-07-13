<?php
/**
 * Tests for WooCommerce audit-only scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies WooCommerce coupling stays isolated to the dedicated integration slice.
 */
final class WooCommerceScopePolicyTest extends TestCase {

	/**
	 * Test WooCommerce coupling is confined to the integration slice.
	 *
	 * @return void
	 */
	public function test_woocommerce_coupling_is_confined_to_the_integration_slice(): void {
		$forbidden_patterns = array(
			'WooCommerce hook registration' => '/\b(?:add_action|add_filter)\s*\(\s*[\'"]woocommerce_[^\'"]+[\'"]/i',
			'WooCommerce hook string'       => '/[\'"]woocommerce_[^\'"]+[\'"]/i',
			'WooCommerce token'             => '/woocommerce_[a-z0-9_]+/i',
			'WooCommerce class reference'   => '/\bWooCommerce\b/',
			'WooCommerce function call'     => '/\bwc_[a-z0-9_]+\s*\(/i',
			'WooCommerce template helper'   => '/\bwoocommerce_[a-z0-9_]+\s*\(/i',
			'WooCommerce request helper'    => '/\bis_product\s*\(/i',
		);
		$allowed_files      = array(
			'src/Plugin.php',
			'src/Integration/WooCommerceIntegration.php',
			'src/Integration/WooCommercePrimaryImageMatcher.php',
			'src/Integration/WooCommerceRuntimeInterface.php',
			'src/Integration/WordPressWooCommerceRuntime.php',
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
