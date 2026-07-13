<?php
/**
 * Tests for log retention updates.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogRetentionService;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use PHPUnit\Framework\TestCase;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;

/**
 * Verifies retention updates reuse the settings repository sanitizer.
 */
final class LogRetentionServiceTest extends TestCase {

	/**
	 * Test requested retention days are normalized through settings persistence.
	 *
	 * @return void
	 */
	public function test_update_returns_normalized_saved_retention_days(): void {
		$options = new FakeOptionStore(
			array(
				SettingsRepository::OPTION_NAME => array(
					'log_retention_days' => 30,
				),
			)
		);
		$service = new LogRetentionService( SettingsRepository::for_options( $options ) );

		$result = $service->update( 9000 )->to_array();

		self::assertSame( 3650, $result['retentionDays'] );
		self::assertSame( 3650, $options->options[ SettingsRepository::OPTION_NAME ]['log_retention_days'] );
	}
}
