<?php
/**
 * Tests for log pruning.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Logging\LogPruner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies retention cutoff and bounded deletes.
 */
final class LogPrunerTest extends TestCase {

	/**
	 * Test default retention and batch size.
	 *
	 * @return void
	 */
	public function test_default_retention_uses_thirty_days_and_limit_500(): void {
		$database                = new FakeLogDatabase();
		$database->delete_result = 12;
		$pruner                  = new LogPruner(
			new FakeOptionStore(),
			$database,
			'wp_hwlio_logs',
			static function (): int {
				return 1783555200;
			}
		);

		self::assertSame( 12, $pruner->prune() );
		self::assertSame( 'wp_hwlio_logs', $database->delete_table );
		self::assertSame( '2026-06-09 00:00:00', $database->delete_cutoff_gmt );
		self::assertSame( 500, $database->delete_limit );
	}

	/**
	 * Test configured retention days.
	 *
	 * @return void
	 */
	public function test_configured_retention_days_are_used(): void {
		$database = new FakeLogDatabase();
		$options  = new FakeOptionStore(
			array(
				Installer::OPTION_SETTINGS => array( 'log_retention_days' => 7 ),
			)
		);
		$pruner   = new LogPruner(
			$options,
			$database,
			'wp_hwlio_logs',
			static function (): int {
				return 1783555200;
			}
		);

		$pruner->prune();

		self::assertSame( '2026-07-02 00:00:00', $database->delete_cutoff_gmt );
	}

	/**
	 * Test invalid settings fall back to defaults.
	 *
	 * @return void
	 */
	public function test_invalid_retention_setting_falls_back_to_default(): void {
		$database = new FakeLogDatabase();
		$options  = new FakeOptionStore(
			array(
				Installer::OPTION_SETTINGS => array( 'log_retention_days' => 'forever' ),
			)
		);
		$pruner   = new LogPruner(
			$options,
			$database,
			'wp_hwlio_logs',
			static function (): int {
				return 1783555200;
			}
		);

		$pruner->prune();

		self::assertSame( '2026-06-09 00:00:00', $database->delete_cutoff_gmt );
		self::assertSame( LogPruner::BATCH_SIZE, $database->delete_limit );
	}
}
