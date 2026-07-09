<?php
/**
 * Tests for runtime upgrade runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\InstallerResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner;
use PHPUnit\Framework\TestCase;

/**
 * Verifies runtime upgrade hook registration.
 */
final class UpgradeRunnerTest extends TestCase {

	/**
	 * Test upgrade runner registers before i18n.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_priority_one_upgrade_check(): void {
		$runner = new UpgradeRunner(
			new Installer(
				new FakeOptionStore(),
				new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) ),
				'0.1.0-alpha.3',
				'1',
				1
			)
		);
		$hooks  = new HookRegistrar();

		$runner->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'plugins_loaded', $hooks->actions()[0]['hook'] );
		self::assertSame( 1, $hooks->actions()[0]['priority'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
	}
}
