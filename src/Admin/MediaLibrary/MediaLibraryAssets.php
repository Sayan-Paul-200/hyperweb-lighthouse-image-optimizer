<?php
/**
 * Media Library asset provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminAssetRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Enqueues Media Library controls only on media-capable admin screens.
 */
final class MediaLibraryAssets implements HookProviderInterface {

	public const PRIORITY      = 10;
	public const STYLE_HANDLE  = 'hwlio-media-library';
	public const SCRIPT_HANDLE = 'hwlio-media-library';

	/**
	 * Media runtime adapter.
	 *
	 * @var MediaLibraryRuntimeInterface
	 */
	private $runtime;

	/**
	 * Admin asset runtime adapter.
	 *
	 * @var AdminAssetRuntimeInterface
	 */
	private $assets;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

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
	 * Whether assets were already enqueued for the request.
	 *
	 * @var bool
	 */
	private $did_enqueue = false;

	/**
	 * Create the provider.
	 *
	 * @param MediaLibraryRuntimeInterface $runtime Media runtime adapter.
	 * @param AdminAssetRuntimeInterface   $assets Asset runtime adapter.
	 * @param SettingsRepositoryInterface  $settings Settings repository.
	 * @param string                       $asset_base_url Asset base URL.
	 * @param string                       $version Plugin version.
	 */
	public function __construct(
		MediaLibraryRuntimeInterface $runtime,
		AdminAssetRuntimeInterface $assets,
		SettingsRepositoryInterface $settings,
		string $asset_base_url,
		string $version
	) {
		$this->runtime        = $runtime;
		$this->assets         = $assets;
		$this->settings       = $settings;
		$this->asset_base_url = rtrim( $asset_base_url, '/' ) . '/';
		$this->version        = $version;
	}

	/**
	 * Register media-screen asset hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), self::PRIORITY, 1 );
		$hooks->add_action( 'wp_enqueue_media', array( $this, 'enqueue_media_modal_assets' ), self::PRIORITY, 0 );
	}

	/**
	 * Enqueue assets on upload and attachment edit screens.
	 *
	 * @param string $hook_suffix Current hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! $this->settings->media_library_controls_enabled() ) {
			return;
		}

		if ( ! $this->should_load_for_hook( $hook_suffix ) ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Enqueue assets when the classic media modal is loaded.
	 *
	 * @return void
	 */
	public function enqueue_media_modal_assets(): void {
		if ( ! $this->settings->media_library_controls_enabled() ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Determine whether one admin hook should load Media Library assets.
	 *
	 * @param string $hook_suffix Admin hook suffix.
	 * @return bool
	 */
	private function should_load_for_hook( string $hook_suffix ): bool {
		if ( 'upload.php' === $hook_suffix ) {
			return true;
		}

		if ( 'post.php' === $hook_suffix && 'attachment' === $this->runtime->current_post_type() ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue the shared Media Library assets and bootstrap data once.
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		if ( $this->did_enqueue ) {
			return;
		}

		$this->did_enqueue = true;

		$this->assets->enqueue_style(
			self::STYLE_HANDLE,
			$this->asset_base_url . 'admin/css/hyperweb-lighthouse-image-optimizer-media-library.css',
			array(),
			$this->version
		);

		$this->assets->enqueue_script(
			self::SCRIPT_HANDLE,
			$this->asset_base_url . 'admin/js/hyperweb-lighthouse-image-optimizer-media-library.js',
			array( 'wp-api-fetch', 'media-views' ),
			$this->version,
			true
		);

		$bootstrap = new MediaLibraryBootstrapConfig(
			$this->assets->rest_url( AdminBootstrapConfig::REST_NAMESPACE ),
			$this->assets->create_nonce( 'wp_rest' ),
			$this->version,
			$this->settings->automatic_optimization_enabled(),
			$this->settings->media_library_controls_enabled(),
			$this->settings->attachment_exclusion_allowed()
		);

		$this->assets->add_inline_script_before(
			self::SCRIPT_HANDLE,
			'window.hwlioMediaLibraryConfig = ' . $this->json_encode( $bootstrap->to_array() ) . ';'
		);
	}

	/**
	 * Encode one bootstrap payload to JSON.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return string
	 */
	private function json_encode( array $payload ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			$json = wp_json_encode( $payload );
		} else {
			$json = json_encode( $payload );
		}

		return is_string( $json ) ? $json : '{}';
	}
}
