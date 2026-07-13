<?php
/**
 * Tests for settings repository.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies settings persistence and typed access.
 */
final class SettingsRepositoryTest extends TestCase {

	/**
	 * Test ensure initializes missing settings.
	 *
	 * @return void
	 */
	public function test_ensure_initializes_missing_settings(): void {
		$options    = new FakeOptionStore();
		$repository = SettingsRepository::for_options( $options );

		$result = $repository->ensure();

		self::assertTrue( $result->is_valid() );
		self::assertTrue( $result->has_code( SettingsResult::CODE_INITIALIZED ) );
		self::assertSame( SettingsSchema::defaults(), $options->options[ SettingsRepository::OPTION_NAME ] );
		self::assertSame( 'yes', $options->autoload[ SettingsRepository::OPTION_NAME ] );
	}

	/**
	 * Test ensure repairs invalid stored settings.
	 *
	 * @return void
	 */
	public function test_ensure_repairs_invalid_stored_settings(): void {
		$options    = new FakeOptionStore( array( SettingsRepository::OPTION_NAME => 'invalid' ) );
		$repository = SettingsRepository::for_options( $options );

		$result = $repository->ensure();

		self::assertFalse( $result->is_valid() );
		self::assertTrue( $result->has_code( SettingsResult::CODE_REPAIRED ) );
		self::assertSame( SettingsSchema::defaults(), $options->options[ SettingsRepository::OPTION_NAME ] );
	}

	/**
	 * Test read reports missing keys and sanitized values.
	 *
	 * @return void
	 */
	public function test_read_merges_missing_keys_without_persisting(): void {
		$options    = new FakeOptionStore(
			array(
				SettingsRepository::OPTION_NAME => array(
					'automatic_optimization' => true,
				),
			)
		);
		$repository = SettingsRepository::for_options( $options );

		$result = $repository->read();

		self::assertTrue( $result->is_valid() );
		self::assertTrue( $result->has_changes() );
		self::assertTrue( $result->settings()['automatic_optimization'] );
		self::assertSame( 82, $result->settings()['webp_quality'] );
		self::assertSame(
			array( 'automatic_optimization' => true ),
			$options->options[ SettingsRepository::OPTION_NAME ]
		);
	}

	/**
	 * Test save persists sanitized settings only.
	 *
	 * @return void
	 */
	public function test_save_persists_sanitized_values(): void {
		$options    = new FakeOptionStore( array( SettingsRepository::OPTION_NAME => SettingsSchema::defaults() ) );
		$repository = SettingsRepository::for_options( $options );

		$result = $repository->save(
			array(
				'delivery_enabled'  => 'on',
				'webp_quality'      => 900,
				'enabled_formats'   => array( 'gif', 'avif' ),
				'unknown_behavior'  => true,
				'queue_concurrency' => '4',
			)
		);

		$stored = $options->options[ SettingsRepository::OPTION_NAME ];

		self::assertTrue( $result->is_valid() );
		self::assertTrue( $result->has_code( SettingsResult::CODE_SAVED ) );
		self::assertTrue( $stored['delivery_enabled'] );
		self::assertSame( 100, $stored['webp_quality'] );
		self::assertSame( array( 'avif' ), $stored['enabled_formats'] );
		self::assertSame( 4, $stored['queue_concurrency'] );
		self::assertArrayNotHasKey( 'unknown_behavior', $stored );
	}

	/**
	 * Test typed getters return sanitized values.
	 *
	 * @return void
	 */
	public function test_typed_getters_return_sanitized_values(): void {
		$options    = new FakeOptionStore(
			array(
				SettingsRepository::OPTION_NAME => array_replace(
					SettingsSchema::defaults(),
					array(
						'automatic_optimization'          => '1',
						'media_library_controls'          => true,
						'allow_attachment_exclusion'      => false,
						'delivery_enabled'                => true,
						'delivery_emergency_disabled'     => true,
						'enabled_formats'                 => array( 'webp', 'avif' ),
						'format_preference'               => array( 'avif', 'webp' ),
						'webp_quality'                    => 70,
						'avif_quality'                    => 55,
						'minimum_savings_percent'         => 7,
						'max_retries'                     => 4,
						'worker_time_budget'              => 25,
						'queue_concurrency'               => 2,
						'log_retention_days'              => 14,
						'delete_data_on_uninstall'        => true,
						'delete_derivatives_on_uninstall' => true,
					)
				),
			)
		);
		$repository = SettingsRepository::for_options( $options );

		self::assertTrue( $repository->automatic_optimization_enabled() );
		self::assertTrue( $repository->media_library_controls_enabled() );
		self::assertFalse( $repository->attachment_exclusion_allowed() );
		self::assertTrue( $repository->delivery_enabled() );
		self::assertTrue( $repository->delivery_emergency_disabled() );
		self::assertSame( array( 'webp', 'avif' ), $repository->enabled_formats() );
		self::assertSame( array( 'avif', 'webp' ), $repository->format_preference() );
		self::assertSame( 70, $repository->quality_for( 'webp' ) );
		self::assertSame( 55, $repository->quality_for( 'avif' ) );
		self::assertSame( 7, $repository->minimum_savings_percent() );
		self::assertSame( 4, $repository->max_retries() );
		self::assertSame( 25, $repository->worker_time_budget() );
		self::assertSame( 2, $repository->queue_concurrency() );
		self::assertSame( 14, $repository->log_retention_days() );
		self::assertTrue( $repository->delete_data_on_uninstall() );
		self::assertTrue( $repository->delete_derivatives_on_uninstall() );
	}
}
