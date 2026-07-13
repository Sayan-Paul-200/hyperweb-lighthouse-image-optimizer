<?php
/**
 * Admin asset hook provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Enqueues screen-scoped admin assets and REST bootstrap data.
 */
final class Assets implements HookProviderInterface {

	public const PRIORITY      = 10;
	public const STYLE_HANDLE  = 'hwlio-admin';
	public const SCRIPT_HANDLE = 'hwlio-admin';

	/**
	 * Menu helper.
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Screen context resolver.
	 *
	 * @var AdminScreenContextResolver
	 */
	private $context_resolver;

	/**
	 * Admin asset runtime adapter.
	 *
	 * @var AdminAssetRuntimeInterface
	 */
	private $runtime;

	/**
	 * Notice manager.
	 *
	 * @var NoticeManager
	 */
	private $notice_manager;

	/**
	 * Asset base URL with trailing slash.
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
	 * Create the provider.
	 *
	 * @param Menu                       $menu Menu helper.
	 * @param AdminScreenContextResolver $context_resolver Screen context resolver.
	 * @param AdminAssetRuntimeInterface $runtime Asset runtime adapter.
	 * @param NoticeManager              $notice_manager Notice manager.
	 * @param string                     $asset_base_url Plugin base URL.
	 * @param string                     $version Plugin version.
	 */
	public function __construct(
		Menu $menu,
		AdminScreenContextResolver $context_resolver,
		AdminAssetRuntimeInterface $runtime,
		NoticeManager $notice_manager,
		string $asset_base_url,
		string $version
	) {
		$this->menu             = $menu;
		$this->context_resolver = $context_resolver;
		$this->runtime          = $runtime;
		$this->notice_manager   = $notice_manager;
		$this->asset_base_url   = rtrim( $asset_base_url, '/' ) . '/';
		$this->version          = $version;
	}

	/**
	 * Register the admin enqueue hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), self::PRIORITY, 1 );
	}

	/**
	 * Enqueue screen-scoped admin assets and bootstrap data.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( ! $this->menu->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$context = $this->context_resolver->resolve( $hook_suffix );

		$this->runtime->enqueue_style(
			self::STYLE_HANDLE,
			$this->asset_base_url . 'admin/css/hyperweb-lighthouse-image-optimizer-admin.css',
			array(),
			$this->version
		);

		$this->runtime->enqueue_script(
			self::SCRIPT_HANDLE,
			$this->asset_base_url . 'admin/js/hyperweb-lighthouse-image-optimizer-admin.js',
			array( 'wp-api-fetch' ),
			$this->version,
			true
		);

		$bootstrap = new AdminBootstrapConfig(
			$context,
			$this->runtime->rest_url( AdminBootstrapConfig::REST_NAMESPACE ),
			$this->runtime->create_nonce( 'wp_rest' ),
			$this->version,
			$this->notice_manager
		);

		$this->runtime->add_inline_script_before(
			self::SCRIPT_HANDLE,
			'window.hwlioAdminConfig = ' . $this->json_encode( $bootstrap->to_array() ) . ';'
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
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for non-WordPress test/runtime contexts.
			$json = json_encode( $payload );
		}

		return is_string( $json ) ? $json : '{}';
	}
}
