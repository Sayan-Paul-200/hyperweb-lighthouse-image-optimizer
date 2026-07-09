<?php
/**
 * Tests for the minimal Composer autoload proof.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit;

use HyperWeb\LighthouseImageOptimizer\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that Composer can autoload the namespaced plugin class.
 */
final class PluginTest extends TestCase {

	/**
	 * Test that the Plugin class autoloads and returns the expected slug.
	 *
	 * @return void
	 */
	public function test_plugin_class_autoloads(): void {
		$plugin = new Plugin();

		self::assertSame( 'hyperweb-lighthouse-image-optimizer', $plugin->slug() );
	}
}
