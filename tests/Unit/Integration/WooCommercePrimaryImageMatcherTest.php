<?php
/**
 * Tests for WooCommerce image-fragment classification.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommercePrimaryImageMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verifies WooCommerce fragment classification stays conservative.
 */
final class WooCommercePrimaryImageMatcherTest extends TestCase {

	/**
	 * Test gallery, commerce-thumbnail, variation, and primary contexts classify predictably.
	 *
	 * @return void
	 */
	public function test_match_classifies_primary_gallery_commerce_and_variation_contexts(): void {
		$runtime                    = new FakeWooCommerceRuntime();
		$runtime->single_product    = true;
		$runtime->primary_image_id  = 501;
		$runtime->primary_image_url = 'https://example.test/wp-content/uploads/2026/07/product-main-600x600.jpg';
		$runtime->gallery_image_ids = array( 902 );
		$matcher                    = new WooCommercePrimaryImageMatcher( $runtime, new WordPressImageMarkupAnalyzer() );

		self::assertSame(
			WooCommercePrimaryImageMatcher::MATCH_PRIMARY,
			$matcher->match( 501, array( 'html' => $this->fixture_image( 'single-product-primary.html' ) ) )
		);
		self::assertSame(
			WooCommercePrimaryImageMatcher::MATCH_GALLERY_SECONDARY,
			$matcher->match( 902, array( 'html' => $this->fixture_image( 'single-product-gallery-secondary.html' ) ) )
		);
		self::assertSame(
			WooCommercePrimaryImageMatcher::MATCH_COMMERCE_THUMBNAIL,
			$matcher->match( 903, array( 'html' => $this->fixture_image( 'cart-item-thumbnail.html' ) ) )
		);
		self::assertSame(
			WooCommercePrimaryImageMatcher::MATCH_VARIATION_OR_UNCERTAIN,
			$matcher->match( 904, array( 'html' => $this->fixture_image( 'single-product-variation-image.html' ) ) )
		);
		self::assertSame(
			WooCommercePrimaryImageMatcher::MATCH_VARIATION_OR_UNCERTAIN,
			$matcher->match(
				501,
				array(
					'html' => '<img class="wp-image-501 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/product-main.jpg" alt="Duplicate primary">',
				)
			)
		);
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
