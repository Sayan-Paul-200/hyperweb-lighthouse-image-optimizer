<?php
/**
 * WordPress admin asset runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Calls WordPress asset/bootstrap APIs for plugin admin screens.
 */
final class WordPressAdminAssetRuntime implements AdminAssetRuntimeInterface {

	/**
	 * Enqueue one stylesheet.
	 *
	 * @param string   $handle Asset handle.
	 * @param string   $src Asset URL.
	 * @param string[] $deps Dependency handles.
	 * @param string   $version Asset version.
	 * @param string   $media Media target.
	 * @return void
	 */
	public function enqueue_style( string $handle, string $src, array $deps, string $version, string $media = 'all' ): void {
		\wp_enqueue_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Enqueue one script.
	 *
	 * @param string   $handle Asset handle.
	 * @param string   $src Asset URL.
	 * @param string[] $deps Dependency handles.
	 * @param string   $version Asset version.
	 * @param bool     $in_footer Whether to print in the footer.
	 * @return void
	 */
	public function enqueue_script( string $handle, string $src, array $deps, string $version, bool $in_footer ): void {
		\wp_enqueue_script( $handle, $src, $deps, $version, $in_footer );
	}

	/**
	 * Add inline bootstrap code before the given script.
	 *
	 * @param string $handle Script handle.
	 * @param string $data Inline JavaScript.
	 * @return void
	 */
	public function add_inline_script_before( string $handle, string $data ): void {
		\wp_add_inline_script( $handle, $data, 'before' );
	}

	/**
	 * Build a REST URL.
	 *
	 * @param string $path REST-relative path.
	 * @return string
	 */
	public function rest_url( string $path ): string {
		return \rest_url( $path );
	}

	/**
	 * Create a nonce.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string {
		return \wp_create_nonce( $action );
	}
}
