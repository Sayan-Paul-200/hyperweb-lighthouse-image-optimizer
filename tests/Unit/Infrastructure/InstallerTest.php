<?php
/**
 * Tests for installer routines.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\InstallerResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies idempotent installation and upgrade behavior.
 */
final class InstallerTest extends TestCase {

	/**
	 * Test clean install initializes options and log table state.
	 *
	 * @return void
	 */
	public function test_clean_install_initializes_options(): void {
		$options   = new FakeOptionStore();
		$log_table = new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) );
		$installer = $this->installer( $options, $log_table );

		$result = $installer->install();

		self::assertTrue( $result->is_successful() );
		self::assertFalse( $result->has_warnings() );
		self::assertTrue( $result->has_code( InstallerResult::CODE_SETTINGS_INITIALIZED ) );
		self::assertTrue( $result->has_code( InstallerResult::CODE_LOG_TABLE_READY ) );
		self::assertSame( SettingsSchema::defaults(), $options->options[ Installer::OPTION_SETTINGS ] );
		self::assertSame( '0.1.0-alpha.3', $options->options[ Installer::OPTION_VERSION ] );
		self::assertSame( '1', $options->options[ Installer::OPTION_DB_VERSION ] );
		self::assertSame( 'yes', $options->autoload[ Installer::OPTION_SETTINGS ] );
		self::assertSame( 'no', $options->autoload[ Installer::OPTION_ACTIVATION_STATE ] );
		self::assertSame( 'ok', $options->options[ Installer::OPTION_ACTIVATION_STATE ]['status'] );
		self::assertFalse( $options->options[ Installer::OPTION_ACTIVATION_STATE ]['notice_pending'] );
		self::assertSame( array(), $options->options[ Installer::OPTION_ACTIVATION_STATE ]['notices'] );
		self::assertSame( 1, $log_table->install_calls );
	}

	/**
	 * Test rerunning installation is idempotent.
	 *
	 * @return void
	 */
	public function test_rerunning_install_does_not_change_existing_state(): void {
		$options   = new FakeOptionStore();
		$log_table = new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) );
		$installer = $this->installer( $options, $log_table );

		$installer->install();
		$first_options = $options->options;

		$installer->install();

		self::assertSame( $first_options, $options->options );
		self::assertSame( 2, $log_table->install_calls );
	}

	/**
	 * Test older stored versions and settings schema upgrade.
	 *
	 * @return void
	 */
	public function test_upgrade_preserves_existing_settings_and_fills_defaults(): void {
		$options = new FakeOptionStore(
			array(
				Installer::OPTION_SETTINGS         => array(
					'schema_version'         => 0,
					'automatic_optimization' => true,
				),
				Installer::OPTION_VERSION          => '0.1.0-alpha.2',
				Installer::OPTION_DB_VERSION       => '0',
				Installer::OPTION_ACTIVATION_STATE => array( 'schema_version' => 0 ),
			)
		);

		$installer = $this->installer(
			$options,
			new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) )
		);

		self::assertTrue( $installer->needs_upgrade() );

		$result = $installer->install();

		self::assertTrue( $result->has_code( InstallerResult::CODE_SETTINGS_MERGED ) );
		self::assertSame( 1, $options->options[ Installer::OPTION_SETTINGS ]['schema_version'] );
		self::assertTrue( $options->options[ Installer::OPTION_SETTINGS ]['automatic_optimization'] );
		self::assertFalse( $options->options[ Installer::OPTION_SETTINGS ]['delivery_enabled'] );
		self::assertSame( '0.1.0-alpha.3', $options->options[ Installer::OPTION_VERSION ] );
		self::assertSame( '1', $options->options[ Installer::OPTION_DB_VERSION ] );
		self::assertFalse( $installer->needs_upgrade() );
	}

	/**
	 * Test invalid settings are repaired with diagnostics.
	 *
	 * @return void
	 */
	public function test_invalid_settings_are_repaired_with_activation_warning(): void {
		$options = new FakeOptionStore(
			array(
				Installer::OPTION_SETTINGS => 'not-an-array',
			)
		);

		$installer = $this->installer(
			$options,
			new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) )
		);

		$result = $installer->install();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( InstallerResult::CODE_SETTINGS_REPAIRED ) );
		self::assertSame( SettingsSchema::defaults(), $options->options[ Installer::OPTION_SETTINGS ] );
		self::assertSame( 'warning', $options->options[ Installer::OPTION_ACTIVATION_STATE ]['status'] );
		self::assertTrue( $options->options[ Installer::OPTION_ACTIVATION_STATE ]['notice_pending'] );
		self::assertSame( InstallerResult::CODE_SETTINGS_REPAIRED, $options->options[ Installer::OPTION_ACTIVATION_STATE ]['notices'][0]['code'] );
	}

	/**
	 * Test log table failure records diagnostics without failing setup.
	 *
	 * @return void
	 */
	public function test_log_table_failure_is_recorded_without_failing_setup(): void {
		$options   = new FakeOptionStore();
		$log_table = new FakeLogTableInstaller(
			InstallerResult::failure(
				array( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ),
				array( 'The log table could not be verified after creation.' )
			)
		);
		$installer = $this->installer( $options, $log_table );

		$result = $installer->install();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ) );
		self::assertSame( 'warning', $options->options[ Installer::OPTION_ACTIVATION_STATE ]['status'] );
		self::assertSame( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE, $options->options[ Installer::OPTION_ACTIVATION_STATE ]['notices'][0]['code'] );
	}

	/**
	 * Build an installer for tests.
	 *
	 * @param FakeOptionStore       $options Option store.
	 * @param FakeLogTableInstaller $log_table Log table installer.
	 * @return Installer
	 */
	private function installer( FakeOptionStore $options, FakeLogTableInstaller $log_table ): Installer {
		return new Installer(
			$options,
			$log_table,
			'0.1.0-alpha.3',
			'1',
			1,
			static function (): string {
				return '2026-07-09 00:00:00';
			}
		);
	}
}
