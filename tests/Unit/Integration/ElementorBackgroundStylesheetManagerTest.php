<?php
/**
 * Tests for the Elementor background stylesheet manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundBreakpointMap;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetGenerator;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetManager;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the companion stylesheet manager stays bounded to late frontend enqueue behavior.
 */
final class ElementorBackgroundStylesheetManagerTest extends TestCase {

	/**
	 * Test the manager registers only wp_enqueue_scripts.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_wp_enqueue_scripts(): void {
		$hooks = new HookRegistrar();

		$this->manager()->register_hooks( $hooks );

		self::assertSame( array(), $hooks->filters() );
		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'wp_enqueue_scripts', $hooks->actions()[0]['hook'] );
	}

	/**
	 * Test the manager does nothing for ineligible runtime states.
	 *
	 * @return void
	 */
	public function test_manager_does_nothing_for_ineligible_runtime_states(): void {
		$settings                  = new FakeSettingsRepository(
			array(
				'delivery_enabled'            => true,
				'delivery_emergency_disabled' => false,
			)
		);
		$runtime                   = new FakeElementorBackgroundStylesheetRuntime();
		$runtime->frontend_request = false;
		$manager                   = $this->manager( $settings, null, $runtime );

		$manager->enqueue_current_document_stylesheet();

		self::assertSame( array(), $runtime->enqueued );
	}

	/**
	 * Test the manager does nothing when Elementor background delivery is disabled.
	 *
	 * @return void
	 */
	public function test_manager_does_nothing_when_background_delivery_is_disabled(): void {
		$settings                  = new FakeSettingsRepository(
			array(
				'delivery_enabled'                      => true,
				'elementor_background_delivery_enabled' => false,
				'delivery_emergency_disabled'           => false,
			)
		);
		$runtime                   = new FakeElementorBackgroundStylesheetRuntime();
		$runtime->frontend_request = true;
		$runtime->document_id      = 501;
		$manager                   = $this->manager( $settings, null, $runtime );

		$manager->enqueue_current_document_stylesheet();

		self::assertSame( array(), $runtime->enqueued );
	}

	/**
	 * Test the manager enqueues one companion stylesheet for an eligible current document.
	 *
	 * @return void
	 */
	public function test_manager_enqueues_one_companion_stylesheet_for_an_eligible_document(): void {
		$settings                  = new FakeSettingsRepository(
			array(
				'delivery_enabled'            => true,
				'delivery_emergency_disabled' => false,
			)
		);
		$runtime                   = new FakeElementorBackgroundStylesheetRuntime();
		$runtime->frontend_request = true;
		$runtime->document_id      = 501;
		$runtime->breakpoint_map   = ElementorBackgroundBreakpointMap::from_max_widths( 767, 1024 );
		$store                     = new FakeElementorBackgroundStylesheetStore();
		$manager                   = $this->manager( $settings, $store, $runtime );

		$manager->enqueue_current_document_stylesheet();

		self::assertCount( 1, $runtime->enqueued );
		self::assertSame( 'hwlio-elementor-backgrounds-501', $runtime->enqueued[0]['handle'] );
		self::assertStringContainsString( '501.hwlio-backgrounds.css', $runtime->enqueued[0]['url'] );
		self::assertTrue( $store->exists( 501 ) );
	}

	/**
	 * Test regeneration reuses the stored artifact when the signature is already current.
	 *
	 * @return void
	 */
	public function test_regeneration_reuses_the_stored_artifact_when_current(): void {
		$runtime                 = new FakeElementorBackgroundStylesheetRuntime();
		$runtime->document_id    = 501;
		$runtime->breakpoint_map = ElementorBackgroundBreakpointMap::from_max_widths( 767, 1024 );
		$store                   = new FakeElementorBackgroundStylesheetStore();
		$manager                 = $this->manager( null, $store, $runtime );
		$first                   = $manager->regenerate_document( 501 );

		self::assertTrue( $first->is_ready() );
		self::assertTrue( $store->exists( 501 ) );

		$writes_before = count( $store->writes );
		$second        = $manager->regenerate_document( 501 );

		self::assertTrue( $second->is_ready() );
		self::assertSame( 'stylesheet_current', $second->code() );
		self::assertCount( $writes_before, $store->writes );
	}

	/**
	 * Test rollback removes only the plugin-owned companion stylesheet.
	 *
	 * @return void
	 */
	public function test_rollback_removes_the_plugin_owned_companion_stylesheet(): void {
		$store                = new FakeElementorBackgroundStylesheetStore();
		$store->contents[501] = '/* existing */';
		$manager              = $this->manager( null, $store );

		$result = $manager->rollback_document( 501 );

		self::assertTrue( $result->is_noop() );
		self::assertSame( 'stylesheet_deleted', $result->code() );
		self::assertFalse( $store->exists( 501 ) );
	}

	/**
	 * Build one manager fixture.
	 *
	 * @param FakeSettingsRepository|null                   $settings Settings.
	 * @param FakeElementorBackgroundStylesheetStore|null   $store Store.
	 * @param FakeElementorBackgroundStylesheetRuntime|null $runtime Runtime.
	 * @return ElementorBackgroundStylesheetManager
	 */
	private function manager(
		?FakeSettingsRepository $settings = null,
		?FakeElementorBackgroundStylesheetStore $store = null,
		?FakeElementorBackgroundStylesheetRuntime $runtime = null
	): ElementorBackgroundStylesheetManager {
		$settings          = $settings ?? new FakeSettingsRepository(
			array(
				'delivery_enabled'            => true,
				'delivery_emergency_disabled' => false,
				'format_preference'           => array( 'avif', 'webp' ),
			)
		);
		$runtime           = $runtime ?? new FakeElementorBackgroundStylesheetRuntime();
		$store             = $store ?? new FakeElementorBackgroundStylesheetStore();
		$elementor_runtime = new FakeElementorRuntime();
		$meta_store        = new FakeAttachmentMetaStore();

		$meta_store->meta[901][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 1,
			'fingerprint'    => null,
			'updated_at'     => 1783987200,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero-desktop.jpg',
						'mime'   => 'image/jpeg',
						'width'  => 1600,
						'height' => 900,
						'bytes'  => 250000,
					),
					'formats' => array(
						'avif' => array(
							'status' => 'ready',
							'file'   => '2026/07/hero-desktop.jpg.hwlio.avif',
							'mime'   => 'image/avif',
						),
						'webp' => array(
							'status' => 'ready',
							'file'   => '2026/07/hero-desktop.jpg.hwlio.webp',
							'mime'   => 'image/webp',
						),
					),
				),
			),
		);

		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = 'C:/site/wp-content/uploads';

		$document_store           = new FakeElementorDocumentDataStore();
		$document_store->document = ElementorDocumentData::valid(
			require dirname( __DIR__, 2 ) . '/Fixtures/Elementor/BackgroundDiscovery/background-classic-desktop.php'
		);

		return new ElementorBackgroundStylesheetManager(
			$settings,
			$elementor_runtime,
			$runtime,
			new ElementorBackgroundStylesheetGenerator(
				new ElementorBackgroundDiscovery( $document_store ),
				new DerivativeRepository( $meta_store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
				new DerivativeUrlResolver( $uploads, new DerivativeManifestSanitizer() ),
				$settings,
				new DerivativeManifestSanitizer(),
				static function (): array {
					return array(
						'basedir' => 'C:/site/wp-content/uploads',
						'baseurl' => 'https://example.test/wp-content/uploads',
						'error'   => '',
					);
				}
			),
			$store
		);
	}
}
