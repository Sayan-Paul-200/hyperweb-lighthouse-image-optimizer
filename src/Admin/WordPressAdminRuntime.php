<?php
/**
 * WordPress admin runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Calls the WordPress admin runtime APIs used by the screen shell.
 */
final class WordPressAdminRuntime implements AdminRuntimeInterface {

	/**
	 * Register a submenu page.
	 *
	 * @param string   $parent_slug Parent slug.
	 * @param string   $page_title Browser page title.
	 * @param string   $menu_title Menu label.
	 * @param string   $capability Required capability.
	 * @param string   $menu_slug Menu slug.
	 * @param callable $callback Render callback.
	 * @return string
	 */
	public function register_submenu_page(
		string $parent_slug,
		string $page_title,
		string $menu_title,
		string $capability,
		string $menu_slug,
		callable $callback
	): string {
		$hook_suffix = \add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$callback
		);

		return is_string( $hook_suffix ) ? $hook_suffix : '';
	}

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool {
		return \current_user_can( $capability );
	}

	/**
	 * Build an admin URL.
	 *
	 * @param string $path Admin-relative path.
	 * @return string
	 */
	public function admin_url( string $path = '' ): string {
		return \admin_url( $path );
	}

	/**
	 * Abort the current request with a WordPress admin error.
	 *
	 * @param string              $message Error message.
	 * @param string              $title Error title.
	 * @param array<string,mixed> $args Error arguments.
	 * @return void
	 */
	public function forbid( string $message, string $title = '', array $args = array() ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die handles escaped admin error output.
		\wp_die( $message, $title, $args );
	}
}
