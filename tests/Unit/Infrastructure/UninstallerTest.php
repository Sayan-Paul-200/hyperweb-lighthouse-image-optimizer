<?php
/**
 * Tests for uninstall policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\Uninstaller;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies uninstall cleanup is explicit opt-in only.
 */
final class UninstallerTest extends TestCase {

	/**
	 * Test default uninstall preserves data and derivatives.
	 *
	 * @return void
	 */
	public function test_default_uninstall_preserves_data_and_derivatives(): void {
		$options     = new FakeOptionStore( array( Installer::OPTION_SETTINGS => SettingsSchema::defaults() ) );
		$derivatives = new FakeDerivativeCleanup();
		$data        = new FakePluginDataCleaner();
		$uninstaller = new Uninstaller( $options, $derivatives, $data );

		$result = $uninstaller->uninstall();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DERIVATIVES_PRESERVED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_UNINSTALL_DATA_PRESERVED ) );
		self::assertSame( 0, $derivatives->calls );
		self::assertSame( 0, $data->calls );
		self::assertArrayHasKey( Installer::OPTION_SETTINGS, $options->options );
	}

	/**
	 * Test explicit cleanup settings delete plugin-owned data and derivatives.
	 *
	 * @return void
	 */
	public function test_explicit_cleanup_settings_run_destructive_cleanup(): void {
		$settings = array_replace(
			SettingsSchema::defaults(),
			array(
				'delete_data_on_uninstall'        => true,
				'delete_derivatives_on_uninstall' => true,
			)
		);

		$options     = new FakeOptionStore( array( Installer::OPTION_SETTINGS => $settings ) );
		$derivatives = new FakeDerivativeCleanup();
		$data        = new FakePluginDataCleaner();
		$uninstaller = new Uninstaller( $options, $derivatives, $data );

		$result = $uninstaller->uninstall();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DERIVATIVES_DELETED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_UNINSTALL_DATA_DELETED ) );
		self::assertSame( 1, $derivatives->calls );
		self::assertSame( 1, $data->calls );
	}

	/**
	 * Test invalid settings preserve all data.
	 *
	 * @return void
	 */
	public function test_invalid_settings_preserve_everything(): void {
		$options     = new FakeOptionStore( array( Installer::OPTION_SETTINGS => 'invalid' ) );
		$derivatives = new FakeDerivativeCleanup();
		$data        = new FakePluginDataCleaner();
		$uninstaller = new Uninstaller( $options, $derivatives, $data );

		$result = $uninstaller->uninstall();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_INVALID_SETTINGS_PRESERVED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DERIVATIVES_PRESERVED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_UNINSTALL_DATA_PRESERVED ) );
		self::assertSame( 0, $derivatives->calls );
		self::assertSame( 0, $data->calls );
	}
}
