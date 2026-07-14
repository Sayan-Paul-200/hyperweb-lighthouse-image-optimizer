<?php
/**
 * Tests for the PageSpeed credentials store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\WordPressPageSpeedCredentialsStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies autoload-disabled PSI credential persistence behavior.
 */
final class WordPressPageSpeedCredentialsStoreTest extends TestCase {

	/**
	 * Test a blank submission preserves an existing saved key.
	 *
	 * @return void
	 */
	public function test_blank_submission_preserves_existing_key(): void {
		$options = new FakeOptionStore(
			array(
				WordPressPageSpeedCredentialsStore::OPTION_NAME => array(
					'api_key' => 'saved-key',
				),
			)
		);
		$store   = WordPressPageSpeedCredentialsStore::for_options( $options );

		$result = $store->save_submission(
			array(
				'api_key' => '',
			)
		);

		self::assertSame( array( 'api_key' => 'saved-key' ), $result );
		self::assertSame( 'saved-key', $store->api_key() );
		self::assertSame( 'no', $options->autoload[ WordPressPageSpeedCredentialsStore::OPTION_NAME ] );
	}

	/**
	 * Test a non-empty submission is stored with autoload disabled.
	 *
	 * @return void
	 */
	public function test_non_empty_submission_is_saved_with_autoload_disabled(): void {
		$options = new FakeOptionStore();
		$store   = WordPressPageSpeedCredentialsStore::for_options( $options );

		$result = $store->save_submission(
			array(
				'api_key' => '  live-key  ',
			)
		);

		self::assertSame( array( 'api_key' => 'live-key' ), $result );
		self::assertSame( 'live-key', $options->options[ WordPressPageSpeedCredentialsStore::OPTION_NAME ]['api_key'] );
		self::assertSame( 'no', $options->autoload[ WordPressPageSpeedCredentialsStore::OPTION_NAME ] );
	}

	/**
	 * Test the explicit clear flag removes the stored key.
	 *
	 * @return void
	 */
	public function test_clear_flag_removes_saved_key(): void {
		$options = new FakeOptionStore(
			array(
				WordPressPageSpeedCredentialsStore::OPTION_NAME => array(
					'api_key' => 'saved-key',
				),
			)
		);
		$store   = WordPressPageSpeedCredentialsStore::for_options( $options );

		$result = $store->save_submission(
			array(
				'clear_api_key' => '1',
			)
		);

		self::assertSame( array( 'api_key' => '' ), $result );
		self::assertFalse( $store->has_api_key() );
		self::assertSame( '', $options->options[ WordPressPageSpeedCredentialsStore::OPTION_NAME ]['api_key'] );
		self::assertSame( 'no', $options->autoload[ WordPressPageSpeedCredentialsStore::OPTION_NAME ] );
	}
}
