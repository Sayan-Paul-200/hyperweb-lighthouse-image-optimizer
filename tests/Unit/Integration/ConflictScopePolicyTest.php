<?php
/**
 * Tests for conflict detector scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the conflict detector stays read-only and does not mutate third-party plugins.
 */
final class ConflictScopePolicyTest extends TestCase {

	/**
	 * Test the conflict slice stays read-only and does not add runtime/admin surface.
	 *
	 * @return void
	 */
	public function test_conflict_slice_stays_read_only_and_non_runtime_mutating(): void {
		$forbidden_patterns = array(
			'plugin deactivation'     => '/\bdeactivate_plugins\s*\(/',
			'plugin activation'       => '/\bactivate_plugin[s]?\s*\(/',
			'plugin deletion'         => '/\bdelete_plugins\s*\(/',
			'option write'            => '/\b(?:add_option|update_option|delete_option|update_site_option|delete_site_option)\s*\(/',
			'plugin file write'       => '/\b(?:file_put_contents|rename|unlink)\s*\(/',
			'REST route registration' => '/\bregister_rest_route\s*\(/',
			'admin menu page'         => '/\badd_menu_page\s*\(/',
			'admin submenu page'      => '/\badd_submenu_page\s*\(/',
			'global frontend hook'    => '/\bwp_enqueue_scripts\b/',
			'delivery URL override'   => '/hwlio_delivery_(?:uploads_base_url|derivative_url)/',
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
	 * Read conflict-slice source files.
	 *
	 * @return array<string,string>
	 */
	private function source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Integration' . DIRECTORY_SEPARATOR . 'Conflict';
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
