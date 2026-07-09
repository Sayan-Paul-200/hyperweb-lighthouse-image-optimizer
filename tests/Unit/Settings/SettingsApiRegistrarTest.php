<?php
/**
 * Tests for Settings API registration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSanitizer;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Settings API integration behavior.
 */
final class SettingsApiRegistrarTest extends TestCase {

	/**
	 * Test hook registration.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_admin_init_action(): void {
		$registrar = $this->registrar();
		$hooks     = new HookRegistrar();

		$registrar->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'admin_init', $hooks->actions()[0]['hook'] );
		self::assertSame( SettingsApiRegistrar::PRIORITY, $hooks->actions()[0]['priority'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test Settings API registration calls.
	 *
	 * @return void
	 */
	public function test_register_settings_registers_option_and_sections(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar( $settings_api );

		$registrar->register_settings();

		self::assertCount( 1, $settings_api->settings );
		self::assertSame( SettingsApiRegistrar::OPTION_GROUP, $settings_api->settings[0]['group'] );
		self::assertSame( SettingsRepository::OPTION_NAME, $settings_api->settings[0]['name'] );
		self::assertSame( 'array', $settings_api->settings[0]['args']['type'] );
		self::assertSame( SettingsSchema::defaults(), $settings_api->settings[0]['args']['default'] );
		self::assertFalse( $settings_api->settings[0]['args']['show_in_rest'] );
		self::assertIsCallable( $settings_api->settings[0]['args']['sanitize_callback'] );

		self::assertCount( 6, $settings_api->sections );
		self::assertSame(
			array(
				SettingsSchema::GROUP_GENERAL,
				SettingsSchema::GROUP_FORMATS,
				SettingsSchema::GROUP_PROCESS,
				SettingsSchema::GROUP_DELIVERY,
				SettingsSchema::GROUP_LOGGING,
				SettingsSchema::GROUP_ADVANCED,
			),
			array_column( $settings_api->sections, 'id' )
		);
		self::assertSame(
			array_fill( 0, 6, SettingsApiRegistrar::PAGE_SLUG ),
			array_column( $settings_api->sections, 'page' )
		);
	}

