<?php
/**
 * Tests for the WordPress multisite site-context runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Multisite;

require_once __DIR__ . '/MultisiteTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\WordPressSiteContextRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative current-site and network-activation behavior.
 */
final class WordPressSiteContextRuntimeTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_current_blog_id'],
			$GLOBALS['hwlio_test_blog_stack'],
			$GLOBALS['hwlio_test_is_multisite'],
			$GLOBALS['hwlio_test_network_active_plugins'],
			$GLOBALS['hwlio_test_switched_sites'],
			$GLOBALS['hwlio_test_restore_count']
		);
	}

	/**
	 * Test runtime reads current site, network-active state, and switch/restore calls.
	 *
	 * @return void
	 */
	public function test_runtime_reads_and_mutates_site_context_conservatively(): void {
		$GLOBALS['hwlio_test_current_blog_id']      = 7;
		$GLOBALS['hwlio_test_is_multisite']         = true;
		$GLOBALS['hwlio_test_network_active_plugins'] = array( 'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php' );

		$runtime = new WordPressSiteContextRuntime();

		self::assertSame( 7, $runtime->current_site_id() );
		self::assertTrue( $runtime->is_multisite() );
		self::assertTrue( $runtime->plugin_network_active( 'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php' ) );

		$runtime->switch_to_site( 11 );
		self::assertSame( 11, $runtime->current_site_id() );
		$runtime->restore_site();

		self::assertSame( 7, $runtime->current_site_id() );
		self::assertSame( array( 11 ), $GLOBALS['hwlio_test_switched_sites'] );
		self::assertSame( 1, $GLOBALS['hwlio_test_restore_count'] );
	}
}
