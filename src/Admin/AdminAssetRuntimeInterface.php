<?php
/**
 * Admin asset runtime adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Describes WordPress asset/bootstrap APIs needed by the admin client.
 */
interface AdminAssetRuntimeInterface {

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
	public function enqueue_style( string $handle, string $src, array $deps, string $version, string $media = 'all' ): void;

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
	public function enqueue_script( string $handle, string $src, array $deps, string $version, bool $in_footer ): void;

	/**
	 * Add inline bootstrap code before the given script.
	 *
	 * @param string $handle Script handle.
	 * @param string $data Inline JavaScript.
	 * @return void
	 */
	public function add_inline_script_before( string $handle, string $data ): void;

	/**
	 * Build a REST URL.
	 *
	 * @param string $path REST-relative path.
	 * @return string
	 */
	public function rest_url( string $path ): string;

	/**
	 * Create a nonce.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string;
}
