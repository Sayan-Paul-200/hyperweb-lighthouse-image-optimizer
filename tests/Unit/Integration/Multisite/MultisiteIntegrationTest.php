<?php
/**
 * Tests for multisite new-site initialization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Multisite;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\InstallerResult;
use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\MultisiteIntegration;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeLogTableInstaller;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies new-site initialization stays network-scoped and restore-safe.
 */
final class MultisiteIntegrationTest extends TestCase {

	/**
	 * Test hook registration adds only wp_initialize_site.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_wp_initialize_site(): void {
		$hooks    = new HookRegistrar();
		$runtime  = new FakeSiteContextRuntime();
		$provider = new MultisiteIntegration(
			$runtime,
			'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php',
			'0.1.0-alpha.4',
			'1',
			1
		);

		$provider->register_hooks( $hooks );

		self::assertSame( array(), $hooks->filters() );
		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'wp_initialize_site', $hooks->actions()[0]['hook'] );
		self::assertSame( 2, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test non-multisite requests do nothing.
	 *
	 * @return void
	 */
	public function test_non_multisite_requests_do_nothing(): void {
		$runtime               = new FakeSiteContextRuntime();
		$runtime->is_multisite = false;
		$installer             = $this->installer();
		$provider              = $this->provider( $runtime, $installer );

		$provider->initialize_site( (object) array( 'blog_id' => 23 ), array() );

		self::assertSame( 0, $this->log_table_installs( $installer ) );
		self::assertSame( array(), $runtime->switched_sites );
		self::assertSame( 0, $runtime->restore_calls );
	}

	/**
	 * Test non-network-active installs do nothing.
	 *
	 * @return void
	 */
	public function test_non_network_active_install_does_nothing(): void {
		$runtime   = new FakeSiteContextRuntime();
		$installer = $this->installer();
		$provider  = $this->provider( $runtime, $installer );

		$provider->initialize_site( (object) array( 'blog_id' => 23 ), array() );

		self::assertSame( 0, $this->log_table_installs( $installer ) );
		self::assertSame( array(), $runtime->switched_sites );
		self::assertSame( 0, $runtime->restore_calls );
	}

	/**
	 * Test network-active new sites initialize once and always restore.
	 *
	 * @return void
	 */
	public function test_network_active_new_site_initializes_and_restores(): void {
		$runtime                         = new FakeSiteContextRuntime();
		$runtime->network_active_plugins = array( 'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php' );
		$installer                       = $this->installer();
		$provider                        = $this->provider( $runtime, $installer );

		$provider->initialize_site( (object) array( 'blog_id' => 23 ), array() );

		self::assertSame( 1, $this->log_table_installs( $installer ) );
		self::assertSame( array( 23 ), $runtime->switched_sites );
		self::assertSame( 1, $runtime->restore_calls );
	}

	/**
	 * Test restore still happens when installer creation throws.
	 *
	 * @return void
	 */
	public function test_restore_always_happens_when_installer_throws(): void {
		$runtime                         = new FakeSiteContextRuntime();
		$runtime->network_active_plugins = array( 'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php' );
		$provider                        = new MultisiteIntegration(
			$runtime,
			'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php',
			'0.1.0-alpha.4',
			'1',
			1,
			static function (): Installer {
				throw new \RuntimeException( 'Installer failed.' );
			}
		);

		$provider->initialize_site( (object) array( 'blog_id' => 23 ), array() );

		self::assertSame( array( 23 ), $runtime->switched_sites );
		self::assertSame( 1, $runtime->restore_calls );
	}

	/**
	 * Build one installer fixture.
	 *
	 * @return Installer
	 */
	private function installer(): Installer {
		return new Installer(
			new FakeOptionStore(),
			new FakeLogTableInstaller( InstallerResult::success( array( InstallerResult::CODE_LOG_TABLE_READY ) ) ),
			'0.1.0-alpha.4',
			'1',
			1,
			static function (): string {
				return '2026-07-13 00:00:00';
			}
		);
	}

	/**
	 * Build one provider fixture.
	 *
	 * @param FakeSiteContextRuntime $runtime Site runtime.
	 * @param Installer              $installer Installer.
	 * @return MultisiteIntegration
	 */
	private function provider( FakeSiteContextRuntime $runtime, Installer $installer ): MultisiteIntegration {
		return new MultisiteIntegration(
			$runtime,
			'hyperweb-lighthouse-image-optimizer/hyperweb-lighthouse-image-optimizer.php',
			'0.1.0-alpha.4',
			'1',
			1,
			static function () use ( $installer ): Installer {
				return $installer;
			}
		);
	}

	/**
	 * Read the fake log table install call count.
	 *
	 * @param Installer $installer Installer.
	 * @return int
	 */
	private function log_table_installs( Installer $installer ): int {
		$reflection = new \ReflectionProperty( Installer::class, 'log_table_installer' );
		$reflection->setAccessible( true );
		$log_table = $reflection->getValue( $installer );

		return $log_table instanceof FakeLogTableInstaller ? $log_table->install_calls : 0;
	}
}
