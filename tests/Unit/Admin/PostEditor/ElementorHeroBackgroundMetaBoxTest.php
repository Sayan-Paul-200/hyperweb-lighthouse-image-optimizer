<?php
/**
 * Tests for the Elementor hero background meta box.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\ElementorHeroBackgroundMetaBox;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundTargetSelection;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorDocumentDataStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorHeroBackgroundPostMetaStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies post/page editor hero-background selection behavior.
 */
final class ElementorHeroBackgroundMetaBoxTest extends TestCase {

	/**
	 * Clean up superglobals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_POST = array();
	}

	/**
	 * Test hook registration adds only meta-box and save hooks.
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
	 * Test rendered output lists only supported discovered targets.
	 *
	 * @return void
	 */
	public function test_render_outputs_supported_target_options(): void {
		$fixture                      = $this->provider();
		$fixture['store']->values[44] = new ElementorHeroBackgroundTargetSelection( 'hero-section', 'background' );

		ob_start();
		$fixture['provider']->render_meta_box( (object) array( 'ID' => 44 ) );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'name="hwlio_elementor_hero_background_target"', $output );
		self::assertStringContainsString( 'hero-section (Background)', $output );
		self::assertStringContainsString( 'overlay-column (Background overlay)', $output );
		self::assertStringContainsString( 'selected', $output );
	}

	/**
	 * Test save requires nonce and capability.
	 *
	 * @return void
	 */
	public function test_save_requires_nonce_and_capability(): void {
		$fixture                 = $this->provider();
		$fixture['runtime']->can = false;
		$_POST                   = array(
			ElementorHeroBackgroundMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			ElementorHeroBackgroundMetaBox::FIELD_NAME  => 'hero-section|background',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );

		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test autosaves and revisions do not persist.
	 *
	 * @return void
	 */
	public function test_autosaves_and_revisions_do_not_persist(): void {
		$fixture                      = $this->provider();
		$fixture['runtime']->autosave = true;
		$_POST                        = array(
			ElementorHeroBackgroundMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			ElementorHeroBackgroundMetaBox::FIELD_NAME  => 'hero-section|background',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'page' ), true );
		self::assertSame( array(), $fixture['store']->values );

		$fixture['runtime']->autosave = false;
		$fixture['runtime']->revision = true;
		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'page' ), true );
		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test invalid or stale selections delete stored state.
	 *
	 * @return void
	 */
	public function test_invalid_or_stale_selection_deletes_stored_state(): void {
		$fixture                      = $this->provider();
		$fixture['store']->values[44] = new ElementorHeroBackgroundTargetSelection( 'hero-section', 'background' );
		$_POST                        = array(
			ElementorHeroBackgroundMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			ElementorHeroBackgroundMetaBox::FIELD_NAME  => 'missing|background',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );

		self::assertSame( array(), $fixture['store']->values );
	}

	/**
	 * Test valid supported selections persist.
	 *
	 * @return void
	 */
	public function test_valid_selection_persists(): void {
		$fixture = $this->provider();
		$_POST   = array(
			ElementorHeroBackgroundMetaBox::NONCE_FIELD => $fixture['runtime']->nonce,
			ElementorHeroBackgroundMetaBox::FIELD_NAME  => 'overlay-column|background_overlay',
		);

		$fixture['provider']->save_post( 44, (object) array( 'post_type' => 'post' ), true );

		self::assertArrayHasKey( 44, $fixture['store']->values );
		self::assertSame( 'overlay-column|background_overlay', $fixture['store']->values[44]->key() );
	}

	/**
	 * Build provider fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function provider(): array {
		$runtime                  = new FakePostEditorRuntime();
		$store                    = new FakeElementorHeroBackgroundPostMetaStore();
		$document_store           = new FakeElementorDocumentDataStore();
		$document_store->document = ElementorDocumentData::valid(
			array(
				array(
					'id'       => 'hero-section',
					'elType'   => 'section',
					'settings' => array(
						'background_background' => 'classic',
						'background_image'      => array(
							'id'  => 901,
							'url' => 'https://example.test/wp-content/uploads/2026/07/hero-desktop.jpg',
						),
					),
				),
				array(
					'id'       => 'overlay-column',
					'elType'   => 'column',
					'settings' => array(
						'background_overlay_background' => 'classic',
						'background_overlay_image'      => array(
							'id'  => 905,
							'url' => 'https://example.test/wp-content/uploads/2026/07/overlay-image.jpg',
						),
					),
				),
			)
		);

		$provider = new ElementorHeroBackgroundMetaBox(
			$runtime,
			$store,
			new ElementorBackgroundDiscovery( $document_store )
		);

		return array(
			'provider' => $provider,
			'runtime'  => $runtime,
			'store'    => $store,
		);
	}
}
