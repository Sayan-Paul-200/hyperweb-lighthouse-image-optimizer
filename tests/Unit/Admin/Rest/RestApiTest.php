<?php
// phpcs:ignoreFile Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test-only fake controller lives with its only consumer.
/**
 * Tests for the REST API provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestApi;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestControllerInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Verifies REST provider hook registration and controller delegation.
 */
final class RestApiTest extends TestCase {

	/**
	 * Test hook registration adds only rest_api_init.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_rest_api_init(): void {
		$hooks    = new HookRegistrar();
		$provider = new RestApi( array( new FakeRestController() ) );

		$provider->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'rest_api_init', $hooks->actions()[0]['hook'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test register_routes delegates to each controller.
	 *
	 * @return void
	 */
	public function test_register_routes_delegates_to_registered_controllers(): void {
		$first    = new FakeRestController();
		$second   = new FakeRestController();
		$provider = new RestApi( array( $first, $second ) );

		$provider->register_routes();

		self::assertSame( 1, $first->registered );
		self::assertSame( 1, $second->registered );
	}
}

/**
 * Fake controller for provider tests.
 */
final class FakeRestController implements RestControllerInterface {

	/**
	 * Registration count.
	 *
	 * @var int
	 */
	public $registered = 0;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		++$this->registered;
	}
}
