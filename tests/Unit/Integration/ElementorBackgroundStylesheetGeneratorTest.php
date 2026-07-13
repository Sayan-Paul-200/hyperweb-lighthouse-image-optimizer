<?php
/**
 * Tests for the Elementor background companion stylesheet generator.
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
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetGenerator;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetResult;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Elementor background CSS generation stays conservative and deterministic.
 */
final class ElementorBackgroundStylesheetGeneratorTest extends TestCase {

	/**
	 * Test a desktop-only classic background produces one safe override rule.
	 *
	 * @return void
	 */
	public function test_desktop_only_classic_background_produces_one_safe_override_rule(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 901, '2026/07/hero-desktop.jpg' );

		$result = $this->generator( $store, 'background-classic-desktop.php' )->generate( 501 );

		self::assertTrue( $result->is_ready() );
		self::assertTrue( $result->has_rules() );
		self::assertSame( 1, $result->rule_count() );
		self::assertSame( ElementorBackgroundStylesheetResult::CODE_RULES_GENERATED, $result->code() );
		self::assertStringContainsString( '.elementor-element.elementor-element-hero-section {', $result->css() );
		self::assertStringContainsString( 'image-set(', $result->css() );
		self::assertStringContainsString( 'hero-desktop.jpg.hwlio.avif', $result->css() );
		self::assertStringContainsString( 'hero-desktop.jpg.hwlio.webp', $result->css() );
		self::assertStringContainsString( 'hero-desktop.jpg', $result->css() );
		self::assertSame( 64, strlen( $result->signature() ) );
	}

	/**
	 * Test overlay backgrounds use the canonical overlay selector.
	 *
	 * @return void
	 */
	public function test_overlay_background_uses_the_overlay_selector(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 905, '2026/07/overlay-image.jpg' );

		$result = $this->generator( $store, 'background-overlay-classic.php' )->generate( 503 );

		self::assertTrue( $result->is_ready() );
		self::assertStringContainsString(
			'.elementor-element.elementor-element-overlay-column > .elementor-background-overlay {',
			$result->css()
		);
	}

	/**
	 * Test explicit desktop/tablet/mobile mappings emit media-query-scoped rules only.
	 *
	 * @return void
	 */
	public function test_explicit_responsive_backgrounds_emit_mutually_exclusive_media_queries(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 902, '2026/07/responsive-desktop.jpg' );
		$this->seed_manifest( $store, 903, '2026/07/responsive-tablet.jpg' );
		$this->seed_manifest( $store, 904, '2026/07/responsive-mobile.jpg' );

		$result = $this->generator( $store, 'background-classic-responsive.php' )->generate(
			502,
			ElementorBackgroundBreakpointMap::from_max_widths( 767, 1024 )
		);

		self::assertTrue( $result->is_ready() );
		self::assertSame( 3, $result->rule_count() );
		self::assertStringContainsString( '@media (min-width: 1025px)', $result->css() );
		self::assertStringContainsString( '@media (min-width: 768px) and (max-width: 1024px)', $result->css() );
		self::assertStringContainsString( '@media (max-width: 767px)', $result->css() );

		$before_first_media = explode( '@media', $result->css(), 2 )[0];

		self::assertStringNotContainsString(
			'.elementor-element.elementor-element-responsive-container {',
			$before_first_media
		);
	}

	/**
	 * Test unsupported URL-only values do not produce any safe rules.
	 *
	 * @return void
	 */
	public function test_unsupported_url_only_values_emit_no_rules(): void {
		$result = $this->generator( new FakeAttachmentMetaStore(), 'background-url-only.php' )->generate( 504 );

		self::assertTrue( $result->is_noop() );
		self::assertSame( ElementorBackgroundStylesheetResult::CODE_NO_SUPPORTED_SOURCES, $result->code() );
	}

	/**
	 * Test missing ready derivatives emit no override rules.
	 *
	 * @return void
	 */
	public function test_missing_derivatives_emit_no_safe_rules(): void {
		$result = $this->generator( new FakeAttachmentMetaStore(), 'background-classic-desktop.php' )->generate( 501 );

		self::assertTrue( $result->is_noop() );
		self::assertSame( ElementorBackgroundStylesheetResult::CODE_NO_SAFE_RULES, $result->code() );
	}

	/**
	 * Test stylesheet signatures remain deterministic for unchanged inputs.
	 *
	 * @return void
	 */
	public function test_stylesheet_signature_is_deterministic(): void {
		$store = new FakeAttachmentMetaStore();
		$this->seed_manifest( $store, 901, '2026/07/hero-desktop.jpg' );

		$generator = $this->generator( $store, 'background-classic-desktop.php' );
		$first     = $generator->generate( 501 );
		$second    = $generator->generate( 501 );

		self::assertSame( $first->signature(), $second->signature() );
		self::assertSame( $first->css(), $second->css() );
	}

	/**
	 * Build one generator fixture.
	 *
	 * @param FakeAttachmentMetaStore $store Meta store.
	 * @param string                  $fixture Fixture filename.
	 * @return ElementorBackgroundStylesheetGenerator
	 */
	private function generator( FakeAttachmentMetaStore $store, string $fixture ): ElementorBackgroundStylesheetGenerator {
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = 'C:/site/wp-content/uploads';

		$document_store           = new FakeElementorDocumentDataStore();
		$document_store->document = ElementorDocumentData::valid( $this->fixture_elements( $fixture ) );

		return new ElementorBackgroundStylesheetGenerator(
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
	 * Seed one ready manifest entry for a background attachment.
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
