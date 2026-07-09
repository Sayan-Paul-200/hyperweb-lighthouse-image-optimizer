<?php
/**
 * Tests for the application composition root.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner;
use HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance;
use HyperWeb\LighthouseImageOptimizer\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that the namespaced plugin root owns service composition.
 */
final class PluginTest extends TestCase {

	/**
	 * Test production composition defaults.
	 *
	 * @return void
	 */
	public function test_create_builds_composition_root(): void {
		$plugin = Plugin::create();

		self::assertSame( 'hyperweb-lighthouse-image-optimizer', $plugin->slug() );
		self::assertSame( '0.1.0-alpha.3', $plugin->version() );
		self::assertInstanceOf( HookRegistrar::class, $plugin->hooks() );
		self::assertCount( 3, $plugin->providers() );
		self::assertInstanceOf( UpgradeRunner::class, $plugin->providers()[0] );
		self::assertInstanceOf( LogMaintenance::class, $plugin->providers()[1] );
		self::assertInstanceOf( I18n::class, $plugin->providers()[2] );
	}

	/**
	 * Test providers register hooks through one shared registrar.
	 *
	 * @return void
	 */
	public function test_register_hooks_uses_shared_registrar(): void {
		$hooks  = new HookRegistrar();
		$plugin = new Plugin(
			'test-version',
			$hooks,
			array(
				new I18n( Plugin::SLUG, 'hyperweb-lighthouse-image-optimizer/languages/' ),
			)
		);

		$plugin->register_hooks();
		$plugin->register_hooks();

		self::assertSame( $hooks, $plugin->hooks() );
		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'plugins_loaded', $hooks->actions()[0]['hook'] );
		self::assertSame( 10, $hooks->actions()[0]['priority'] );
		self::assertSame( 1, $hooks->actions()[0]['accepted_args'] );
		self::assertSame( array(), $hooks->filters() );
	}

	/**
	 * Test no admin or delivery providers are composed yet.
	 *
	 * @return void
	 */
	public function test_create_does_not_compose_admin_or_delivery_providers(): void {
		$plugin = Plugin::create();

		foreach ( $plugin->providers() as $provider ) {
			self::assertInstanceOf( HookProviderInterface::class, $provider );
			self::assertStringNotContainsString( '\\Admin\\', get_class( $provider ) );
			self::assertStringNotContainsString( '\\Delivery\\', get_class( $provider ) );
		}
	}
}
