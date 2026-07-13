<?php
/**
 * Tests for the critical-image editor assets.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageAssets;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\FakeAdminAssetRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies post/page-editor-scoped media-picker asset loading.
 */
final class CriticalImageAssetsTest extends TestCase {

	/**
	 * Test hook registration adds only the scoped editor asset hook.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_the_editor_asset_hook(): void {
		$hooks = new HookRegistrar();

		$this->provider()['provider']->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'admin_enqueue_scripts', $hooks->actions()[0]['hook'] );
	}

	/**
	 * Test unrelated screens do not load post editor assets.
	 *
	 * @return void
	 */
	public function test_unrelated_screens_do_not_load_assets(): void {
		$fixture = $this->provider();

		$fixture['provider']->enqueue_editor_assets( 'upload.php' );

		self::assertFalse( $fixture['runtime']->media_enqueued );
		self::assertSame( array(), $fixture['assets']->scripts );
	}

	/**
	 * Test supported post editor screens load media picker assets once without jQuery.
	 *
	 * @return void
	 */
	public function test_supported_editor_screens_load_media_picker_assets_once_without_jquery(): void {
		$fixture = $this->provider();

		$fixture['provider']->enqueue_editor_assets( 'post.php' );
		$fixture['provider']->enqueue_editor_assets( 'post.php' );

		self::assertTrue( $fixture['runtime']->media_enqueued );
		self::assertCount( 1, $fixture['assets']->scripts );
		self::assertCount( 1, $fixture['assets']->inline_scripts );
		self::assertNotContains( 'jquery', $fixture['assets']->scripts[0]['deps'] );
		self::assertStringContainsString( 'window.hwlioCriticalImageEditor = ', $fixture['assets']->inline_scripts[0]['data'] );
	}

	/**
	 * Build provider fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function provider(): array {
		$runtime            = new FakePostEditorRuntime();
		$runtime->post_type = 'post';
		$assets             = new FakeAdminAssetRuntime();
		$provider           = new CriticalImageAssets(
			$runtime,
			$assets,
			'https://example.test/wp-content/plugins/hwlio/',
			'1.2.3'
		);

		return array(
			'provider' => $provider,
			'runtime'  => $runtime,
			'assets'   => $assets,
		);
	}
}
