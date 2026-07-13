<?php
/**
 * Tests for WooCommerce primary-image integration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommerceIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommercePrimaryImageMatcher;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies WooCommerce primary-image registration and filtering behavior.
 */
final class WooCommerceIntegrationTest extends TestCase {

	/**
	 * Primary product attachment ID.
	 *
	 * @var int
	 */
	private const PRIMARY_ATTACHMENT_ID = 501;

	/**
	 * Clean up globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test hook registration adds only the internal Woo filters.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_internal_woo_filters(): void {
		$hooks = new HookRegistrar();

		$this->provider()->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 3, $hooks->filters() );
		self::assertSame( 'hwlio_critical_image_candidates', $hooks->filters()[0]['hook'] );
		self::assertSame( 'hwlio_loading_image_role', $hooks->filters()[1]['hook'] );
		self::assertSame( 'hwlio_markup_is_eligible', $hooks->filters()[2]['hook'] );
	}

	/**
	 * Test single-product requests register the featured image as the primary critical image.
	 *
	 * @return void
	 */
	public function test_single_product_request_registers_featured_image_as_primary_critical_image(): void {
		$runtime                   = $this->runtime();
		$runtime->single_product   = true;
		$runtime->product_id       = 77;
		$runtime->primary_image_id = self::PRIMARY_ATTACHMENT_ID;

		$result = $this->provider( $runtime )->filter_critical_image_candidates(
			array(
				'primary_attachment_id'   => null,
				'critical_attachment_ids' => array( 999 ),
				'critical_urls'           => array(),
				'preload_attachment_id'   => 999,
			),
			array()
		);

		self::assertSame( self::PRIMARY_ATTACHMENT_ID, $result['primary_attachment_id'] );
		self::assertSame( array( 999, self::PRIMARY_ATTACHMENT_ID ), $result['critical_attachment_ids'] );
		self::assertNull( $result['preload_attachment_id'] );
	}

	/**
	 * Test non-product or missing-image requests do not register Woo critical images.
	 *
	 * @return void
	 */
	public function test_non_product_or_missing_image_requests_do_not_register_woo_critical_images(): void {
		$baseline = array(
			'primary_attachment_id'   => null,
			'critical_attachment_ids' => array(),
			'critical_urls'           => array(),
			'preload_attachment_id'   => null,
		);

		self::assertSame( $baseline, $this->provider()->filter_critical_image_candidates( $baseline, array() ) );

		$runtime                 = $this->runtime();
		$runtime->single_product = true;

		self::assertSame( $baseline, $this->provider( $runtime )->filter_critical_image_candidates( $baseline, array() ) );
	}

