<?php
/**
 * Tests for multisite uninstall batching.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\NetworkUninstaller;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Verifies bounded network uninstall behavior.
 */
final class NetworkUninstallerTest extends TestCase {

	/**
	 * Test network uninstall batches site IDs and restores each site.
	 *
	 * @return void
	 */
	public function test_network_uninstall_batches_and_restores_each_site(): void {
		$provider_calls = array();
		$switched       = array();
		$restored       = 0;
		$uninstall_runs = 0;

		$network = new NetworkUninstaller(
			static function ( int $offset, int $limit ) use ( &$provider_calls ): array {
				$provider_calls[] = array( $offset, $limit );
				$sites            = array( 11, 12, 13 );

				return array_slice( $sites, $offset, $limit );
			},
			static function ( int $site_id ) use ( &$switched ): void {
				$switched[] = $site_id;
			},
			static function () use ( &$restored ): void {
				++$restored;
			},
			static function () use ( &$uninstall_runs ): LifecycleResult {
				++$uninstall_runs;

				return LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_COMPLETE ) );
			},
			2
		);

		$result = $network->uninstall();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_NETWORK_BATCH_PROCESSED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_NETWORK_UNINSTALL_COMPLETED ) );
		self::assertSame( array( array( 0, 2 ), array( 2, 2 ) ), $provider_calls );
		self::assertSame( array( 11, 12, 13 ), $switched );
		self::assertSame( 3, $restored );
		self::assertSame( 3, $uninstall_runs );
	}

	/**
	 * Test network uninstall restores site after a site-level failure.
	 *
	 * @return void
	 */
	public function test_network_uninstall_restores_site_after_failure(): void {
		$restored = 0;

		$network = new NetworkUninstaller(
			static function ( int $offset, int $limit ): array {
				unset( $offset, $limit );

				return array( 11 );
			},
			static function (): void {},
			static function () use ( &$restored ): void {
				++$restored;
			},
			static function (): LifecycleResult {
				throw new RuntimeException( 'Site cleanup failed.' );
			},
			10
		);

		$result = $network->uninstall();

		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_NETWORK_SITE_FAILED ) );
		self::assertSame( 1, $restored );
	}
}