	/**
	 * Test sanitize callback normalizes valid administrator input.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_normalizes_payload_and_records_feedback(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar(
			$settings_api,
			new FakeFormatSupportProvider(
				array(
					SettingsSchema::FORMAT_WEBP => FormatSupportResult::supported( 'webp', 'image/webp' ),
					SettingsSchema::FORMAT_AVIF => FormatSupportResult::supported( 'avif', 'image/avif' ),
				)
			)
		);

		$settings = $registrar->sanitize_settings(
			array(
				'automatic_optimization' => 'yes',
				'webp_quality'           => 900,
				'enabled_formats'        => array( 'gif', 'avif', 'webp', 'avif' ),
				'unknown_key'            => 'ignored',
			)
		);

		self::assertTrue( $settings['automatic_optimization'] );
		self::assertSame( 100, $settings['webp_quality'] );
		self::assertSame( array( 'avif', 'webp' ), $settings['enabled_formats'] );
		self::assertArrayNotHasKey( 'unknown_key', $settings );
		self::assertContains( SettingsSchema::CAPABILITY_MANAGE_OPTIONS, $settings_api->capability_checks );
		self::assertSame(
			array( 'unknown_settings_dropped', 'settings_normalized' ),
			array_column( $settings_api->errors, 'code' )
		);
	}

	/**
	 * Test unauthorized saves preserve existing settings.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_rejects_unauthorized_save(): void {
		$settings_api      = new FakeSettingsApi();
		$settings_api->can = false;
		$registrar         = $this->registrar( $settings_api );

		$settings = $registrar->sanitize_settings(
			array(
				'delivery_enabled' => true,
			)
		);

		self::assertFalse( $settings['delivery_enabled'] );
		self::assertSame( array( 'unauthorized_settings_save' ), array_column( $settings_api->errors, 'code' ) );
	}

	/**
	 * Test malformed payloads preserve existing settings and report an error.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_handles_malformed_payload(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar( $settings_api );

		$settings = $registrar->sanitize_settings( 'not-an-array' );

		self::assertSame( SettingsSchema::defaults(), $settings );
		self::assertSame(
			array( 'invalid_settings_payload' ),
			array_column( $settings_api->errors, 'code' )
		);
	}

	/**
	 * Test unsupported formats are removed when another selected format is supported.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_removes_unsupported_enabled_formats(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar(
			$settings_api,
			new FakeFormatSupportProvider(
				array(
					SettingsSchema::FORMAT_WEBP => FormatSupportResult::supported( 'webp', 'image/webp' ),
					SettingsSchema::FORMAT_AVIF => FormatSupportResult::unsupported(
						'avif',
						'image/avif',
						true,
						false,
						'encoding_not_supported'
					),
				)
			)
		);

		$settings = $registrar->sanitize_settings(
			array(
				'enabled_formats' => array( 'avif', 'webp' ),
			)
		);

		self::assertSame( array( 'webp' ), $settings['enabled_formats'] );
		self::assertContains( 'unsupported_enabled_formats', array_column( $settings_api->errors, 'code' ) );
	}

	/**
	 * Test misconfigured formats are also blocked.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_removes_misconfigured_enabled_formats(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar(
			$settings_api,
			new FakeFormatSupportProvider(
				array(
					SettingsSchema::FORMAT_WEBP => FormatSupportResult::misconfigured(
						'webp',
						'image/webp',
						true,
						false,
						'no_image_editor_available'
					),
					SettingsSchema::FORMAT_AVIF => FormatSupportResult::supported( 'avif', 'image/avif' ),
				)
			)
		);

		$settings = $registrar->sanitize_settings(
			array(
				'enabled_formats' => array( 'webp', 'avif' ),
			)
		);

		self::assertSame( array( 'avif' ), $settings['enabled_formats'] );
		self::assertContains( 'unsupported_enabled_formats', array_column( $settings_api->errors, 'code' ) );
	}

	/**
	 * Test unknown support preserves existing 2.2 behavior and does not block.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_allows_unknown_enabled_format_support(): void {
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar(
			$settings_api,
			new FakeFormatSupportProvider(
				array(
					SettingsSchema::FORMAT_AVIF => FormatSupportResult::unknown(
						'avif',
						'image/avif',
						null,
						null,
						'support_check_unavailable'
					),
				)
			)
		);

		$settings = $registrar->sanitize_settings(
			array(
				'enabled_formats' => array( 'avif' ),
			)
		);

		self::assertSame( array( 'avif' ), $settings['enabled_formats'] );
		self::assertNotContains( 'unsupported_enabled_formats', array_column( $settings_api->errors, 'code' ) );
	}

	/**
	 * Test unsupported-only submissions preserve the previous enabled formats.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_preserves_current_formats_when_all_submitted_formats_are_unsupported(): void {
		$current = array_replace(
			SettingsSchema::defaults(),
			array(
				'enabled_formats' => array( 'avif' ),
			)
		);

		$options      = new FakeOptionStore( array( SettingsRepository::OPTION_NAME => $current ) );
		$settings_api = new FakeSettingsApi();
		$registrar    = $this->registrar(
			$settings_api,
			new FakeFormatSupportProvider(
				array(
					SettingsSchema::FORMAT_WEBP => FormatSupportResult::unsupported(
						'webp',
						'image/webp',
						true,
						false,
						'encoding_not_supported'
					),
					SettingsSchema::FORMAT_AVIF => FormatSupportResult::supported( 'avif', 'image/avif' ),
				)
			),
			$options
		);

		$settings = $registrar->sanitize_settings(
			array(
				'enabled_formats' => array( 'webp' ),
			)
		);

		self::assertSame( array( 'avif' ), $settings['enabled_formats'] );
		self::assertSame(
			array( 'unsupported_enabled_formats', 'enabled_formats_preserved' ),
			array_column( $settings_api->errors, 'code' )
		);
	}

	/**
	 * Build the registrar under test.
	 *
	 * @param FakeSettingsApi|null           $settings_api Settings API fake.
	 * @param FakeFormatSupportProvider|null $format_support Format support fake.
	 * @param FakeOptionStore|null           $options Option store.
	 * @return SettingsApiRegistrar
	 */
	private function registrar(
		?FakeSettingsApi $settings_api = null,
		?FakeFormatSupportProvider $format_support = null,
		?FakeOptionStore $options = null
	): SettingsApiRegistrar {
		$options        = $options ?? new FakeOptionStore( array( SettingsRepository::OPTION_NAME => SettingsSchema::defaults() ) );
		$settings_api   = $settings_api ?? new FakeSettingsApi();
		$format_support = $format_support ?? new FakeFormatSupportProvider();

		return new SettingsApiRegistrar(
			SettingsRepository::for_options( $options ),
			new SettingsSanitizer( SettingsSchema::definitions(), SettingsSchema::base_defaults() ),
			$settings_api,
			$format_support
		);
	}
}