	/**
	 * Test confirmed primary fragments stay primary while known non-primary Woo fragments are demoted.
	 *
	 * @return void
	 */
	public function test_loading_role_refinement_keeps_primary_and_demotes_known_non_primary_fragments(): void {
		$runtime                    = $this->runtime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( 902 );
		$provider                   = $this->provider( $runtime );

		$primary   = $provider->filter_loading_image_role(
			'primary',
			array(
				'attachment_id' => self::PRIMARY_ATTACHMENT_ID,
				'src'           => 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg',
				'attr'          => array(
					'class' => 'wp-post-image attachment-woocommerce_single size-woocommerce_single',
				),
			)
		);
		$gallery   = $provider->filter_loading_image_role(
			'secondary',
			array(
				'attachment_id' => 902,
				'src'           => 'https://example.test/wp-content/uploads/2026/07/product-gallery-150x150.jpg',
				'attr'          => array(
					'class' => 'wp-post-image attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
				),
				'html'          => $this->fixture_image( 'single-product-gallery-secondary.html' ),
			)
		);
		$loop      = $provider->filter_loading_image_role(
			'secondary',
			array(
				'attachment_id' => 903,
				'src'           => 'https://example.test/wp-content/uploads/2026/07/loop-product-300x300.jpg',
				'attr'          => array(
					'class' => 'wp-post-image attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
				),
				'html'          => $this->fixture_image( 'product-loop-thumbnail.html' ),
			)
		);
		$variation = $provider->filter_loading_image_role(
			'secondary',
			array(
				'attachment_id' => 904,
				'src'           => 'https://example.test/wp-content/uploads/2026/07/product-variation-600x600.jpg',
				'attr'          => array(
					'class' => 'wp-post-image attachment-woocommerce_single size-woocommerce_single',
				),
				'html'          => $this->fixture_image( 'single-product-variation-image.html' ),
			)
		);
		$duplicate = $provider->filter_loading_image_role(
			'primary',
			array(
				'attachment_id' => self::PRIMARY_ATTACHMENT_ID,
				'src'           => 'https://example.test/wp-content/uploads/2026/07/product-main.jpg',
				'attr'          => array(
					'class' => 'wp-image-501 alignnone size-full',
				),
				'html'          => '<img class="wp-image-501 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/product-main.jpg" alt="Duplicate">',
			)
		);

		self::assertSame( 'primary', $primary );
		self::assertSame( 'none', $gallery );
		self::assertSame( 'none', $loop );
		self::assertSame( 'none', $variation );
		self::assertSame( 'none', $duplicate );
	}

	/**
	 * Test eligibility allows only the confirmed primary product image and leaves uncertain non-Woo content alone.
	 *
	 * @return void
	 */
	public function test_markup_eligibility_allows_only_confirmed_primary_product_images(): void {
		$runtime                    = $this->runtime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = self::PRIMARY_ATTACHMENT_ID;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( 902 );
		$provider                   = $this->provider( $runtime );

		self::assertTrue(
			$provider->filter_markup_eligibility(
				true,
				self::PRIMARY_ATTACHMENT_ID,
				$this->fixture_image( 'single-product-primary.html' ),
				array( 'hook' => 'wp_get_attachment_image' )
			)
		);
		self::assertFalse(
			$provider->filter_markup_eligibility(
				true,
				903,
				$this->fixture_image( 'product-loop-thumbnail.html' ),
				array( 'hook' => 'wp_get_attachment_image' )
			)
		);
		self::assertTrue(
			$provider->filter_markup_eligibility(
				true,
				902,
				$this->fixture_image( 'single-product-gallery-secondary.html' ),
				array( 'hook' => 'wp_get_attachment_image' )
			)
		);
		self::assertFalse(
			$provider->filter_markup_eligibility(
				true,
				904,
				$this->fixture_image( 'single-product-variation-image.html' ),
				array( 'hook' => 'wp_get_attachment_image' )
			)
		);
		self::assertFalse(
			$provider->filter_markup_eligibility(
				true,
				self::PRIMARY_ATTACHMENT_ID,
				'<img class="wp-image-501 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/product-main.jpg" alt="Duplicate">',
				array( 'hook' => 'wp_content_img_tag' )
			)
		);

		$runtime->single_product = false;

		self::assertTrue(
			$this->provider( $runtime )->filter_markup_eligibility(
				true,
				321,
				'<img class="wp-image-321 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Content image">',
				array( 'hook' => 'wp_content_img_tag' )
			)
		);
	}

	/**
	 * Build provider fixture.
	 *
	 * @param FakeWooCommerceRuntime|null $runtime Runtime seam.
	 * @return WooCommerceIntegration
	 */
	private function provider( ?FakeWooCommerceRuntime $runtime = null ): WooCommerceIntegration {
		$runtime = $runtime ?? $this->runtime();

		return new WooCommerceIntegration(
			$runtime,
			new WooCommercePrimaryImageMatcher( $runtime, new WordPressImageMarkupAnalyzer() )
		);
	}

	/**
	 * Build runtime fixture.
	 *
	 * @return FakeWooCommerceRuntime
	 */
	private function runtime(): FakeWooCommerceRuntime {
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
}
