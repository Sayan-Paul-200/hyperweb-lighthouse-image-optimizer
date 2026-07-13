<?php
/**
 * Tests for the Media Library asset provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryAssets;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\FakeAdminAssetRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies media-screen-scoped CSS, JS, and bootstrap loading.
 */
final class MediaLibraryAssetsTest extends TestCase {

	/**
	 * Test hook registration adds only the media-screen asset hooks.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_media_asset_hooks(): void {
		$hooks  = new HookRegistrar();
		$assets = $this->provider()['provider'];

		$assets->register_hooks( $hooks );

		self::assertSame(
			array( 'admin_enqueue_scripts', 'wp_enqueue_media' ),
			array_map(
				static function ( array $action ): string {
					return $action['hook'];
				},
				$hooks->actions()
			)
		);
	}

	/**
	 * Test unrelated admin screens do not enqueue assets.
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_skips_unrelated_screens(): void {
		$fixture = $this->provider();

		$fixture['provider']->enqueue_admin_assets( 'plugins.php' );

		self::assertSame( array(), $fixture['assets']->styles );
		self::assertSame( array(), $fixture['assets']->scripts );
		self::assertSame( array(), $fixture['assets']->inline_scripts );
	}

	/**
	 * Test upload.php enqueues the expected media assets and bootstrap.
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_loads_scoped_media_assets(): void {
		$fixture = $this->provider();

		$fixture['provider']->enqueue_admin_assets( 'upload.php' );

		self::assertCount( 1, $fixture['assets']->styles );
		self::assertCount( 1, $fixture['assets']->scripts );
		self::assertCount( 1, $fixture['assets']->inline_scripts );
		self::assertSame( array( 'wp-api-fetch', 'media-views' ), $fixture['assets']->scripts[0]['deps'] );
		self::assertNotContains( 'jquery', $fixture['assets']->scripts[0]['deps'] );
		self::assertTrue( $fixture['assets']->scripts[0]['in_footer'] );
		self::assertStringContainsString( 'window.hwlioMediaLibraryConfig = ', $fixture['assets']->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"automaticOptimization":false', $fixture['assets']->inline_scripts[0]['data'] );
		self::assertStringContainsString( '"allowAttachmentExclusion":false', $fixture['assets']->inline_scripts[0]['data'] );
	}

	/**
	 * Test wp_enqueue_media also loads the assets once for modal screens.
	 *
	 * @return void
	 */
	public function test_enqueue_media_modal_assets_loads_once(): void {
		$fixture = $this->provider();

		$fixture['provider']->enqueue_media_modal_assets();
		$fixture['provider']->enqueue_media_modal_assets();

		self::assertCount( 1, $fixture['assets']->styles );
		self::assertCount( 1, $fixture['assets']->scripts );
		self::assertCount( 1, $fixture['assets']->inline_scripts );
	}

	/**
	 * Build the provider fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function provider(): array {
		$runtime  = new FakeMediaLibraryRuntime();
		$assets   = new FakeAdminAssetRuntime();
		$settings = new FakeSettingsRepository(
			array(
				'automatic_optimization'     => false,
				'allow_attachment_exclusion' => false,
			)
		);

		return array(
			'provider' => new MediaLibraryAssets(
				$runtime,
				$assets,
				$settings,
				'https://example.test/wp-content/plugins/hwlio/',
				'1.2.3'
			),
			'assets'   => $assets,
		);
	}
}
