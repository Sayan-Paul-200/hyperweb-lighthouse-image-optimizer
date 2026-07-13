<?php
/**
 * Tests for the admin controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminController;
use HyperWeb\LighthouseImageOptimizer\Admin\AdminScreenContextResolver;
use HyperWeb\LighthouseImageOptimizer\Admin\BulkPage;
use HyperWeb\LighthouseImageOptimizer\Admin\DashboardPage;
use HyperWeb\LighthouseImageOptimizer\Admin\DiagnosticsPage;
use HyperWeb\LighthouseImageOptimizer\Admin\LogsPage;
use HyperWeb\LighthouseImageOptimizer\Admin\Menu;
use HyperWeb\LighthouseImageOptimizer\Admin\NoticeManager;
use HyperWeb\LighthouseImageOptimizer\Admin\SettingsPage;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Verifies menu-hook registration and shell rendering behavior.
 */
final class AdminControllerTest extends TestCase {

	/**
	 * Test hook registration adds only the admin-menu action.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_admin_menu_action(): void {
		$hooks      = new HookRegistrar();
		$controller = $this->controller( array() );

		$controller->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'admin_menu', $hooks->actions()[0]['hook'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test the controller registers the submenu through the menu helper.
	 *
	 * @return void
	 */
	public function test_register_menu_uses_media_submenu_registration(): void {
		$runtime    = new FakeAdminRuntime();
		$menu       = new Menu( $runtime );
		$controller = $this->controller( array(), $runtime, $menu );

		$controller->register_menu();

		self::assertCount( 1, $runtime->submenu_calls );
		self::assertSame( Menu::PARENT_SLUG, $runtime->submenu_calls[0]['parent_slug'] );
		self::assertSame( Menu::MENU_SLUG, $runtime->submenu_calls[0]['menu_slug'] );
	}

	/**
	 * Test valid tabs render the expected shell content.
	 *
	 * @return void
	 */
	public function test_render_uses_requested_valid_tab(): void {
		$controller = $this->controller( array( 'tab' => 'settings' ) );

		ob_start();
		$controller->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Lighthouse Image Optimizer', $output );
		self::assertStringContainsString( '>Settings<', $output );
		self::assertStringContainsString( 'The visible settings form will be added', $output );
		self::assertStringContainsString( 'tab=settings', $output );
		self::assertStringContainsString( 'id="hwlio-admin-notices"', $output );
		self::assertStringContainsString( 'id="hwlio-admin-app"', $output );
		self::assertStringContainsString( 'data-page="hwlio"', $output );
		self::assertStringContainsString( 'data-tab="settings"', $output );
	}

	/**
	 * Test invalid tabs fall back to the dashboard shell.
	 *
	 * @return void
	 */
	public function test_render_falls_back_to_dashboard_for_invalid_tab(): void {
		$controller = $this->controller( array( 'tab' => 'invalid-tab' ) );

		ob_start();
		$controller->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '>Dashboard<', $output );
		self::assertStringContainsString( 'data-hwlio-dashboard="root"', $output );
		self::assertStringContainsString( 'data-hwlio-dashboard-action="recalculate"', $output );
		self::assertStringContainsString( 'data-hwlio-dashboard-panel="environment"', $output );
		self::assertStringNotContainsString( 'tab=dashboard', $output );
		self::assertStringContainsString( 'id="hwlio-admin-live-polite"', $output );
		self::assertStringContainsString( 'id="hwlio-admin-live-assertive"', $output );
	}

	/**
	 * Test the bulk tab renders the dry-run scan shell.
	 *
	 * @return void
	 */
	public function test_render_outputs_bulk_scan_shell(): void {
		$controller = $this->controller( array( 'tab' => 'bulk-optimize' ) );

		ob_start();
		$controller->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'data-hwlio-bulk="root"', $output );
		self::assertStringContainsString( 'data-hwlio-bulk-form', $output );
		self::assertStringContainsString( 'data-hwlio-bulk-summary', $output );
		self::assertStringContainsString( 'data-hwlio-bulk-preview-body', $output );
		self::assertStringContainsString( 'Queue Current Scan Results', $output );
		self::assertStringContainsString( 'data-hwlio-bulk-queue-status', $output );
	}

	/**
	 * Test the diagnostics tab renders the structured diagnostics shell.
	 *
	 * @return void
	 */
	public function test_render_outputs_diagnostics_shell(): void {
		$controller = $this->controller( array( 'tab' => 'diagnostics' ) );

		ob_start();
		$controller->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'data-hwlio-diagnostics="root"', $output );
		self::assertStringContainsString( 'data-hwlio-diagnostics-action="refresh"', $output );
		self::assertStringContainsString( 'data-hwlio-diagnostics-summary', $output );
		self::assertStringContainsString( 'data-hwlio-diagnostics-groups', $output );
	}

	/**
	 * Test the logs tab renders filters, maintenance controls, and the paginated table shell.
	 *
	 * @return void
	 */
	public function test_render_outputs_logs_shell(): void {
		$controller = $this->controller( array( 'tab' => 'logs' ) );

		ob_start();
		$controller->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'data-hwlio-logs="root"', $output );
		self::assertStringContainsString( 'data-hwlio-logs-filters', $output );
		self::assertStringContainsString( 'data-hwlio-logs-action="save-retention"', $output );
		self::assertStringContainsString( 'data-hwlio-logs-action="clear-all"', $output );
		self::assertStringContainsString( 'data-hwlio-logs-body', $output );
		self::assertStringContainsString( 'data-hwlio-logs-page-status', $output );
	}

	/**
	 * Test unauthorized direct access uses the forbidden path.
	 *
	 * @return void
	 */
	public function test_render_denies_unauthorized_access(): void {
		$runtime      = new FakeAdminRuntime();
		$runtime->can = false;
		$controller   = $this->controller( array(), $runtime );

		$this->expectException( AdminAccessDenied::class );

		try {
			$controller->render();
		} finally {
			self::assertSame( array( 'manage_options' ), $runtime->capability_checks );
			self::assertCount( 1, $runtime->forbid_calls );
			self::assertSame( 403, $runtime->forbid_calls[0]['args']['response'] );
		}
	}

	/**
	 * Build the controller under test.
	 *
	 * @param array<string,mixed>   $query Query data.
	 * @param FakeAdminRuntime|null $runtime Optional runtime.
	 * @param Menu|null            $menu Optional menu helper.
	 * @return AdminController
	 */
	private function controller( array $query, ?FakeAdminRuntime $runtime = null, ?Menu $menu = null ): AdminController {
		$runtime = $runtime ?? new FakeAdminRuntime();
		$menu    = $menu ?? new Menu( $runtime );

		return new AdminController(
			$menu,
			array(
				new DashboardPage(),
				new BulkPage(),
				new SettingsPage(),
				new DiagnosticsPage(),
				new LogsPage(),
			),
			$runtime,
			new AdminScreenContextResolver(
				$menu,
				static function () use ( $query ): array {
					return array_merge(
						array(
							'page' => 'hwlio',
						),
						$query
					);
				}
			),
			new NoticeManager()
		);
	}
}
