<?php
/**
 * Tests for post-editor scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\PostEditor;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the critical-image post-editor slice stays narrowly scoped.
 */
final class PostEditorScopePolicyTest extends TestCase {

	/**
	 * Test only the planned post-editor APIs are used in the new slice.
	 *
	 * @return void
	 */
	public function test_post_editor_slice_stays_narrowly_scoped(): void {
		$forbidden_patterns = array(
			'REST route registration'   => '/\bregister_rest_route\s*\(/',
			'REST API hook'             => '/\brest_api_init\b/',
			'frontend image hook'       => '/\bwp_get_attachment_image\b/',
			'frontend content hook'     => '/\bwp_content_img_tag\b/',
			'responsive srcset hook'    => '/\bwp_calculate_image_srcset\b/',
			'delivery loading hook'     => '/\bwp_get_loading_optimization_attributes\b/',
			'media metadata update'     => '/\bwp_update_attachment_metadata\s*\(/',
			'attachment metadata write' => '/\b(?:add|update|delete)_post_meta\s*\(/',
			'file write operation'      => '/\bfile_put_contents\s*\(/',
			'queue scheduling'          => '/\bas_(?:enqueue_async_action|schedule_single_action)\s*\(/',
			'global admin asset hook'   => '/\badmin_enqueue_scripts\b/',
			'media picker enqueue'      => '/\bwp_enqueue_media\b/',
			'meta box registration'     => '/\badd_meta_box\s*\(/',
			'post save hook'            => '/\bsave_post\b/',
			'script enqueue'            => '/\bwp_enqueue_script\s*\(/',
		);

		$allowed_patterns = array(
			'src/Admin/PostEditor/CriticalImageAssets.php' => array(
				'global admin asset hook',
			),
			'src/Admin/PostEditor/CriticalImageMetaBox.php' => array(
				'meta box registration',
				'post save hook',
			),
			'src/Admin/PostEditor/ElementorHeroBackgroundMetaBox.php' => array(
				'meta box registration',
				'post save hook',
			),
			'src/Admin/PostEditor/PostEditorRuntimeInterface.php' => array(
				'meta box registration',
			),
			'src/Admin/PostEditor/WordPressPostEditorRuntime.php' => array(
				'media picker enqueue',
				'meta box registration',
			),
			'src/Admin/WordPressAdminAssetRuntime.php'     => array(
				'script enqueue',
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
	 * Read post-editor slice source files.
	 *
	 * @return array<string,string>
	 */
	private function source_files(): array {
		$root      = dirname( __DIR__, 4 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'PostEditor';
		$sources   = array();

		foreach ( $this->files_in_directory( $directory ) as $path ) {
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
	 * Get PHP files in the post-editor slice.
	 *
	 * @param string $directory Directory to scan.
	 * @return string[]
	 */
	private function files_in_directory( string $directory ): array {
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
