<?php
/**
 * Tests for the responsive preload manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\LateDiscoveredCriticalImageLocator;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadResult;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies opt-in responsive preload behavior for late-discovered critical images.
 */
final class ResponsivePreloadManagerTest extends TestCase {

	private const ATTACHMENT_ID = 123;
	private const POST_ID       = 55;
	private const UPLOADS       = 'C:/site/wp-content/uploads';

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test hook registration adds only wp_head.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_wp_head(): void {
		$hooks = new HookRegistrar();

		$this->manager()->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'wp_head', $hooks->actions()[0]['hook'] );
		self::assertSame( 1, $hooks->actions()[0]['priority'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
		self::assertSame( array(), $hooks->filters() );
	}

	/**
	 * Test disabled preload emits nothing.
	 *
	 * @return void
	 */
	public function test_disabled_preload_emits_nothing(): void {
		$result = $this->manager()->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_DISABLED, $result->code() );
		self::assertFalse( $result->is_ready() );
	}

	/**
	 * Test non-frontend requests emit nothing.
	 *
	 * @return void
	 */
	public function test_non_frontend_requests_emit_nothing(): void {
		$runtime                              = $this->runtime();
		$runtime->request_context['is_admin'] = true;

		$result = $this->manager( $runtime, new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) )->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_INELIGIBLE_REQUEST, $result->code() );
	}

	/**
	 * Test missing preload selection emits nothing.
	 *
	 * @return void
	 */
	public function test_missing_preload_selection_emits_nothing(): void {
		$result = $this->manager( $this->runtime(), new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ), new FakeCriticalImagePostMetaStore() )
			->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_NO_PRELOAD_SELECTION, $result->code() );
	}

	/**
	 * Test ambiguous matches emit nothing.
	 *
	 * @return void
	 */
	public function test_ambiguous_matches_emit_nothing(): void {
		$runtime               = $this->runtime();
		$runtime->post_content = '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" sizes="100vw"><img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg" sizes="100vw">';

		$result = $this->manager( $runtime, new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) )->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_NO_UNIQUE_MATCH, $result->code() );
	}

	/**
	 * Test missing sizes emit nothing.
	 *
	 * @return void
	 */
	public function test_missing_sizes_emit_nothing(): void {
		$runtime               = $this->runtime();
		$runtime->post_content = '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w">';

		$result = $this->manager( $runtime, new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) )->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_MISSING_SIZES, $result->code() );
	}

	/**
	 * Test unmatched fallback src emits nothing.
	 *
	 * @return void
	 */
	public function test_unmatched_fallback_src_emits_nothing(): void {
		$runtime               = $this->runtime();
		$runtime->post_content = '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/missing.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w" sizes="100vw">';

		$result = $this->manager( $runtime, new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) )->build_for_current_request();

		self::assertSame( ResponsivePreloadResult::CODE_NO_MATCHING_SOURCE, $result->code() );
	}

	/**
	 * Test one unique late-discovered primary image emits a responsive preload tag.
	 *
	 * @return void
	 */
	public function test_unique_primary_content_image_emits_a_responsive_preload_tag(): void {
		$manager = $this->manager( $this->runtime(), new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) );
		$result  = $manager->build_for_current_request();

		self::assertTrue( $result->is_ready() );
		self::assertSame( ResponsivePreloadResult::CODE_READY, $result->code() );
		self::assertNotNull( $result->link() );
		self::assertSame( 'avif', $result->link()->format() );
		self::assertSame( 'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif', $result->link()->href() );
		self::assertSame( '100vw', $result->link()->imagesizes() );
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg.hwlio.avif 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif 2400w',
			$result->link()->imagesrcset()
		);

		ob_start();
		$manager->emit_preload_tag();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<link rel="preload" as="image"', $output );
		self::assertStringContainsString( 'href="https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif"', $output );
		self::assertStringContainsString( 'type="image/avif"', $output );
		self::assertStringContainsString( 'imagesizes="100vw"', $output );
	}

	/**
	 * Test repeated emits are deduplicated per request.
	 *
	 * @return void
	 */
	public function test_repeated_emits_are_deduplicated_per_request(): void {
		$manager = $this->manager( $this->runtime(), new FakeSettingsRepository( array( 'responsive_preload_enabled' => true ) ) );

		ob_start();
		$manager->emit_preload_tag();
		$manager->emit_preload_tag();
		$output = (string) ob_get_clean();

		self::assertSame( 1, substr_count( $output, '<link rel="preload" as="image"' ) );
		self::assertSame( ResponsivePreloadResult::CODE_ALREADY_EMITTED, $manager->build_for_current_request()->code() );
	}

	/**
	 * Build manager fixture.
	 *
	 * @param FakeAttachmentImageRuntime|null     $runtime Runtime seam.
	 * @param FakeSettingsRepository|null         $settings Settings repository.
	 * @param FakeCriticalImagePostMetaStore|null $store Critical-image store.
	 * @return ResponsivePreloadManager
	 */
	private function manager(
		?FakeAttachmentImageRuntime $runtime = null,
		?FakeSettingsRepository $settings = null,
		?FakeCriticalImagePostMetaStore $store = null
	): ResponsivePreloadManager {
		$runtime  = $runtime ?? $this->runtime();
		$settings = $settings ?? new FakeSettingsRepository();
		if ( null === $store ) {
			$store                          = new FakeCriticalImagePostMetaStore();
			$store->values[ self::POST_ID ] = self::ATTACHMENT_ID;
		}
		$analyzer          = new WordPressImageMarkupAnalyzer();
		$sanitizer         = new DerivativeManifestSanitizer();
		$resolver          = new AttachmentSizeResolver( $sanitizer );
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = self::UPLOADS;
		$meta_store        = new FakeAttachmentMetaStore();
		$meta_store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		return new ResponsivePreloadManager(
			$settings,
			$runtime,
			new CriticalImageRegistry( $runtime, $settings, $store ),
			new LateDiscoveredCriticalImageLocator( $runtime, $analyzer ),
			new IntrinsicDimensionRepair( $resolver, $analyzer ),
			new AttachmentImageSourceExtractor( $analyzer ),
			new SourceSetBuilder(
				new DerivativeRepository(
					$meta_store,
					$sanitizer,
					new FixedAttachmentClock( 1783526500 )
				),
				new DerivativeUrlResolver( $uploads, $sanitizer ),
				$uploads,
				$this->probe_with_derivatives(),
				$sanitizer,
				$resolver
			),
			$analyzer,
			new ResponsivePreloadRegistry()
		);
	}

	/**
	 * Build runtime seam.
	 *
	 * @return FakeAttachmentImageRuntime
	 */
	private function runtime(): FakeAttachmentImageRuntime {
		$runtime               = new FakeAttachmentImageRuntime();
		$runtime->metadata     = $this->image_meta();
		$runtime->post_id      = self::POST_ID;
		$runtime->post_type    = 'post';
		$runtime->post_content = '<p>Intro</p><img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w" sizes="100vw" width="2400" height="1600" alt="Hero">';

		return $runtime;
	}

	/**
	 * Build a probe with derivative files.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives(): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 300, 100, 'image/webp', 2400, 1600 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif', 220, 100, 'image/avif', 2400, 1600 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.webp', 25, 100, 'image/webp', 150, 100 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.avif', 20, 100, 'image/avif', 150, 100 );

		return $probe;
	}

	/**
	 * Build image metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function image_meta(): array {
		return array(
			'file'   => '2026/07/hero.jpg',
			'width'  => 2400,
			'height' => 1600,
			'sizes'  => array(
				'thumbnail' => array(
					'file'   => 'hero-150x100.jpg',
					'width'  => 150,
					'height' => 100,
				),
			),
		);
	}

	/**
	 * Build stored derivative manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function stored_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/hero.jpg',
				'file_size'     => 1000,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'a', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full'      => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 300,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/hero.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 220,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'thumbnail' => array(
					'source'  => array(
						'file'   => '2026/07/hero-150x100.jpg',
						'width'  => 150,
						'height' => 100,
						'mime'   => 'image/jpeg',
						'bytes'  => 125,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero-150x100.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 25,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/hero-150x100.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 20,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			),
		);
	}
}
