<?php
/**
 * Critical image post editor assets.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminAssetRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Loads the media-picker bootstrap only on supported post editor screens.
 */
final class CriticalImageAssets implements HookProviderInterface {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	public const SCRIPT_HANDLE = 'hwlio-post-editor-critical-image';

	/**
	 * Supported post types.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_POST_TYPES = array( 'post', 'page' );

	/**
	 * Post editor runtime.
	 *
	 * @var PostEditorRuntimeInterface
	 */
	private $runtime;

	/**
	 * Shared admin asset runtime.
	 *
	 * @var AdminAssetRuntimeInterface
	 */
	private $assets;

	/**
	 * Asset base URL.
	 *
	 * @var string
	 */
	private $asset_base_url;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Whether assets already loaded for the request.
	 *
	 * @var bool
	 */
	private $did_enqueue = false;

	/**
	 * Create provider.
	 *
	 * @param PostEditorRuntimeInterface $runtime Runtime seam.
	 * @param AdminAssetRuntimeInterface $assets Shared admin asset runtime.
	 * @param string                     $asset_base_url Asset base URL.
	 * @param string                     $version Plugin version.
	 */
	public function __construct(
		PostEditorRuntimeInterface $runtime,
		AdminAssetRuntimeInterface $assets,
		string $asset_base_url,
		string $version
	) {
		$this->runtime        = $runtime;
		$this->assets         = $assets;
		$this->asset_base_url = rtrim( $asset_base_url, '/' ) . '/';
		$this->version        = $version;
	}

	/**
	 * Register scoped editor asset hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ), 10, 1 );
	}

	/**
	 * Enqueue the media picker bootstrap only on supported post editor screens.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_editor_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( ! in_array( $this->runtime->current_post_type(), self::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		if ( $this->did_enqueue ) {
			return;
		}

		$this->did_enqueue = true;

		$this->runtime->enqueue_media();
		$this->assets->enqueue_script(
			self::SCRIPT_HANDLE,
			$this->asset_base_url . 'admin/js/hyperweb-lighthouse-image-optimizer-post-editor.js',
			array( 'media-editor' ),
			$this->version,
			true
		);
		$this->assets->add_inline_script_before(
			self::SCRIPT_HANDLE,
			'window.hwlioCriticalImageEditor = ' . $this->json_encode(
				array(
					'selectors' => array(
						'box'     => '[data-hwlio-critical-image-box="1"]',
						'input'   => '[data-hwlio-critical-image-input="1"]',
						'title'   => '[data-hwlio-critical-image-title="1"]',
						'summary' => '[data-hwlio-critical-image-summary="1"]',
						'preview' => '[data-hwlio-critical-image-preview="1"]',
						'select'  => '[data-hwlio-critical-image-action="select"]',
						'clear'   => '[data-hwlio-critical-image-action="clear"]',
					),
					'strings'   => array(
						'selectTitle'   => 'Select critical image',
						'buttonSelect'  => 'Select image',
						'buttonReplace' => 'Replace image',
						'buttonClear'   => 'Clear',
						'noSelection'   => 'No critical image selected.',
					),
				)
			) . ';'
		);
	}

	/**
	 * Encode one bootstrap payload.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return string
	 */
	private function json_encode( array $payload ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			$json = wp_json_encode( $payload );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for non-WordPress test/runtime contexts.
			$json = json_encode( $payload );
		}

		return is_string( $json ) ? $json : '{}';
	}
}
