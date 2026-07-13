<?php
/**
 * Tests for the late-discovered critical image locator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\LateDiscoveredCriticalImageLocator;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies unique content-image matching for responsive preload.
 */
final class LateDiscoveredCriticalImageLocatorTest extends TestCase {

	/**
	 * Test one unique fragment matched by class is returned.
	 *
	 * @return void
	 */
	public function test_unique_fragment_matched_by_class_is_returned(): void {
		$runtime               = new FakeAttachmentImageRuntime();
		$runtime->post_content = '<p>Hello</p><img class="wp-image-123 size-full" src="https://example.test/uploads/hero.jpg" sizes="100vw">';

		$match = $this->locator( $runtime )->locate( 123 );

		self::assertNotNull( $match );
		self::assertSame( 123, $match->attachment_id() );
		self::assertStringContainsString( 'wp-image-123', $match->html() );
	}

	/**
	 * Test one unique fragment matched by data-id is returned.
	 *
	 * @return void
	 */
	public function test_unique_fragment_matched_by_data_id_is_returned(): void {
		$runtime               = new FakeAttachmentImageRuntime();
		$runtime->post_content = '<img data-id="123" src="https://example.test/uploads/hero.jpg" sizes="100vw">';

		$match = $this->locator( $runtime )->locate( 123 );

		self::assertNotNull( $match );
		self::assertSame( 123, $match->attachment_id() );
		self::assertStringContainsString( 'data-id="123"', $match->html() );
	}

	/**
	 * Test ambiguous multiple matches fail open.
	 *
	 * @return void
	 */
	public function test_ambiguous_multiple_matches_fail_open(): void {
		$runtime               = new FakeAttachmentImageRuntime();
		$runtime->post_content = '<img class="wp-image-123" src="https://example.test/uploads/hero-a.jpg"><img class="wp-image-123" src="https://example.test/uploads/hero-b.jpg">';

		self::assertNull( $this->locator( $runtime )->locate( 123 ) );
	}

	/**
	 * Test missing content fails open.
	 *
	 * @return void
	 */
	public function test_missing_content_fails_open(): void {
		self::assertNull( $this->locator( new FakeAttachmentImageRuntime() )->locate( 123 ) );
	}

	/**
	 * Build locator fixture.
	 *
	 * @param FakeAttachmentImageRuntime $runtime Runtime seam.
	 * @return LateDiscoveredCriticalImageLocator
	 */
	private function locator( FakeAttachmentImageRuntime $runtime ): LateDiscoveredCriticalImageLocator {
		return new LateDiscoveredCriticalImageLocator( $runtime, new WordPressImageMarkupAnalyzer() );
	}
}
