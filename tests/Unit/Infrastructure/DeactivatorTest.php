<?php
/**
 * Tests for deactivation cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\Deactivator;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies deactivation remains non-destructive.
 */
final class DeactivatorTest extends TestCase {

	/**
	 * Test deactivation delegates only scheduled maintenance cleanup.
	 *
	 * @return void
	 */
	public function test_deactivation_unschedules_maintenance_without_data_cleanup(): void {
		$cleaner     = new FakeScheduledActionCleaner();
		$deactivator = new Deactivator( $cleaner );

		$result = $deactivator->deactivate();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_MAINTENANCE_UNSCHEDULED ) );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DEACTIVATION_COMPLETE ) );
		self::assertSame( 1, $cleaner->calls );
	}
}
