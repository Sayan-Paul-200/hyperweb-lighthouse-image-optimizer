<?php
/**
 * Tests for the critical-image meta box.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageMetaBox;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies post/page editor critical-image persistence behavior.
 */
final class CriticalImageMetaBoxTest extends TestCase {

	/**
	 * Clean up superglobals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_POST = array();
	}

	/**
	 * Test hook registration adds only the meta-box and save hooks.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_meta_box_and_save_hooks(): void {
		$hooks = new HookRegistrar();

		$this->provider()['provider']->register_hooks( $hooks );

		self::assertSame(
			array( 'add_meta_boxes', 'save_post' ),
			array_map(
				static function ( array $action ): string {
					return $action['hook'];
				},
				$hooks->actions()
			)
		);
	}

	/**
	 * Test the meta box registers only on post and page screens.
	 *
	 * @return void
	 */
	public function test_meta_box_registers_only_on_post_and_page(): void {
		$fixture = $this->provider();

		$fixture['provider']->register_meta_boxes( 'post', (object) array( 'ID' => 9 ) );
		$fixture['provider']->register_meta_boxes( 'page', (object) array( 'ID' => 9 ) );
		$fixture['provider']->register_meta_boxes( 'product', (object) array( 'ID' => 9 ) );

		self::assertCount( 2, $fixture['runtime']->meta_boxes );
		self::assertSame( 'post', $fixture['runtime']->meta_boxes[0]['screen'] );
		self::assertSame( 'page', $fixture['runtime']->meta_boxes[1]['screen'] );
	}

	/**
	 * Test save requires a valid nonce and edit capability.
	 *
	 * @return void
	 */
	public function test_save_requires_nonce_and_edit_capability(): void {
		$fixture                 = $this->provider();
		$fixture['runtime']->can = false;
		$_POST                   = array(
			CriticalImageMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			CriticalImageMetaBox::FIELD_NAME  => '123',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );

		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test autosaves and revisions do not persist critical-image state.
	 *
	 * @return void
	 */
	public function test_autosaves_and_revisions_do_not_persist(): void {
		$fixture                      = $this->provider();
		$fixture['runtime']->autosave = true;
		$_POST                        = array(
			CriticalImageMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			CriticalImageMetaBox::FIELD_NAME  => '123',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );
		self::assertSame( array(), $fixture['store']->values );

		$fixture['runtime']->autosave = false;
		$fixture['runtime']->revision = true;
		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );
		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test invalid or empty values delete stored meta instead of saving zero.
	 *
	 * @return void
	 */
	public function test_invalid_or_empty_values_delete_meta(): void {
		$fixture                      = $this->provider();
		$fixture['store']->values[44] = 999;
		$_POST                        = array(
			CriticalImageMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			CriticalImageMetaBox::FIELD_NAME  => '',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );

		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test valid image selections persist to the narrow meta store.
	 *
	 * @return void
	 */
	public function test_valid_image_selection_persists(): void {
		$fixture = $this->provider();
		$_POST   = array(
			CriticalImageMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			CriticalImageMetaBox::FIELD_NAME  => '123',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'page' ), true );

		self::assertSame( 123, $fixture['store']->values[44] );
	}

	/**
	 * Test rendered output includes the hidden field, preview, and actions.
	 *
	 * @return void
	 */
	public function test_render_outputs_hidden_field_preview_and_actions(): void {
		$fixture                      = $this->provider();
		$fixture['store']->values[44] = 123;

		ob_start();
		$fixture['provider']->render_meta_box( (object) array( 'ID' => 44 ) );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'name="hwlio_critical_image_id"', $output );
		self::assertStringContainsString( 'Hero image', $output );
		self::assertStringContainsString( 'Replace image', $output );
		self::assertStringContainsString( 'Clear', $output );
	}

	/**
	 * Build provider fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function provider(): array {
		$runtime  = new FakePostEditorRuntime();
		$store    = new FakeCriticalImagePostMetaStore();
		$provider = new CriticalImageMetaBox( $runtime, $store );

		return array(
			'provider' => $provider,
			'runtime'  => $runtime,
			'store'    => $store,
		);
	}
}
