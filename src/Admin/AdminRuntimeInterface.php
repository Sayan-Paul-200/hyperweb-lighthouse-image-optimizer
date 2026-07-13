<?php
/**
 * Admin runtime adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Describes the WordPress admin APIs needed by the screen shell.
 */
interface AdminRuntimeInterface {

	/**
	 * Register a submenu page.
	 *
	 * @param string   $parent_slug Parent slug.
	 * @param string   $page_title Browser page title.
	 * @param string   $menu_title Menu label.
	 * @param string   $capability Required capability.
	 * @param string   $menu_slug Menu slug.
	 * @param callable $callback Render callback.
	 * @return string Hook suffix or empty string on failure.
	 */
	public function register_submenu_page(
		string $parent_slug,
		string $page_title,
		string $menu_title,
		string $capability,
		string $menu_slug,
		callable $callback
	): string;

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool;

	/**
	 * Build an admin URL.
	 *
	 * @param string $path Admin-relative path.
	 * @return string
	 */
	public function admin_url( string $path = '' ): string;

	/**
	 * Abort the current request with a WordPress admin error.
	 *
	 * @param string              $message Error message.
	 * @param string              $title Error title.
	 * @param array<string,mixed> $args Error arguments.
	 * @return void
	 */
	public function forbid( string $message, string $title = '', array $args = array() ): void;
}
