<?php
/**
 * Tests for the admin menu helper.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\Menu;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies menu registration, routing, and screen scoping.
 */
final class MenuTest extends TestCase {

	/**
	 * Test menu registration uses the Media submenu and captures the screen ID.
	 *
	 * @return void
	 */
	public function test_register_uses_media_submenu_and_captures_screen_id(): void {
		$runtime = new FakeAdminRuntime();
		$menu    = new Menu( $runtime );

		$menu->register(
			static function (): void {
			}
		);

		self::assertCount( 1, $runtime->submenu_calls );
		self::assertSame( Menu::PARENT_SLUG, $runtime->submenu_calls[0]['parent_slug'] );
		self::assertSame( SettingsSchema::CAPABILITY_MANAGE_OPTIONS, $runtime->submenu_calls[0]['capability'] );
		self::assertSame( Menu::MENU_SLUG, $runtime->submenu_calls[0]['menu_slug'] );
		self::assertSame( array( 'media_page_hwlio' ), $menu->screen_ids() );
		self::assertTrue( $menu->is_plugin_screen( 'media_page_hwlio' ) );
		self::assertFalse( $menu->is_plugin_screen( 'upload' ) );
	}

	/**
	 * Test known tabs resolve as-is and unknown tabs fall back to dashboard.
	 *
	 * @return void
	 */
	public function test_resolve_tab_accepts_known_tabs_and_falls_back(): void {
		$menu = new Menu( new FakeAdminRuntime() );

		self::assertSame( 'dashboard', $menu->resolve_tab( 'dashboard' ) );
		self::assertSame( 'bulk-optimize', $menu->resolve_tab( 'bulk-optimize' ) );
		self::assertSame( 'settings', $menu->resolve_tab( 'settings' ) );
		self::assertSame( 'diagnostics', $menu->resolve_tab( 'diagnostics' ) );
		self::assertSame( 'logs', $menu->resolve_tab( 'logs' ) );
		self::assertSame( 'dashboard', $menu->resolve_tab( 'not-real' ) );
		self::assertSame( 'dashboard', $menu->resolve_tab( null ) );
	}

	/**
	 * Test menu URL generation uses the shell slug and optional tab query.
	 *
	 * @return void
	 */
	public function test_page_url_uses_shell_slug_and_tab_query(): void {
		$menu = new Menu( new FakeAdminRuntime() );

		self::assertSame(
			'https://example.test/wp-admin/upload.php?page=hwlio',
			$menu->page_url()
		);
		self::assertSame(
			'https://example.test/wp-admin/upload.php?page=hwlio&tab=settings',
			$menu->page_url( 'settings' )
		);
	}
}
