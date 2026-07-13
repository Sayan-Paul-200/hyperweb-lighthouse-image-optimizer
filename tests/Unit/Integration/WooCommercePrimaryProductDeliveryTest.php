<?php
/**
 * Tests for WooCommerce primary product image delivery behavior.
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
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommerceIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommercePrimaryImageMatcher;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies 9.2 end-to-end delivery behavior for WooCommerce primary product images.
 */
final class WooCommercePrimaryProductDeliveryTest extends TestCase {

	/**
	 * Primary attachment ID.
	 *
	 * @var int
	 */
	private const PRIMARY_ATTACHMENT_ID = 501;

	/**
	 * Gallery attachment ID.
	 *
	 * @var int
	 */
	private const GALLERY_ATTACHMENT_ID = 902;

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
	 * Test the primary product image renders picture markup and preserves Woo data attributes.
	 *
	 * @return void
	 */
	public function test_primary_product_image_renders_picture_markup_and_preserves_woo_data_attributes(): void {
		$runtime                    = $this->woo_runtime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( self::GALLERY_ATTACHMENT_ID );

		$this->activate_provider_filters( $runtime );

		$result = $this->manager()->filter_attachment_image(
			$this->fixture_image( 'single-product-primary.html' ),
			self::PRIMARY_ATTACHMENT_ID,
			'woocommerce_single',
			false,
			array()
		);

		self::assertStringStartsWith( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( '<source type="image/avif"', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( 'class="wp-post-image attachment-woocommerce_single size-woocommerce_single"', $result );
		self::assertStringContainsString( 'data-caption="Primary product image caption"', $result );
		self::assertStringContainsString( 'data-src="https://example.test/wp-content/uploads/2026/07/product-main.jpg"', $result );
		self::assertStringContainsString( 'data-large_image="https://example.test/wp-content/uploads/2026/07/product-main.jpg"', $result );
		self::assertStringContainsString( 'data-large_image_width="1200"', $result );
		self::assertStringContainsString( 'data-large_image_height="1200"', $result );
		self::assertStringNotContainsString( self::UPLOADS, $result );
	}

	/**
	 * Test confirmed gallery secondary images now render picture markup and preserve gallery data attributes.
	 *
	 * @return void
	 */
	public function test_confirmed_gallery_secondary_image_renders_picture_markup_and_preserves_gallery_data_attributes(): void {
		$runtime                    = $this->woo_runtime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( self::GALLERY_ATTACHMENT_ID );

		$this->activate_provider_filters( $runtime );

		$result = $this->manager()->filter_attachment_image(
			$this->fixture_image( 'single-product-gallery-secondary.html' ),
			self::GALLERY_ATTACHMENT_ID,
			'woocommerce_thumbnail',
			false,
			array()
		);

		self::assertStringStartsWith( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( '<source type="image/avif"', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( 'class="wp-post-image attachment-woocommerce_thumbnail size-woocommerce_thumbnail"', $result );
		self::assertStringContainsString( 'data-caption="Secondary gallery image caption"', $result );
		self::assertStringContainsString( 'data-src="https://example.test/wp-content/uploads/2026/07/product-gallery-2.jpg"', $result );
		self::assertStringContainsString( 'data-large_image="https://example.test/wp-content/uploads/2026/07/product-gallery-2.jpg"', $result );
		self::assertStringContainsString( 'data-large_image_width="1200"', $result );
		self::assertStringContainsString( 'data-large_image_height="1200"', $result );
		self::assertStringNotContainsString( self::UPLOADS, $result );
	}

	/**
	 * Test non-primary WooCommerce image contexts remain unchanged in 9.2.
	 *
	 * @param string $fixture Fixture file.
	 * @param bool   $single_product Whether to simulate a single-product request.
	 * @return void
	 *
	 * @dataProvider non_primary_fixture_provider
	 */
	public function test_non_primary_woo_image_contexts_remain_unchanged( string $fixture, bool $single_product ): void {
		$runtime                    = $this->woo_runtime();
		$runtime->single_product    = $single_product;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( self::GALLERY_ATTACHMENT_ID );

		$this->activate_provider_filters( $runtime );

		$html = $this->fixture_image( $fixture );

		self::assertSame(
			$html,
			$this->manager()->filter_attachment_image( $html, 903, 'woocommerce_thumbnail', false, array() )
		);
	}

	/**
	 * Test duplicate appearances of the primary attachment outside the confirmed primary fragment stay unchanged.
	 *
	 * @return void
	 */
	public function test_duplicate_primary_attachment_appearance_outside_the_confirmed_primary_fragment_stays_unchanged(): void {
		$runtime                    = $this->woo_runtime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( self::GALLERY_ATTACHMENT_ID );

		$this->activate_provider_filters( $runtime );

		$html = '<img class="wp-image-501 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/product-main.jpg" width="1200" height="1200" alt="Duplicate primary">';

		self::assertSame(
			$html,
			$this->manager()->filter_content_img_tag( $html, 'the_content', self::PRIMARY_ATTACHMENT_ID )
		);
	}

	/**
	 * Provide non-primary WooCommerce fixture cases.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function non_primary_fixture_provider(): array {
		return array(
			'cart thumbnail'     => array( 'cart-item-thumbnail.html', false ),
			'checkout thumbnail' => array( 'checkout-review-thumbnail.html', false ),
			'product loop'       => array( 'product-loop-thumbnail.html', false ),
			'related thumbnail'  => array( 'related-product-thumbnail.html', false ),
			'upsell thumbnail'   => array( 'upsell-product-thumbnail.html', false ),
			'variation image'    => array( 'single-product-variation-image.html', true ),
		);
	}

	/**
	 * Activate provider callbacks through the test filter shim.
	 *
	 * @param FakeWooCommerceRuntime $runtime Woo runtime.
	 * @return void
	 */
	private function activate_provider_filters( FakeWooCommerceRuntime $runtime ): void {
		$provider = new WooCommerceIntegration(
			$runtime,
			new WooCommercePrimaryImageMatcher( $runtime, new WordPressImageMarkupAnalyzer() )
		);

		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_critical_image_candidates' => array( $provider, 'filter_critical_image_candidates' ),
			'hwlio_loading_image_role'        => array( $provider, 'filter_loading_image_role' ),
			'hwlio_markup_is_eligible'        => array( $provider, 'filter_markup_eligibility' ),
		);
	}

	/**
	 * Build the delivery manager fixture.
	 *
	 * @return DeliveryManager
	 */
	private function manager(): DeliveryManager {
		$settings              = new FakeSettingsRepository( array( 'delivery_enabled' => true ) );
		$runtime               = new FakeAttachmentImageRuntime();
		$runtime->metadata_map = array(
			self::PRIMARY_ATTACHMENT_ID => $this->primary_image_meta(),
			self::GALLERY_ATTACHMENT_ID => $this->gallery_image_meta(),
			903                         => $this->thumbnail_image_meta( 'loop-product.jpg' ),
			904                         => $this->variation_image_meta(),
		);
		$uploads               = new FakeUploadsUrlRuntime();
		$uploads->base_url     = 'https://example.test/wp-content/uploads';
		$uploads->base_dir     = self::UPLOADS;
		$store                 = new FakeAttachmentMetaStore();
		$store->meta[ self::PRIMARY_ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();
		$store->meta[ self::GALLERY_ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->gallery_manifest();
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
				new CriticalImageRegistry( $runtime, $settings, new FakeCriticalImagePostMetaStore() ),
				$runtime,
				$analyzer
			)
		);
	}

	/**
	 * Build Woo runtime.
	 *
	 * @return FakeWooCommerceRuntime
	 */
	private function woo_runtime(): FakeWooCommerceRuntime {
		return new FakeWooCommerceRuntime();
	}

	/**
	 * Load one fixture image fragment from the Woo fixture pack.
	 *
	 * @param string $file Fixture file.
	 * @return string
	 */
	private function fixture_image( string $file ): string {
		$path = dirname( __DIR__, 2 ) . '/Fixtures/WooCommerce/' . $file;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading committed fixture files during tests.
		$html = file_get_contents( $path );

		self::assertIsString( $html );
		self::assertMatchesRegularExpression( '/<img\b[^>]*>/i', $html, $file );
		preg_match( '/<img\b[^>]*>/i', $html, $matches );

		return $matches[0];
	}

	/**
	 * Build the attachment metadata for the Woo primary image.
	 *
	 * @return array<string,mixed>
	 */
	private function primary_image_meta(): array {
		return array(
			'file'   => '2026/07/product-main.jpg',
			'width'  => 1200,
			'height' => 1200,
			'sizes'  => array(
				'woocommerce_single'    => array(
					'file'   => 'product-main-600x600.jpg',
					'width'  => 600,
					'height' => 600,
				),
				'woocommerce_thumbnail' => array(
					'file'   => 'product-main-300x300.jpg',
					'width'  => 300,
					'height' => 300,
				),
			),
		);
	}

	/**
	 * Build the attachment metadata for the Woo gallery image.
	 *
	 * @return array<string,mixed>
	 */
	private function gallery_image_meta(): array {
		return array(
			'file'   => '2026/07/product-gallery-2.jpg',
			'width'  => 1200,
			'height' => 1200,
			'sizes'  => array(
				'woocommerce_single'    => array(
					'file'   => 'product-gallery-2-600x600.jpg',
					'width'  => 600,
					'height' => 600,
				),
				'woocommerce_thumbnail' => array(
					'file'   => 'product-gallery-2-150x150.jpg',
					'width'  => 150,
					'height' => 150,
				),
			),
		);
	}

	/**
	 * Build simple thumbnail metadata for fail-open contexts.
	 *
	 * @param string $file Original file basename.
	 * @return array<string,mixed>
	 */
	private function thumbnail_image_meta( string $file ): array {
		return array(
			'file'   => '2026/07/' . $file,
			'width'  => 1200,
			'height' => 1200,
			'sizes'  => array(
				'woocommerce_thumbnail' => array(
					'file'   => str_replace( '.jpg', '-300x300.jpg', $file ),
					'width'  => 300,
					'height' => 300,
				),
			),
		);
	}

	/**
	 * Build variation image metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function variation_image_meta(): array {
		return array(
			'file'   => '2026/07/product-variation.jpg',
			'width'  => 1200,
			'height' => 1200,
			'sizes'  => array(
				'woocommerce_single' => array(
					'file'   => 'product-variation-600x600.jpg',
					'width'  => 600,
					'height' => 600,
				),
			),
		);
	}

	/**
	 * Build a probe containing current derivative files.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives(): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );

		$probe->add_file( self::UPLOADS . '/2026/07/product-main.jpg.hwlio.webp', 190, 100, 'image/webp', 1200, 1200 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-main.jpg.hwlio.avif', 150, 100, 'image/avif', 1200, 1200 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-main-600x600.jpg.hwlio.webp', 80, 100, 'image/webp', 600, 600 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-main-600x600.jpg.hwlio.avif', 70, 100, 'image/avif', 600, 600 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-main-300x300.jpg.hwlio.webp', 30, 100, 'image/webp', 300, 300 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-main-300x300.jpg.hwlio.avif', 24, 100, 'image/avif', 300, 300 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2.jpg.hwlio.webp', 200, 100, 'image/webp', 1200, 1200 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2.jpg.hwlio.avif', 170, 100, 'image/avif', 1200, 1200 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2-600x600.jpg.hwlio.webp', 90, 100, 'image/webp', 600, 600 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2-600x600.jpg.hwlio.avif', 75, 100, 'image/avif', 600, 600 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2-150x150.jpg.hwlio.webp', 20, 100, 'image/webp', 150, 150 );
		$probe->add_file( self::UPLOADS . '/2026/07/product-gallery-2-150x150.jpg.hwlio.avif', 18, 100, 'image/avif', 150, 150 );

		return $probe;
	}

	/**
	 * Build the stored derivative manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function stored_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/product-main.jpg',
				'file_size'     => 1000,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'b', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full'                  => array(
					'source'  => array(
						'file'   => '2026/07/product-main.jpg',
						'width'  => 1200,
						'height' => 1200,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-main.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 190,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-main.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 150,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'woocommerce_single'    => array(
					'source'  => array(
						'file'   => '2026/07/product-main-600x600.jpg',
						'width'  => 600,
						'height' => 600,
						'mime'   => 'image/jpeg',
						'bytes'  => 400,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-main-600x600.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 80,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-main-600x600.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 70,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'woocommerce_thumbnail' => array(
					'source'  => array(
						'file'   => '2026/07/product-main-300x300.jpg',
						'width'  => 300,
						'height' => 300,
						'mime'   => 'image/jpeg',
						'bytes'  => 200,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-main-300x300.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 30,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-main-300x300.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 24,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			),
		);
	}

	/**
	 * Build the stored derivative manifest for the Woo gallery image.
	 *
	 * @return array<string,mixed>
	 */
	private function gallery_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/product-gallery-2.jpg',
				'file_size'     => 900,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'c', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full'                  => array(
					'source'  => array(
						'file'   => '2026/07/product-gallery-2.jpg',
						'width'  => 1200,
						'height' => 1200,
						'mime'   => 'image/jpeg',
						'bytes'  => 900,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-gallery-2.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 200,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-gallery-2.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 170,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'woocommerce_single'    => array(
					'source'  => array(
						'file'   => '2026/07/product-gallery-2-600x600.jpg',
						'width'  => 600,
						'height' => 600,
						'mime'   => 'image/jpeg',
						'bytes'  => 360,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-gallery-2-600x600.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 90,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-gallery-2-600x600.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 75,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'woocommerce_thumbnail' => array(
					'source'  => array(
						'file'   => '2026/07/product-gallery-2-150x150.jpg',
						'width'  => 150,
						'height' => 150,
						'mime'   => 'image/jpeg',
						'bytes'  => 120,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/product-gallery-2-150x150.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 20,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/product-gallery-2-150x150.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 18,
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
