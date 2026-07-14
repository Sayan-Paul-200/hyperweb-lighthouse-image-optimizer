<?php
/**
 * Tests for settings schema.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

require_once __DIR__ . '/SettingsTestFilterShim.php';

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies settings defaults and metadata.
 */
final class SettingsSchemaTest extends TestCase {

	/**
	 * Clear test filter state.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_default_settings_filter'] );
	}

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
		self::assertFalse( $defaults['pagespeed_insights_enabled'] );
		self::assertFalse( $defaults['delivery_enabled'] );
		self::assertTrue( $defaults['loading_attribute_overrides_enabled'] );
		self::assertFalse( $defaults['critical_logo_enabled'] );
		self::assertFalse( $defaults['responsive_preload_enabled'] );
		self::assertFalse( $defaults['critical_background_preload_enabled'] );
		self::assertTrue( $defaults['elementor_background_delivery_enabled'] );
		self::assertFalse( $defaults['delivery_emergency_disabled'] );
		self::assertSame( array( 'webp' ), $defaults['enabled_formats'] );
		self::assertSame( array( 'avif', 'webp' ), $defaults['format_preference'] );
		self::assertSame( 82, $defaults['webp_quality'] );
		self::assertSame( 60, $defaults['avif_quality'] );
		self::assertSame( 30, $defaults['log_retention_days'] );
		self::assertFalse( $defaults['delete_data_on_uninstall'] );
		self::assertFalse( $defaults['delete_derivatives_on_uninstall'] );
	}

	/**
	 * Test definitions include metadata for every default.
	 *
	 * @return void
	 */
	public function test_definitions_include_metadata_for_every_default(): void {
		$definitions = SettingsSchema::definitions();
		$defaults    = SettingsSchema::defaults();

		self::assertSame( array_keys( $defaults ), array_keys( $definitions ) );

		foreach ( $definitions as $definition ) {
			self::assertArrayHasKey( 'type', $definition );
			self::assertArrayHasKey( 'default', $definition );
			self::assertArrayHasKey( 'group', $definition );
			self::assertArrayHasKey( 'capability', $definition );
			self::assertArrayHasKey( 'description', $definition );
			self::assertArrayHasKey( 'sanitizer', $definition );
			self::assertArrayHasKey( 'validation', $definition );
			self::assertArrayHasKey( 'internal', $definition );
			self::assertSame( SettingsSchema::CAPABILITY_MANAGE_OPTIONS, $definition['capability'] );
		}

		self::assertTrue( $definitions['delivery_emergency_disabled']['internal'] );
		self::assertSame( SettingsSchema::GROUP_INTERNAL, $definitions['delivery_emergency_disabled']['group'] );
		self::assertFalse( $definitions['pagespeed_insights_enabled']['internal'] );
		self::assertSame( SettingsSchema::GROUP_GENERAL, $definitions['pagespeed_insights_enabled']['group'] );
	}

	/**
	 * Test filtered defaults are normalized.
	 *
	 * @return void
	 */
	public function test_filtered_defaults_are_sanitized(): void {
		$GLOBALS['hwlio_test_default_settings_filter'] = static function ( array $defaults ): array {
			$defaults['webp_quality']       = 900;
			$defaults['enabled_formats']    = array( 'gif', 'avif', 'avif' );
			$defaults['schema_version']     = 999;
			$defaults['unknown_future_key'] = true;

			return $defaults;
		};

		$defaults = SettingsSchema::defaults();

		self::assertSame( 100, $defaults['webp_quality'] );
		self::assertSame( array( 'avif' ), $defaults['enabled_formats'] );
		self::assertSame( SettingsSchema::SCHEMA_VERSION, $defaults['schema_version'] );
		self::assertArrayNotHasKey( 'unknown_future_key', $defaults );
	}
}
