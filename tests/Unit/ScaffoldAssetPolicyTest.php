<?php
/**
 * Tests for scaffold asset loading policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the plugin scaffold does not add placeholder asset overhead.
 */
final class ScaffoldAssetPolicyTest extends TestCase {

	/**
	 * Test runtime source does not register global asset hooks.
	 *
	 * @return void
	 */
	public function test_runtime_source_does_not_register_placeholder_assets(): void {
		$forbidden_patterns = array(
			'global frontend asset hook' => '/\bwp_enqueue_scripts\b/',
			'global admin asset hook'    => '/\badmin_enqueue_scripts\b/',
			'stylesheet enqueue'         => '/\bwp_enqueue_style\s*\(/',
			'script enqueue'             => '/\bwp_enqueue_script\s*\(/',
		);
		$allowed_patterns   = array(
			'src/Admin/Assets.php'                         => array(
				'global admin asset hook',
			),
			'src/Admin/MediaLibrary/MediaLibraryAssets.php' => array(
				'global admin asset hook',
			),
			'src/Admin/PostEditor/CriticalImageAssets.php' => array(
				'global admin asset hook',
			),
			'src/Admin/WordPressAdminAssetRuntime.php'     => array(
				'stylesheet enqueue',
				'script enqueue',
			),
			'src/Integration/ElementorBackgroundStylesheetManager.php' => array(
				'global frontend asset hook',
			),
			'src/Integration/WordPressElementorBackgroundStylesheetRuntime.php' => array(
				'stylesheet enqueue',
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
		}
	}

	/**
	 * Test runtime source does not retain placeholder jQuery usage.
	 *
	 * @return void
	 */
	public function test_runtime_source_does_not_retain_placeholder_jquery_usage(): void {
		foreach ( $this->runtime_source_files() as $file => $contents ) {
			self::assertDoesNotMatchRegularExpression(
				'/\bjQuery\b|\bjquery\b/',
				$contents,
				sprintf( 'Placeholder jQuery usage found in %s.', $file )
			);
		}
	}

	/**
	 * Read plugin-owned runtime source files.
	 *
	 * @return array<string,string>
	 */
	private function runtime_source_files(): array {
		$root  = dirname( __DIR__, 2 );
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
