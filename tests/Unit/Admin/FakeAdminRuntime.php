<?php
/**
 * Fake admin runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminRuntimeInterface;

/**
 * Records WordPress admin calls for unit tests.
 */
final class FakeAdminRuntime implements AdminRuntimeInterface {

	/**
	 * Recorded submenu registrations.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $submenu_calls = array();

	/**
	 * Recorded capability checks.
	 *
	 * @var string[]
	 */
	public $capability_checks = array();

	/**
	 * Recorded forbid calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $forbid_calls = array();

	/**
	 * Whether the fake user can access the page.
	 *
	 * @var bool
	 */
	public $can = true;

	/**
	 * Hook suffix returned by submenu registration.
	 *
	 * @var string
	 */
	public $hook_suffix = 'media_page_hwlio';

	/**
	 * Base admin URL used in tests.
	 *
	 * @var string
	 */
	public $base_url = 'https://example.test/wp-admin/';

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
		$this->submenu_calls[] = array(
			'parent_slug' => $parent_slug,
			'page_title'  => $page_title,
			'menu_title'  => $menu_title,
			'capability'  => $capability,
			'menu_slug'   => $menu_slug,
			'callback'    => $callback,
		);

		return $this->hook_suffix;
	}

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string $capability Capability.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool {
		$this->capability_checks[] = $capability;

		return $this->can;
	}

	/**
	 * Build an admin URL.
	 *
	 * @param string $path Admin-relative path.
	 * @return string
	 */
	public function admin_url( string $path = '' ): string {
		return rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * Abort the current request with a fake admin error.
	 *
	 * @param string              $message Error message.
	 * @param string              $title Error title.
	 * @param array<string,mixed> $args Error arguments.
	 * @throws AdminAccessDenied Always thrown to simulate a denied admin request.
	 * @return void
	 */
	public function forbid( string $message, string $title = '', array $args = array() ): void {
		$this->forbid_calls[] = array(
			'message' => $message,
			'title'   => $title,
			'args'    => $args,
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception text is asserted in tests, not rendered.
		throw new AdminAccessDenied( $message );
	}
}
