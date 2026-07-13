<?php
/**
 * Tests for the WordPress attachment image runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressAttachmentImageRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies frontend request helpers used by the critical-image registry.
 */
final class WordPressAttachmentImageRuntimeTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_is_singular'],
			$GLOBALS['hwlio_test_queried_object_id'],
			$GLOBALS['hwlio_test_post_types'],
			$GLOBALS['hwlio_test_post_content'],
			$GLOBALS['hwlio_test_theme_supports_custom_logo'],
			$GLOBALS['hwlio_test_custom_logo_attachment_id']
		);
	}

	/**
	 * Test singular post facts are derived conservatively.
	 *
	 * @return void
	 */
	public function test_current_singular_post_facts_are_derived_conservatively(): void {
		$runtime = new WordPressAttachmentImageRuntime();

		self::assertSame( 0, $runtime->current_singular_post_id() );
		self::assertSame( '', $runtime->current_singular_post_type() );

		$GLOBALS['hwlio_test_is_singular']       = true;
		$GLOBALS['hwlio_test_queried_object_id'] = 45;
		$GLOBALS['hwlio_test_post_types']        = array( 45 => 'page' );

		self::assertSame( 45, $runtime->current_singular_post_id() );
		self::assertSame( 'page', $runtime->current_singular_post_type() );
	}

	/**
	 * Test current singular post content is read conservatively.
	 *
	 * @return void
	 */
	public function test_current_singular_post_content_is_read_conservatively(): void {
		$runtime = new WordPressAttachmentImageRuntime();

		self::assertSame( '', $runtime->current_singular_post_content() );

		$GLOBALS['hwlio_test_is_singular']       = true;
		$GLOBALS['hwlio_test_queried_object_id'] = 45;
		$GLOBALS['hwlio_test_post_content']      = array(
			45 => '<p>Hello</p><img class="wp-image-45" src="https://example.test/uploads/hero.jpg" alt="">',
		);

		self::assertStringContainsString( 'wp-image-45', $runtime->current_singular_post_content() );
	}

	/**
	 * Test custom-logo attachment resolution stays disabled unless the theme supports it.
	 *
	 * @return void
	 */
	public function test_custom_logo_attachment_resolution_requires_theme_support(): void {
		$runtime = new WordPressAttachmentImageRuntime();

		$GLOBALS['hwlio_test_custom_logo_attachment_id'] = 99;
		self::assertSame( 0, $runtime->custom_logo_attachment_id() );

		$GLOBALS['hwlio_test_theme_supports_custom_logo'] = true;
		self::assertSame( 99, $runtime->custom_logo_attachment_id() );
	}
}
