<?php
/**
 * Tests for attachment domain scope boundaries.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifies the attachment domain does not introduce later runtime behavior.
 */
final class AttachmentScopePolicyTest extends TestCase {

	/**
	 * Test no later-phase behavior is introduced.
	 *
	 * @return void
	 */
	public function test_attachment_domain_does_not_introduce_later_phase_behavior(): void {
		$forbidden_patterns = array(
			'REST route registration'      => '/\bregister_rest_route\s*\(/',
			'REST API hook'                => '/\brest_api_init\b/',
			'admin menu page'              => '/\badd_menu_page\s*\(/',
			'admin submenu page'           => '/\badd_submenu_page\s*\(/',
			'global admin asset hook'      => '/\badmin_enqueue_scripts\b/',
			'stylesheet enqueue'           => '/\bwp_enqueue_style\s*\(/',
			'script enqueue'               => '/\bwp_enqueue_script\s*\(/',
			'media modal hook'             => '/\bwp_enqueue_media\b/',
			'media attachment payload'     => '/\bwp_prepare_attachment_for_js\b/',
			'media list columns'           => '/\bmanage_media_columns\b/',
			'media row actions'            => '/\bmedia_row_actions\b/',
			'media attachment fields'      => '/\battachment_fields_to_edit\b/',
			'new-upload media hook'        => '/\bwp_generate_attachment_metadata\b/',
			'attachment metadata read'     => '/\bget_post_meta\s*\(/',
			'attachment metadata write'    => '/\b(?:add|update|delete)_post_meta\s*\(/',
			'attachment metadata update'   => '/\bwp_update_attachment_metadata\s*\(/',
			'runtime hook registration'    => '/\b(?:add_action|add_filter)\s*\(/',
			'image editor conversion'      => '/\bwp_get_image_editor\s*\(/',
			'webp conversion function'     => '/\bimagewebp\s*\(/',
			'avif conversion function'     => '/\bimageavif\s*\(/',
			'frontend image hook'          => '/\bwp_get_attachment_image\b/',
			'frontend content hook'        => '/\bwp_content_img_tag\b/',
			'responsive srcset hook'       => '/\bwp_calculate_image_srcset\b/',
			'delivery loading hook'        => '/\bwp_get_loading_optimization_attributes\b/',
			'optimization queue action'    => '/\bhwlio_optimize_attachment_format\b/',
			'async queue scheduling'       => '/\bas_enqueue_async_action\s*\(/',
			'single queue scheduling'      => '/\bas_schedule_single_action\s*\(/',
			'file write operation'         => '/\bfile_put_contents\s*\(/',
			'file rename operation'        => '/\brename\s*\(/',
			'WordPress file delete'        => '/\bwp_delete_file\s*\(/',
			'file delete operation'        => '/\bunlink\s*\(/',
			'temporary path allocation'    => '/\b(?:wp_tempnam|tempnam)\s*\(/',
			'file copy operation'          => '/\bcopy\s*\(/',
			'uploaded file move operation' => '/\bmove_uploaded_file\s*\(/',
			'automatic memory limit raise' => '/\bini_set\s*\(/',
		);

		$allowed_patterns = array(
			'src/Admin/Rest/RestApi.php'                => array(
				'REST API hook',
			),
			'src/Admin/Rest/WordPressRestRuntime.php'   => array(
				'REST route registration',
			),
			'src/Admin/MediaLibrary/MediaLibraryAssets.php' => array(
				'global admin asset hook',
				'media modal hook',
			),
			'src/Admin/MediaLibrary/MediaLibraryIntegration.php' => array(
				'media attachment payload',
				'media list columns',
				'media row actions',
				'media attachment fields',
			),
			'src/Attachment/AttachmentCleanup.php'      => array(
				'runtime hook registration',
			),
			'src/Attachment/WordPressAttachmentMetaStore.php' => array(
				'attachment metadata read',
				'attachment metadata write',
			),
			'src/Delivery/DeliveryManager.php'          => array(
				'frontend image hook',
				'frontend content hook',
			),
			'src/Delivery/ResponsivePreloadManager.php' => array(
				'runtime hook registration',
			),
		);

		foreach ( $this->attachment_source_files() as $file => $contents ) {
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
	 * Read attachment-domain runtime source files.
	 *
	 * @return array<string,string>
	 */
	private function attachment_source_files(): array {
		$root      = dirname( __DIR__, 3 );
		$directory = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Attachment';
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
