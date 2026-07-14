<?php
/**
 * Tests for the legacy activation entry point.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Includes;

require_once dirname( __DIR__, 3 ) . '/includes/class-hyperweb-lighthouse-image-optimizer-activator.php';

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\InstallerResult;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeLogTableInstaller;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies activation accepts network-wide input without iterating sites.
 */
final class ActivatorTest extends TestCase {

	/**
	 * Test network activation still installs only one current site context.
	 *
	 * @return void
	 */
	public function test_network_activation_installs_only_once(): void {
		$installer = new Installer(
			new FakeOptionStore(),
			new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) ),
			'0.1.0-alpha.3',
			'1',
			1,
			static function (): string {
				return '2026-07-13 00:00:00';
			}
		);

		\Hyperweb_Lighthouse_Image_Optimizer_Activator::activate(
			true,
			static function () use ( $installer ): Installer {
				return $installer;
			}
		);

		$reflection = new \ReflectionProperty( Installer::class, 'log_table_installer' );
		$reflection->setAccessible( true );
		$log_table = $reflection->getValue( $installer );

		self::assertInstanceOf( FakeLogTableInstaller::class, $log_table );
		self::assertSame( 1, $log_table->install_calls );
	}
}
