<?php
/**
 * Tests for the admin assets provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminScreenContextResolver;
use HyperWeb\LighthouseImageOptimizer\Admin\Assets;
use HyperWeb\LighthouseImageOptimizer\Admin\Menu;
use HyperWeb\LighthouseImageOptimizer\Admin\NoticeManager;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Verifies screen-scoped asset loading and bootstrap generation.
 */
final class AssetsTest extends TestCase {

	/**
	 * Test hook registration adds only the admin enqueue action.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_admin_enqueue_action(): void {
		$hooks  = new HookRegistrar();
		$assets = $this->provider();

		$assets->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'admin_enqueue_scripts', $hooks->actions()[0]['hook'] );
		self::assertSame( 1, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test unrelated admin screens do not load plugin assets.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_skips_unrelated_admin_screens(): void {
		$runtime       = new FakeAdminRuntime();
		$asset_runtime = new FakeAdminAssetRuntime();
		$menu          = new Menu( $runtime );
		$menu->register(
			static function (): void {
			}
		);

		$assets = $this->provider(
			$runtime,
			$asset_runtime,
			static function (): array {
				return array(
					'page' => 'plugins',
				);
			},
			$menu
		);

		$assets->enqueue_assets( 'plugins' );

		self::assertSame( array(), $asset_runtime->styles );
		self::assertSame( array(), $asset_runtime->scripts );
		self::assertSame( array(), $asset_runtime->inline_scripts );
	}

	/**
	 * Test the plugin screen enqueues the expected assets and bootstrap data.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_loads_scoped_assets_and_bootstrap(): void {
		$runtime       = new FakeAdminRuntime();
		$asset_runtime = new FakeAdminAssetRuntime();
		$menu          = new Menu( $runtime );
		$menu->register(
			static function (): void {
			}
		);

		$assets = $this->provider(
			$runtime,
			$asset_runtime,
			static function (): array {
				return array(
					'page' => 'hwlio',
					'tab'  => 'bulk-optimize',
				);
			},
			$menu
		);

		$assets->enqueue_assets( 'media_page_hwlio' );

		self::assertCount( 1, $asset_runtime->styles );
		self::assertCount( 1, $asset_runtime->scripts );
		self::assertCount( 1, $asset_runtime->inline_scripts );
		self::assertSame( Assets::STYLE_HANDLE, $asset_runtime->styles[0]['handle'] );
		self::assertSame( Assets::SCRIPT_HANDLE, $asset_runtime->scripts[0]['handle'] );
		self::assertSame( array( 'wp-api-fetch' ), $asset_runtime->scripts[0]['deps'] );
		self::assertNotContains( 'jquery', $asset_runtime->scripts[0]['deps'] );
		self::assertTrue( $asset_runtime->scripts[0]['in_footer'] );
		self::assertStringContainsString( 'window.hwlioAdminConfig = ', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( 'https:\\/\\/example.test\\/wp-json\\/hwlio\\/v1\\/', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( 'rest-nonce', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"pageSlug":"hwlio"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"screenId":"media_page_hwlio"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"currentTab":"bulk-optimize"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"jobsScanRoute":"\\/jobs\\/scan"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"jobsQueueRoute":"\\/jobs\\/queue"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"jobsPauseRoute":"\\/jobs\\/pause"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"attachmentsRoute":"\\/attachments"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"route":"\\/diagnostics"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"retentionRoute":"\\/logs\\/retention"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"storageKey":"hwlioBulkScanToken"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"queueModeKey":"hwlioBulkQueueMode"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"recalculateAction":"Recalculate Statistics"', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"diagnosticsLoadError":"Diagnostics could not be loaded right now."', $asset_runtime->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"logsLoadError":"Logs could not be loaded right now."', $asset_runtime->inline_scripts[0]['data'] );
	}

	/**
	 * Build the provider under test.
	 *
	 * @param FakeAdminRuntime|null      $runtime Menu runtime.
	 * @param FakeAdminAssetRuntime|null $asset_runtime Asset runtime.
	 * @param callable|null              $query_provider Query provider.
	 * @param Menu|null                  $menu Shared menu helper.
	 * @return Assets
	 */
	private function provider(
		?FakeAdminRuntime $runtime = null,
		?FakeAdminAssetRuntime $asset_runtime = null,
		?callable $query_provider = null,
		?Menu $menu = null
	): Assets {
		$runtime       = $runtime ?? new FakeAdminRuntime();
		$asset_runtime = $asset_runtime ?? new FakeAdminAssetRuntime();
		$menu          = $menu ?? new Menu( $runtime );

		return new Assets(
			$menu,
			new AdminScreenContextResolver( $menu, $query_provider ),
			$asset_runtime,
			new NoticeManager(),
			'https://example.test/wp-content/plugins/hwlio/',
			'1.2.3'
		);
	}
}
