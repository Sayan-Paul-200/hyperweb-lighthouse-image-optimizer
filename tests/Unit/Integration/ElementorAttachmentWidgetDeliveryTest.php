<?php
/**
 * Tests for Elementor attachment-widget delivery behavior.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\LoadingAttributeManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\MarkupEligibility;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderer;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Delivery\TransformedMarkupRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies end-to-end delivery behavior for safe Elementor attachment widgets.
 */
final class ElementorAttachmentWidgetDeliveryTest extends TestCase {

	/**
	 * Uploads base directory.
	 *
	 * @var string
	 */
	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Clean up globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test supported Elementor widgets render picture markup and preserve fallback image attributes.
	 *
	 * @param string $fixture Fixture file.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 *
	 * @dataProvider supported_widget_fixture_provider
	 */
	public function test_supported_elementor_widgets_render_picture_markup_and_preserve_fallback_image_attributes( string $fixture, int $attachment_id ): void {
		$this->activate_provider_filters();

		$html   = $this->fixture_html( $fixture );
		$result = $this->manager()->filter_attachment_image( $html, $attachment_id, 'full', false, array() );

		self::assertStringStartsWith( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( $html, $result );
		self::assertStringContainsString( '<source type="image/avif"', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( 'loading="lazy"', $result );
		self::assertStringNotContainsString( 'loading="eager"', $result );
		self::assertStringNotContainsString( 'fetchpriority="high"', $result );
		self::assertStringNotContainsString( self::UPLOADS, $result );
	}

	/**
	 * Test excluded gallery and carousel fixtures remain unchanged.
	 *
	 * @param string $fixture Fixture file.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 *
	 * @dataProvider excluded_widget_fixture_provider
	 */
	public function test_excluded_gallery_and_carousel_fixtures_remain_unchanged( string $fixture, int $attachment_id ): void {
		$this->activate_provider_filters();

		$html = $this->fixture_html( $fixture );

		self::assertSame( $html, $this->manager()->filter_attachment_image( $html, $attachment_id, 'full', false, array() ) );
	}

	/**
	 * Test editor and preview mode requests remain unchanged.
	 *
	 * @return void
	 */
	public function test_editor_and_preview_mode_requests_remain_unchanged(): void {
		$html                          = $this->fixture_html( 'image-widget-attachment.html' );
		$editor_runtime                = new FakeElementorRuntime();
		$editor_runtime->editor_mode   = true;
		$preview_runtime               = new FakeElementorRuntime();
		$preview_runtime->preview_mode = true;

		$this->activate_provider_filters( $editor_runtime );
		self::assertSame( $html, $this->manager()->filter_attachment_image( $html, 321, 'full', false, array() ) );

		$this->activate_provider_filters( $preview_runtime );
		self::assertSame( $html, $this->manager()->filter_attachment_image( $html, 321, 'full', false, array() ) );
	}

	/**
	 * Provide supported widget fixtures.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function supported_widget_fixture_provider(): array {
		return array(
			'image widget'     => array( 'image-widget-attachment.html', 321 ),
			'image box widget' => array( 'image-box-widget-attachment.html', 322 ),
			'cta widget'       => array( 'cta-widget-attachment.html', 323 ),
		);
	}

	/**
	 * Provide excluded widget fixtures.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function excluded_widget_fixture_provider(): array {
		return array(
			'gallery widget'  => array( 'gallery-widget-attachment.html', 324 ),
			'carousel widget' => array( 'carousel-widget-attachment.html', 325 ),
		);
	}

	/**
	 * Activate provider callbacks through the test filter shim.
	 *
	 * @param FakeElementorRuntime|null $runtime Runtime seam.
	 * @return void
	 */
	private function activate_provider_filters( ?FakeElementorRuntime $runtime = null ): void {
		$runtime  = $runtime ?? new FakeElementorRuntime();
		$provider = new ElementorIntegration(
			new ElementorWidgetMatcher( $runtime, new WordPressImageMarkupAnalyzer() )
		);

		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_markup_is_eligible' => array( $provider, 'filter_markup_eligibility' ),
		);
	}

	/**
	 * Build the delivery manager fixture.
	 *
	 * @return DeliveryManager
	 */
	private function manager(): DeliveryManager {
		$settings                 = new FakeSettingsRepository( array( 'delivery_enabled' => true ) );
		$runtime                  = new FakeAttachmentImageRuntime();
		$runtime->requested_width = null;
		$runtime->metadata_map    = array(
			321 => $this->attachment_meta( 'elementor-image.jpg', 1200, 800 ),
			322 => $this->attachment_meta( 'elementor-image-box-300x200.jpg', 300, 200 ),
			323 => $this->attachment_meta( 'elementor-cta-640x360.jpg', 640, 360 ),
			324 => $this->attachment_meta( 'elementor-gallery-300x300.jpg', 300, 300 ),
			325 => $this->attachment_meta( 'elementor-carousel-640x360.jpg', 640, 360 ),
		);
		$uploads                  = new FakeUploadsUrlRuntime();
		$uploads->base_url        = 'https://example.test/wp-content/uploads';
		$uploads->base_dir        = self::UPLOADS;
		$store                    = new FakeAttachmentMetaStore();

		foreach ( array( 321, 322, 323, 324, 325 ) as $attachment_id ) {
			$store->meta[ $attachment_id ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest_for( $attachment_id );
		}

		$analyzer  = new WordPressImageMarkupAnalyzer();
		$sanitizer = new DerivativeManifestSanitizer();
		$resolver  = new AttachmentSizeResolver( $sanitizer );

		return new DeliveryManager(
			$settings,
			$runtime,
			new MarkupEligibility( $settings, $runtime, $analyzer ),
			new AttachmentImageSourceExtractor( $analyzer ),
			new SourceSetBuilder(
				new DerivativeRepository(
					$store,
					$sanitizer,
					new FixedAttachmentClock( 1783526500 )
				),
				new DerivativeUrlResolver( $uploads, $sanitizer ),
				$uploads,
				$this->probe_with_derivatives(),
				$sanitizer,
				$resolver
			),
			new PictureRenderer( $analyzer ),
			new TransformedMarkupRegistry(),
			new IntrinsicDimensionRepair( $resolver, $analyzer ),
			new LoadingAttributeManager(
				$settings,
				new CriticalImageRegistry( $runtime, $settings, new FakeCriticalImagePostMetaStore() ),
				$runtime,
				$analyzer
			)
		);
	}

	/**
	 * Load one fixture HTML fragment.
	 *
	 * @param string $file Fixture file.
	 * @return string
	 */
	private function fixture_html( string $file ): string {
		$path = dirname( __DIR__, 2 ) . '/Fixtures/Elementor/' . $file;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading committed fixture files during tests.
		$html = file_get_contents( $path );

		self::assertIsString( $html );

		return $html;
	}

	/**
	 * Build simple attachment metadata.
	 *
	 * @param string $file Relative file.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @return array<string,mixed>
	 */
	private function attachment_meta( string $file, int $width, int $height ): array {
		return array(
			'file'   => '2026/07/' . $file,
			'width'  => $width,
			'height' => $height,
			'sizes'  => array(),
		);
	}

	/**
	 * Build a fake derivative probe.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives(): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );

		$probe->add_file( self::UPLOADS . '/2026/07/elementor-image.jpg.hwlio.webp', 180, 100, 'image/webp', 1200, 800 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-image.jpg.hwlio.avif', 140, 100, 'image/avif', 1200, 800 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-image-box-300x200.jpg.hwlio.webp', 35, 100, 'image/webp', 300, 200 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-image-box-300x200.jpg.hwlio.avif', 28, 100, 'image/avif', 300, 200 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-cta-640x360.jpg.hwlio.webp', 70, 100, 'image/webp', 640, 360 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-cta-640x360.jpg.hwlio.avif', 58, 100, 'image/avif', 640, 360 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-gallery-300x300.jpg.hwlio.webp', 44, 100, 'image/webp', 300, 300 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-gallery-300x300.jpg.hwlio.avif', 33, 100, 'image/avif', 300, 300 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-carousel-640x360.jpg.hwlio.webp', 68, 100, 'image/webp', 640, 360 );
		$probe->add_file( self::UPLOADS . '/2026/07/elementor-carousel-640x360.jpg.hwlio.avif', 55, 100, 'image/avif', 640, 360 );

		return $probe;
	}

	/**
	 * Build a stored derivative manifest for one attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	private function stored_manifest_for( int $attachment_id ): array {
		$files = array(
			321 => array( 'elementor-image.jpg', 1200, 800, 1000 ),
			322 => array( 'elementor-image-box-300x200.jpg', 300, 200, 300 ),
			323 => array( 'elementor-cta-640x360.jpg', 640, 360, 500 ),
			324 => array( 'elementor-gallery-300x300.jpg', 300, 300, 320 ),
			325 => array( 'elementor-carousel-640x360.jpg', 640, 360, 450 ),
		);

		list( $file, $width, $height, $bytes ) = $files[ $attachment_id ];

		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/' . $file,
				'file_size'     => $bytes,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( dechex( $attachment_id % 16 ), 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/' . $file,
						'width'  => $width,
						'height' => $height,
						'mime'   => 'image/jpeg',
						'bytes'  => $bytes,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/' . $file . '.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => max( 20, $bytes / 5 ),
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/' . $file . '.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => max( 18, $bytes / 6 ),
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
