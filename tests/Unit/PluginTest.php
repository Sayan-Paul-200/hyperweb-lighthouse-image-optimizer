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
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageMetaBox;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\ElementorHeroBackgroundMetaBox;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestApi;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Cli\CliCommands;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\LoadingAttributeManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadManager;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetManager;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorCriticalBackgroundPreloadManager;
use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\MultisiteIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadDeliveryAdapter;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommerceIntegration;
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
		self::assertSame( '0.1.0-alpha.4', $plugin->version() );
		self::assertInstanceOf( HookRegistrar::class, $plugin->hooks() );
		self::assertCount( 27, $plugin->providers() );
		self::assertInstanceOf( UpgradeRunner::class, $plugin->providers()[0] );
		self::assertInstanceOf( MultisiteIntegration::class, $plugin->providers()[1] );
		self::assertInstanceOf( SettingsApiRegistrar::class, $plugin->providers()[2] );
		self::assertInstanceOf( AdminController::class, $plugin->providers()[3] );
		self::assertInstanceOf( AdminAssets::class, $plugin->providers()[4] );
		self::assertInstanceOf( MediaLibraryIntegration::class, $plugin->providers()[5] );
		self::assertInstanceOf( MediaLibraryAssets::class, $plugin->providers()[6] );
		self::assertInstanceOf( RestApi::class, $plugin->providers()[7] );
		self::assertInstanceOf( CliCommands::class, $plugin->providers()[8] );
		self::assertInstanceOf( LogMaintenance::class, $plugin->providers()[9] );
		self::assertInstanceOf( QueueMaintenance::class, $plugin->providers()[10] );
		self::assertInstanceOf( AttachmentCleanup::class, $plugin->providers()[11] );
		self::assertInstanceOf( NewUploadIntegration::class, $plugin->providers()[12] );
		self::assertInstanceOf( CriticalImageMetaBox::class, $plugin->providers()[13] );
		self::assertInstanceOf( CriticalImageAssets::class, $plugin->providers()[14] );
		self::assertInstanceOf( ElementorHeroBackgroundMetaBox::class, $plugin->providers()[15] );
		self::assertInstanceOf( WooCommerceIntegration::class, $plugin->providers()[16] );
		self::assertInstanceOf( ElementorIntegration::class, $plugin->providers()[17] );
		self::assertInstanceOf( ReconciliationWorker::class, $plugin->providers()[18] );
		self::assertInstanceOf( OptimizationWorker::class, $plugin->providers()[19] );
		self::assertInstanceOf( LoadingAttributeManager::class, $plugin->providers()[20] );
		self::assertInstanceOf( ResponsivePreloadManager::class, $plugin->providers()[21] );
		self::assertInstanceOf( ElementorCriticalBackgroundPreloadManager::class, $plugin->providers()[22] );
		self::assertInstanceOf( ElementorBackgroundStylesheetManager::class, $plugin->providers()[23] );
		self::assertInstanceOf( OffloadDeliveryAdapter::class, $plugin->providers()[24] );
		self::assertInstanceOf( DeliveryManager::class, $plugin->providers()[25] );
		self::assertInstanceOf( I18n::class, $plugin->providers()[26] );
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
		$plugin                = Plugin::create();
		$found_admin           = false;
		$frontend_provider_map = array();

		foreach ( $plugin->providers() as $provider ) {
			self::assertInstanceOf( HookProviderInterface::class, $provider );

			if ( false !== strpos( get_class( $provider ), '\\Admin\\' ) ) {
				$found_admin = true;
			}

			if (
				in_array(
					get_class( $provider ),
					array(
						DeliveryManager::class,
						LoadingAttributeManager::class,
						ResponsivePreloadManager::class,
						ElementorCriticalBackgroundPreloadManager::class,
						ElementorBackgroundStylesheetManager::class,
					),
					true
				)
			) {
				$frontend_provider_map[ get_class( $provider ) ] = true;
			}
		}

		self::assertTrue( $found_admin );
		self::assertCount( 5, $frontend_provider_map );
		self::assertArrayHasKey( DeliveryManager::class, $frontend_provider_map );
		self::assertArrayHasKey( LoadingAttributeManager::class, $frontend_provider_map );
		self::assertArrayHasKey( ResponsivePreloadManager::class, $frontend_provider_map );
		self::assertArrayHasKey( ElementorCriticalBackgroundPreloadManager::class, $frontend_provider_map );
		self::assertArrayHasKey( ElementorBackgroundStylesheetManager::class, $frontend_provider_map );
	}
}
