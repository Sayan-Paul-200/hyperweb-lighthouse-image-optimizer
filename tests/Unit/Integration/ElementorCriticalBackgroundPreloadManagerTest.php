<?php
/**
 * Tests for the Elementor critical background preload manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadRegistry;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundBreakpointMap;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDeliveryPlanBuilder;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorCriticalBackgroundPreloadManager;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundPreloadResult;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundTargetSelection;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies critical background preload stays opt-in, explicit, and fail-open.
 */
final class ElementorCriticalBackgroundPreloadManagerTest extends TestCase {

	/**
	 * Test hook registration adds only wp_head.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_wp_head(): void {
		$hooks = new HookRegistrar();

		$this->manager()['manager']->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'wp_head', $hooks->actions()[0]['hook'] );
		self::assertSame( 1, $hooks->actions()[0]['priority'] );
	}

	/**
	 * Test disabled preload emits nothing.
	 *
	 * @return void
	 */
	public function test_disabled_preload_emits_nothing(): void {
		$result = $this->manager()['manager']->build_for_current_request();

		self::assertSame( ElementorBackgroundPreloadResult::CODE_DISABLED, $result->code() );
		self::assertFalse( $result->is_ready() );
	}

	/**
	 * Test missing selection emits nothing.
	 *
	 * @return void
	 */
	public function test_missing_selection_emits_nothing(): void {
		$fixture = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			),
			'background-classic-desktop.php',
			null,
			new FakeElementorHeroBackgroundPostMetaStore()
		);

		self::assertSame( ElementorBackgroundPreloadResult::CODE_NO_SELECTED_TARGET, $fixture['manager']->build_for_current_request()->code() );
	}

	/**
	 * Test stale selections fail open.
	 *
	 * @return void
	 */
	public function test_stale_selections_fail_open(): void {
		$store             = new FakeElementorHeroBackgroundPostMetaStore();
		$store->values[77] = new ElementorHeroBackgroundTargetSelection( 'missing', 'background' );
		$fixture           = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			),
			'background-classic-desktop.php',
			null,
			$store
		);

		self::assertSame( ElementorBackgroundPreloadResult::CODE_STALE_INVALID_SELECTION, $fixture['manager']->build_for_current_request()->code() );
	}

	/**
	 * Test desktop-only hero backgrounds emit one preload tag.
	 *
	 * @return void
	 */
	public function test_desktop_only_hero_background_emits_one_preload_tag(): void {
		$fixture = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			)
		);
		$result  = $fixture['manager']->build_for_current_request();

		self::assertTrue( $result->is_ready() );
		self::assertCount( 1, $result->links() );
		self::assertStringContainsString( 'hero-desktop.jpg.hwlio.avif', $result->links()[0]->to_array()['href'] );
		self::assertNull( $result->links()[0]->to_array()['media'] );

		ob_start();
		$fixture['manager']->emit_preload_tags();
		$output = (string) ob_get_clean();

		self::assertSame( 1, substr_count( $output, '<link rel="preload" as="image"' ) );
		self::assertStringContainsString( 'type="image/avif"', $output );
	}

	/**
	 * Test responsive explicit variants emit media-scoped preload tags only.
	 *
	 * @return void
	 */
	public function test_responsive_explicit_variants_emit_media_scoped_preload_tags(): void {
		$store             = new FakeElementorHeroBackgroundPostMetaStore();
		$store->values[77] = new ElementorHeroBackgroundTargetSelection( 'responsive-container', 'background' );
		$fixture           = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			),
			'background-classic-responsive.php',
			ElementorBackgroundBreakpointMap::from_max_widths( 767, 1024 ),
			$store,
			array(
				902 => '2026/07/responsive-desktop.jpg',
				903 => '2026/07/responsive-tablet.jpg',
				904 => '2026/07/responsive-mobile.jpg',
			)
		);
		$result            = $fixture['manager']->build_for_current_request();

		self::assertTrue( $result->is_ready() );
		self::assertCount( 3, $result->links() );
		self::assertSame( '(min-width: 1025px)', $result->links()[0]->to_array()['media'] );
		self::assertSame( '(min-width: 768px) and (max-width: 1024px)', $result->links()[1]->to_array()['media'] );
		self::assertSame( '(max-width: 767px)', $result->links()[2]->to_array()['media'] );
	}

	/**
	 * Test missing breakpoint maps suppress responsive background preloads.
	 *
	 * @return void
	 */
	public function test_missing_breakpoint_maps_suppress_responsive_preload(): void {
		$store             = new FakeElementorHeroBackgroundPostMetaStore();
		$store->values[77] = new ElementorHeroBackgroundTargetSelection( 'responsive-container', 'background' );
		$fixture           = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			),
			'background-classic-responsive.php',
			null,
			$store,
			array(
				902 => '2026/07/responsive-desktop.jpg',
				903 => '2026/07/responsive-tablet.jpg',
				904 => '2026/07/responsive-mobile.jpg',
			)
		);

		self::assertSame( ElementorBackgroundPreloadResult::CODE_BREAKPOINT_MAP_MISSING, $fixture['manager']->build_for_current_request()->code() );
	}

	/**
	 * Test repeated emits are deduplicated per request.
	 *
	 * @return void
	 */
	public function test_repeated_emits_are_deduplicated_per_request(): void {
		$fixture = $this->manager(
			new FakeSettingsRepository(
				array(
					'critical_background_preload_enabled' => true,
					'delivery_enabled'                    => true,
				)
			)
		);

		ob_start();
		$fixture['manager']->emit_preload_tags();
		$fixture['manager']->emit_preload_tags();
		$output = (string) ob_get_clean();

		self::assertSame( 1, substr_count( $output, '<link rel="preload" as="image"' ) );
		self::assertSame( ElementorBackgroundPreloadResult::CODE_ALREADY_EMITTED, $fixture['manager']->build_for_current_request()->code() );
	}

	/**
	 * Build manager fixture.
	 *
	 * @param FakeSettingsRepository|null                   $settings Settings repository.
	 * @param string                                        $fixture Fixture filename.
	 * @param ElementorBackgroundBreakpointMap|null         $breakpoint_map Breakpoint map.
	 * @param FakeElementorHeroBackgroundPostMetaStore|null $selection_store Selection store.
	 * @param array<int,string>|null                        $manifests Attachment manifests.
	 * @return array<string,mixed>
	 */
	private function manager(
		?FakeSettingsRepository $settings = null,
		string $fixture = 'background-classic-desktop.php',
		?ElementorBackgroundBreakpointMap $breakpoint_map = null,
		?FakeElementorHeroBackgroundPostMetaStore $selection_store = null,
		?array $manifests = null
	): array {
		$settings                = $settings ?? new FakeSettingsRepository();
		$runtime                 = new FakeElementorBackgroundStylesheetRuntime();
		$runtime->document_id    = 77;
		$runtime->breakpoint_map = $breakpoint_map;

		$elementor_runtime = new FakeElementorRuntime();

		if ( null === $selection_store ) {
			$selection_store             = new FakeElementorHeroBackgroundPostMetaStore();
			$selection_store->values[77] = new ElementorHeroBackgroundTargetSelection( 'hero-section', 'background' );
		}

		$meta_store = new FakeAttachmentMetaStore();
		$manifests  = $manifests ?? array( 901 => '2026/07/hero-desktop.jpg' );

		foreach ( $manifests as $attachment_id => $relative_source ) {
			$this->seed_manifest( $meta_store, (int) $attachment_id, $relative_source );
		}

		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = 'C:/site/wp-content/uploads';

		$document_store           = new FakeElementorDocumentDataStore();
		$document_store->document = ElementorDocumentData::valid( $this->fixture_elements( $fixture ) );

		$builder = new ElementorBackgroundDeliveryPlanBuilder(
			new ElementorBackgroundDiscovery( $document_store ),
			new DerivativeRepository( $meta_store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			new DerivativeUrlResolver( $uploads, new DerivativeManifestSanitizer() ),
			new FakeSettingsRepository( array( 'format_preference' => array( 'avif', 'webp' ) ) ),
			new DerivativeManifestSanitizer(),
			static function (): array {
				return array(
					'basedir' => 'C:/site/wp-content/uploads',
					'baseurl' => 'https://example.test/wp-content/uploads',
					'error'   => '',
				);
			}
		);

		return array(
			'manager' => new ElementorCriticalBackgroundPreloadManager(
				$settings,
				$elementor_runtime,
				$runtime,
				$selection_store,
				$builder,
				new ResponsivePreloadRegistry()
			),
			'runtime' => $runtime,
		);
	}

	/**
	 * Seed one ready manifest entry.
	 *
	 * @param FakeAttachmentMetaStore $store Meta store.
	 * @param int                     $attachment_id Attachment ID.
	 * @param string                  $relative_source Relative source file.
	 * @return void
	 */
	private function seed_manifest( FakeAttachmentMetaStore $store, int $attachment_id, string $relative_source ): void {
		$store->meta[ $attachment_id ][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 1,
			'fingerprint'    => null,
			'updated_at'     => 1783987200,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => $relative_source,
						'mime'   => 'image/jpeg',
						'width'  => 1600,
						'height' => 900,
						'bytes'  => 250000,
					),
					'formats' => array(
						'webp' => array(
							'status' => 'ready',
							'file'   => $relative_source . '.hwlio.webp',
							'mime'   => 'image/webp',
						),
						'avif' => array(
							'status' => 'ready',
							'file'   => $relative_source . '.hwlio.avif',
							'mime'   => 'image/avif',
						),
					),
				),
			),
		);
	}

	/**
	 * Load one structured background fixture.
	 *
	 * @param string $file Fixture filename.
	 * @return array<int,array<string,mixed>>
	 */
	private function fixture_elements( string $file ): array {
		$elements = require dirname( __DIR__, 2 ) . '/Fixtures/Elementor/BackgroundDiscovery/' . $file;

		self::assertIsArray( $elements );

		return $elements;
	}
}
