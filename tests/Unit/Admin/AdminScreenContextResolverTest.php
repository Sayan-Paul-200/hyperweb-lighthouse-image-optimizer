<?php
/**
 * Tests for the admin screen context resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminScreenContextResolver;
use HyperWeb\LighthouseImageOptimizer\Admin\Menu;
use PHPUnit\Framework\TestCase;

/**
 * Verifies normalized tab and plugin-screen resolution.
 */
final class AdminScreenContextResolverTest extends TestCase {

	/**
	 * Test current-tab normalization matches the shell allowlist.
	 *
	 * @return void
	 */
	public function test_resolve_normalizes_known_and_invalid_tabs(): void {
		$runtime = new FakeAdminRuntime();
		$menu    = new Menu( $runtime );
		$menu->register(
			static function (): void {
			}
		);

		$resolver = new AdminScreenContextResolver(
			$menu,
			static function (): array {
				return array(
					'page' => 'hwlio',
					'tab'  => 'diagnostics',
				);
			}
		);

		$context = $resolver->resolve( 'media_page_hwlio' );

		self::assertTrue( $context->is_plugin_screen() );
		self::assertSame( 'diagnostics', $context->current_tab() );
		self::assertSame( 'media_page_hwlio', $context->screen_id() );

		$invalid = new AdminScreenContextResolver(
			$menu,
			static function (): array {
				return array(
					'page' => 'hwlio',
					'tab'  => 'not-real',
				);
			}
		);

		self::assertSame( 'dashboard', $invalid->resolve( 'media_page_hwlio' )->current_tab() );
	}

	/**
	 * Test non-plugin requests do not resolve as plugin screens.
	 *
	 * @return void
	 */
	public function test_resolve_marks_non_plugin_requests_false(): void {
		$runtime = new FakeAdminRuntime();
		$menu    = new Menu( $runtime );
		$menu->register(
			static function (): void {
			}
		);

		$resolver = new AdminScreenContextResolver(
			$menu,
			static function (): array {
				return array(
					'page' => 'upload',
				);
			}
		);

		$context = $resolver->resolve( 'upload' );

		self::assertFalse( $context->is_plugin_screen() );
		self::assertSame( 'dashboard', $context->current_tab() );
		self::assertSame( 'upload', $context->screen_id() );
	}
}
