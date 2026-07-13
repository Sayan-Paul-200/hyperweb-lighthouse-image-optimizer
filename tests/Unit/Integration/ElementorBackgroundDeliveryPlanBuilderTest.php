<?php
/**
 * Tests for the Elementor background delivery plan builder.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundBreakpointMap;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDeliveryPlanBuilder;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies shared Elementor background delivery planning stays conservative and deterministic.
 */
final class ElementorBackgroundDeliveryPlanBuilderTest extends TestCase {

	/**
	 * Test a desktop-only classic target resolves one safe preloadable variant.
	 *
	 * @return void
	 */
	public function test_desktop_only_target_resolves_one_safe_variant(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 901, '2026/07/hero-desktop.jpg' );

		$result = $this->builder( $store, 'background-classic-desktop.php' )->build( 501 );
		$plan   = $result->plan( 'hero-section|background' );

		self::assertTrue( $result->has_supported_sources() );
		self::assertFalse( $result->breakpoint_map_missing() );
		self::assertNotNull( $plan );
		self::assertFalse( $plan->is_responsive() );
		self::assertFalse( $plan->breakpoint_map_missing() );
		self::assertNotNull( $plan->variant( 'desktop' ) );
		self::assertNull( $plan->variant( 'desktop' )->media_query() );
		self::assertSame( 'avif', $plan->variant( 'desktop' )->preferred_candidate()['format'] );
	}

	/**
	 * Test responsive targets resolve mutually exclusive media-query-scoped variants.
	 *
	 * @return void
	 */
	public function test_responsive_target_resolves_explicit_device_variants(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 902, '2026/07/responsive-desktop.jpg' );
		$this->seed_manifest( $store, 903, '2026/07/responsive-tablet.jpg' );
		$this->seed_manifest( $store, 904, '2026/07/responsive-mobile.jpg' );

		$result = $this->builder( $store, 'background-classic-responsive.php' )->build(
			502,
			ElementorBackgroundBreakpointMap::from_max_widths( 767, 1024 )
		);
		$plan   = $result->plan( 'responsive-container|background' );

		self::assertNotNull( $plan );
		self::assertTrue( $plan->is_responsive() );
		self::assertSame( '(min-width: 1025px)', $plan->variant( 'desktop' )->media_query() );
		self::assertSame( '(min-width: 768px) and (max-width: 1024px)', $plan->variant( 'tablet' )->media_query() );
		self::assertSame( '(max-width: 767px)', $plan->variant( 'mobile' )->media_query() );
	}

	/**
	 * Test unsupported URL-only values produce no supported target plans.
	 *
	 * @return void
	 */
	public function test_unsupported_url_only_values_produce_no_supported_target_plans(): void {
		$result = $this->builder( new FakeAttachmentMetaStore(), 'background-url-only.php' )->build( 504 );

		self::assertFalse( $result->has_supported_sources() );
		self::assertFalse( $result->has_plans() );
	}

	/**
	 * Test incomplete breakpoint maps suppress responsive preloadability.
	 *
	 * @return void
	 */
	public function test_incomplete_breakpoint_maps_flag_responsive_targets_as_missing(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 902, '2026/07/responsive-desktop.jpg' );
		$this->seed_manifest( $store, 903, '2026/07/responsive-tablet.jpg' );
		$this->seed_manifest( $store, 904, '2026/07/responsive-mobile.jpg' );

		$result = $this->builder( $store, 'background-classic-responsive.php' )->build( 502 );
		$plan   = $result->plan( 'responsive-container|background' );

		self::assertTrue( $result->has_supported_sources() );
		self::assertTrue( $result->breakpoint_map_missing() );
		self::assertNotNull( $plan );
		self::assertTrue( $plan->breakpoint_map_missing() );
		self::assertFalse( $plan->has_variants() );
	}

	/**
	 * Test missing derivatives suppress safe variants while keeping supported-source awareness.
	 *
	 * @return void
	 */
	public function test_missing_derivatives_suppress_safe_variants(): void {
		$result = $this->builder( new FakeAttachmentMetaStore(), 'background-classic-desktop.php' )->build( 501 );
		$plan   = $result->plan( 'hero-section|background' );

		self::assertTrue( $result->has_supported_sources() );
		self::assertNotNull( $plan );
		self::assertFalse( $plan->has_variants() );
	}

	/**
	 * Build one builder fixture.
	 *
	 * @param FakeAttachmentMetaStore $store Meta store.
	 * @param string                  $fixture Fixture filename.
	 * @return ElementorBackgroundDeliveryPlanBuilder
	 */
	private function builder( FakeAttachmentMetaStore $store, string $fixture ): ElementorBackgroundDeliveryPlanBuilder {
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = 'C:/site/wp-content/uploads';

		$document_store           = new FakeElementorDocumentDataStore();
		$document_store->document = ElementorDocumentData::valid( $this->fixture_elements( $fixture ) );

		return new ElementorBackgroundDeliveryPlanBuilder(
			new ElementorBackgroundDiscovery( $document_store ),
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			new DerivativeUrlResolver( $uploads, new DerivativeManifestSanitizer() ),
			new FakeSettingsRepository(
				array(
					'format_preference' => array( 'avif', 'webp' ),
				)
			),
			new DerivativeManifestSanitizer(),
			static function (): array {
				return array(
					'basedir' => 'C:/site/wp-content/uploads',
					'baseurl' => 'https://example.test/wp-content/uploads',
					'error'   => '',
				);
			}
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
