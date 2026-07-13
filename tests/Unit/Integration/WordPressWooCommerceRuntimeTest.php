<?php
/**
 * Tests for the WordPress-backed WooCommerce runtime seam.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Integration\WordPressWooCommerceRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies WooCommerce request/product helpers normalize safely.
 */
final class WordPressWooCommerceRuntimeTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_is_product'],
			$GLOBALS['hwlio_test_queried_object_id'],
			$GLOBALS['hwlio_test_post_thumbnail_ids'],
			$GLOBALS['hwlio_test_attachment_image_urls'],
			$GLOBALS['hwlio_test_post_meta']
		);
	}

	/**
	 * Test gallery IDs normalize to unique positive integers only.
	 *
	 * @return void
	 */
	public function test_current_product_gallery_image_ids_normalize_safely(): void {
		$runtime = new WordPressWooCommerceRuntime();

		self::assertSame( array(), $runtime->current_product_gallery_image_ids() );

		$GLOBALS['hwlio_test_is_product']        = true;
		$GLOBALS['hwlio_test_queried_object_id'] = 77;
		$GLOBALS['hwlio_test_post_meta']         = array(
			77 => array(
				'_product_image_gallery' => '902, 0, 903,foo,902,904',
			),
		);

		self::assertSame( array( 902, 903, 904 ), $runtime->current_product_gallery_image_ids() );
	}
}
