<?php
/**
 * Tests for settings sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSanitizer;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies schema-driven settings normalization.
 */
final class SettingsSanitizerTest extends TestCase {

	/**
	 * Test boolean and format list normalization.
	 *
	 * @return void
	 */
	public function test_booleans_and_format_lists_are_sanitized(): void {
		$result   = $this->sanitizer()->sanitize(
			array(
				'automatic_optimization'      => 'yes',
				'delivery_enabled'            => '0',
				'delivery_emergency_disabled' => 'yes',
				'enabled_formats'             => array( 'webp', 'gif', 'webp', 'avif' ),
				'format_preference'           => array( 'gif', 'avif', 'webp', 'avif' ),
			)
		);
		$settings = $result->settings();

		self::assertTrue( $settings['automatic_optimization'] );
		self::assertFalse( $settings['delivery_enabled'] );
		self::assertTrue( $settings['delivery_emergency_disabled'] );
		self::assertSame( array( 'webp', 'avif' ), $settings['enabled_formats'] );
		self::assertSame( array( 'avif', 'webp' ), $settings['format_preference'] );
		self::assertTrue( $result->has_code( SettingsResult::CODE_SANITIZED ) );
	}

	/**
	 * Test numeric values are clamped.
	 *
	 * @return void
	 */
	public function test_numeric_values_are_clamped(): void {
		$settings = $this->sanitizer()->sanitize(
			array(
				'webp_quality'            => 900,
				'avif_quality'            => -20,
				'minimum_savings_percent' => -1,
				'max_retries'             => 99,
				'worker_time_budget'      => 0,
				'queue_concurrency'       => 99,
				'log_retention_days'      => 9000,
			)
		)->settings();

		self::assertSame( 100, $settings['webp_quality'] );
		self::assertSame( 1, $settings['avif_quality'] );
		self::assertSame( 0, $settings['minimum_savings_percent'] );
		self::assertSame( 10, $settings['max_retries'] );
		self::assertSame( 1, $settings['worker_time_budget'] );
		self::assertSame( 5, $settings['queue_concurrency'] );
		self::assertSame( 3650, $settings['log_retention_days'] );
	}

	/**
	 * Test unknown keys are dropped and missing keys receive defaults.
	 *
	 * @return void
	 */
	public function test_unknown_keys_are_dropped_and_missing_keys_receive_defaults(): void {
		$result   = $this->sanitizer()->sanitize(
			array(
				'unknown'      => 'value',
				'webp_quality' => 75,
			)
		);
		$settings = $result->settings();

		self::assertArrayNotHasKey( 'unknown', $settings );
		self::assertSame( 75, $settings['webp_quality'] );
		self::assertSame( 60, $settings['avif_quality'] );
		self::assertSame( SettingsSchema::SCHEMA_VERSION, $settings['schema_version'] );
		self::assertTrue( $result->has_code( SettingsResult::CODE_UNKNOWN_KEYS_DROPPED ) );
	}

	/**
	 * Build the sanitizer under test.
	 *
	 * @return SettingsSanitizer
	 */
	private function sanitizer(): SettingsSanitizer {
		return new SettingsSanitizer( SettingsSchema::definitions(), SettingsSchema::base_defaults() );
	}
}
