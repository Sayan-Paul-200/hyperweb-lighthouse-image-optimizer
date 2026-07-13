<?php
/**
 * Tests for the settings page.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\SettingsPage;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the currently visible settings-page controls.
 */
final class SettingsPageTest extends TestCase {

	/**
	 * Test the settings page renders the visible critical-image checkboxes with the registered option name.
	 *
	 * @return void
	 */
	public function test_render_outputs_the_critical_image_checkboxes(): void {
		$page = new SettingsPage(
			new FakeSettingsRepository(
				array(
					'critical_logo_enabled'               => true,
					'responsive_preload_enabled'          => true,
					'critical_background_preload_enabled' => true,
				)
			)
		);

		ob_start();
		$page->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<form method="post" action="options.php"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[critical_logo_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[responsive_preload_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[critical_background_preload_enabled]"', $output );
		self::assertStringContainsString( 'Treat the site custom logo as a critical image', $output );
		self::assertStringContainsString( 'Enable responsive preload for explicit late-discovered critical images', $output );
		self::assertStringContainsString( 'Enable responsive preload for one explicitly selected Elementor hero background', $output );
		self::assertStringContainsString( 'checked', $output );
	}
}
