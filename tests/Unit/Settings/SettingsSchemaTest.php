<?php
/**
 * Tests for settings defaults.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies initial settings defaults.
 */
final class SettingsSchemaTest extends TestCase {

	/**
	 * Test default settings shape.
	 *
	 * @return void
	 */
	public function test_defaults_include_expected_initial_values(): void {
		$defaults = SettingsSchema::defaults();

		self::assertSame( 1, $defaults['schema_version'] );
		self::assertFalse( $defaults['setup_completed'] );
		self::assertFalse( $defaults['automatic_optimization'] );
		self::assertFalse( $defaults['delivery_enabled'] );
		self::assertSame( array( 'webp' ), $defaults['enabled_formats'] );
		self::assertSame( array( 'avif', 'webp' ), $defaults['format_preference'] );
		self::assertFalse( $defaults['delete_data_on_uninstall'] );
		self::assertFalse( $defaults['delete_derivatives_on_uninstall'] );
	}
}
