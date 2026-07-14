<?php
/**
 * Tests for the WordPress cache invalidation dispatcher.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

require_once __DIR__ . '/InfrastructureTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressCacheInvalidationDispatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the stable cache invalidation action contract.
 */
final class WordPressCacheInvalidationDispatcherTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_actions'] );
	}

	/**
	 * Test dispatcher emits the stable action with the formal payload.
	 *
	 * @return void
	 */
	public function test_dispatcher_emits_the_stable_action_with_the_formal_payload(): void {
		$GLOBALS['hwlio_test_actions'] = array();
		$dispatcher                    = new WordPressCacheInvalidationDispatcher();

		$dispatcher->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_SAVED,
				45,
				'save_results',
				array( '2026/07/hero.jpg.hwlio.webp' ),
				array( 'webp' ),
				'2026-07-13 14:00:00'
			)
		);

		$actions = $GLOBALS['hwlio_test_actions'];

		self::assertIsArray( $actions );
		self::assertCount( 1, $actions );
		$action = reset( $actions );

		self::assertIsArray( $action );
		self::assertArrayHasKey( 'hook', $action );
		self::assertArrayHasKey( 'args', $action );
		self::assertIsArray( $action['args'] );
		self::assertArrayHasKey( 0, $action['args'] );
		self::assertArrayHasKey( 1, $action['args'] );
		self::assertSame( LifecyclePolicy::HOOK_CACHE_INVALIDATION_REQUESTED, $action['hook'] );
		self::assertSame( 45, $action['args'][0] );
		self::assertSame(
			array(
				'event'          => 'derivatives_saved',
				'reason'         => 'save_results',
				'attachment_id'  => 45,
				'relative_paths' => array( '2026/07/hero.jpg.hwlio.webp' ),
				'formats'        => array( 'webp' ),
				'timestamp_gmt'  => '2026-07-13 14:00:00',
			),
			$action['args'][1]
		);
	}

	/**
	 * Test dispatcher skips invalid requests.
	 *
	 * @return void
	 */
	public function test_dispatcher_skips_invalid_requests(): void {
		$dispatcher = new WordPressCacheInvalidationDispatcher();

		$dispatcher->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_SAVED,
				0,
				'save_results',
				array( '2026/07/hero.jpg.hwlio.webp' ),
				array( 'webp' ),
				'2026-07-13 14:05:00'
			)
		);
		$dispatcher->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_SAVED,
				45,
				'save_results',
				array( '../outside.webp' ),
				array( 'webp' ),
				'2026-07-13 14:05:00'
			)
		);

		self::assertArrayNotHasKey( 'hwlio_test_actions', $GLOBALS );
	}
}
