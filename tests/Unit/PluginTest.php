<?php
/**
 * Tests for the application composition root.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminController;
use HyperWeb\LighthouseImageOptimizer\Admin\Assets as AdminAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryIntegration;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestApi;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryManager;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner;
use HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance;
use HyperWeb\LighthouseImageOptimizer\Plugin;
use HyperWeb\LighthouseImageOptimizer\Queue\NewUploadIntegration;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationWorker;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueMaintenance;
use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationWorker;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiRegistrar;
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
		self::assertCount( 15, $plugin->providers() );
		self::assertInstanceOf( UpgradeRunner::class, $plugin->providers()[0] );
		self::assertInstanceOf( SettingsApiRegistrar::class, $plugin->providers()[1] );
		self::assertInstanceOf( AdminController::class, $plugin->providers()[2] );
		self::assertInstanceOf( AdminAssets::class, $plugin->providers()[3] );
		self::assertInstanceOf( MediaLibraryIntegration::class, $plugin->providers()[4] );
		self::assertInstanceOf( MediaLibraryAssets::class, $plugin->providers()[5] );
		self::assertInstanceOf( RestApi::class, $plugin->providers()[6] );
		self::assertInstanceOf( LogMaintenance::class, $plugin->providers()[7] );
		self::assertInstanceOf( QueueMaintenance::class, $plugin->providers()[8] );
		self::assertInstanceOf( AttachmentCleanup::class, $plugin->providers()[9] );
		self::assertInstanceOf( NewUploadIntegration::class, $plugin->providers()[10] );
		self::assertInstanceOf( ReconciliationWorker::class, $plugin->providers()[11] );
		self::assertInstanceOf( OptimizationWorker::class, $plugin->providers()[12] );
		self::assertInstanceOf( DeliveryManager::class, $plugin->providers()[13] );
		self::assertInstanceOf( I18n::class, $plugin->providers()[14] );
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
	 * Test admin composition is active and only one delivery provider is composed.
	 *
	 * @return void
	 */
	public function test_create_composes_admin_and_only_one_delivery_provider(): void {
		$plugin         = Plugin::create();
		$found_admin    = false;
		$delivery_count = 0;

		foreach ( $plugin->providers() as $provider ) {
			self::assertInstanceOf( HookProviderInterface::class, $provider );

			if ( false !== strpos( get_class( $provider ), '\\Admin\\' ) ) {
				$found_admin = true;
			}

			if ( false !== strpos( get_class( $provider ), '\\Delivery\\' ) ) {
				++$delivery_count;
				self::assertInstanceOf( DeliveryManager::class, $provider );
			}
		}

		self::assertTrue( $found_admin );
		self::assertSame( 1, $delivery_count );
	}
}
