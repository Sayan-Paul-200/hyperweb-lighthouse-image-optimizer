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
	 * Test the settings page renders the visible critical-image and compatibility checkboxes with the registered option name.
	 *
	 * @return void
	 */
	public function test_render_outputs_the_visible_settings_checkboxes(): void {
		$page = new SettingsPage(
			new FakeSettingsRepository(
				array(
					'automatic_optimization'              => true,
					'delivery_enabled'                    => true,
					'loading_attribute_overrides_enabled' => true,
					'critical_logo_enabled'               => true,
					'responsive_preload_enabled'          => true,
					'critical_background_preload_enabled' => true,
					'elementor_background_delivery_enabled' => true,
				)
			)
		);

		ob_start();
		$page->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<form method="post" action="options.php"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[critical_logo_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[automatic_optimization]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[delivery_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[loading_attribute_overrides_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[responsive_preload_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[critical_background_preload_enabled]"', $output );
		self::assertStringContainsString( 'name="hwlio_settings[elementor_background_delivery_enabled]"', $output );
		self::assertStringContainsString( 'Treat the site custom logo as a critical image', $output );
		self::assertStringContainsString( 'Compatibility', $output );
		self::assertStringContainsString( 'Enable automatic optimization', $output );
		self::assertStringContainsString( 'Enable frontend modern-format delivery', $output );
		self::assertStringContainsString( 'Enable loading attribute overrides', $output );
		self::assertStringContainsString( 'Enable responsive image preload', $output );
		self::assertStringContainsString( 'Enable Elementor background delivery', $output );
		self::assertStringContainsString( 'Enable Elementor hero background preload', $output );
		self::assertStringContainsString( 'checked', $output );
	}
}
