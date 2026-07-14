<?php
/**
 * Tests for multisite-aware uploads resolution.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';
require_once dirname( __DIR__ ) . '/Integration/Multisite/MultisiteTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressUploadsRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies uploads facts follow the current site after a blog switch.
 */
final class WordPressUploadsRuntimeMultisiteTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_current_blog_id'],
			$GLOBALS['hwlio_test_blog_stack'],
			$GLOBALS['hwlio_test_wp_upload_dir'],
			$GLOBALS['hwlio_test_wp_upload_dir_by_site']
		);
	}

	/**
	 * Test uploads base URL and directory follow the current site after switching.
	 *
	 * @return void
	 */
	public function test_uploads_resolution_follows_the_current_site_after_switch(): void {
		$GLOBALS['hwlio_test_current_blog_id']   = 1;
		$GLOBALS['hwlio_test_wp_upload_dir_by_site'] = array(
			1 => array(
				'error'   => '',
				'baseurl' => 'https://site-one.test/wp-content/uploads',
				'basedir' => 'C:/site-one/wp-content/uploads',
			),
			2 => array(
				'error'   => '',
				'baseurl' => 'https://site-two.test/wp-content/uploads',
				'basedir' => 'C:/site-two/wp-content/uploads',
			),
		);

		$runtime = new WordPressUploadsRuntime();
		$request = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp' );

		self::assertSame( 'https://site-one.test/wp-content/uploads', $runtime->uploads_base_url( $request ) );
		self::assertSame( 'C:/site-one/wp-content/uploads', $runtime->uploads_base_dir() );

		\switch_to_blog( 2 );

		self::assertSame( 'https://site-two.test/wp-content/uploads', $runtime->uploads_base_url( $request ) );
		self::assertSame( 'C:/site-two/wp-content/uploads', $runtime->uploads_base_dir() );
	}
}
