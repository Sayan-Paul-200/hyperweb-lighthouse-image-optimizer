<?php
/**
 * Tests for dashboard status refresh requests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusRefreshService;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeSingleActionScheduler;
use PHPUnit\Framework\TestCase;

/**
 * Verifies async statistics recalculation requests and refresh-state summaries.
 */
final class StatusRefreshServiceTest extends TestCase {

	/**
	 * Test summary reports the cache timestamp and pending flag.
	 *
	 * @return void
	 */
	public function test_summary_reports_cache_timestamp_and_pending_flag(): void {
		$scheduler            = new FakeSingleActionScheduler();
		$scheduler->scheduled = true;
		$service              = new StatusRefreshService( $scheduler );

		$summary = $service->summary( StatisticsCache::empty( '2026-07-12 10:00:00' ) );

		self::assertSame( '2026-07-12 10:00:00', $summary['generated_at_gmt'] );
		self::assertTrue( $summary['pending'] );
	}

	/**
	 * Test request_recalculation returns already_pending when an action exists.
	 *
	 * @return void
	 */
	public function test_request_recalculation_returns_already_pending_when_action_exists(): void {
		$scheduler            = new FakeSingleActionScheduler();
		$scheduler->scheduled = true;
		$service              = new StatusRefreshService( $scheduler );

		$result = $service->request_recalculation()->to_array();

		self::assertSame( 'already_pending', $result['result_code'] );
		self::assertCount( 0, $scheduler->enqueue_calls );
	}

	/**
	 * Test request_recalculation enqueues a unique async action.
	 *
	 * @return void
	 */
	public function test_request_recalculation_enqueues_unique_async_action(): void {
		$scheduler = new FakeSingleActionScheduler();
		$service   = new StatusRefreshService( $scheduler );

		$result = $service->request_recalculation()->to_array();

		self::assertSame( 'queued', $result['result_code'] );
		self::assertCount( 1, $scheduler->enqueue_calls );
		self::assertSame( 'hwlio_reconcile_statistics', $scheduler->enqueue_calls[0]['hook'] );
		self::assertSame( 'hwlio', $scheduler->enqueue_calls[0]['group'] );
		self::assertTrue( $scheduler->enqueue_calls[0]['unique'] );
	}

	/**
	 * Test request_recalculation returns unavailable when enqueue fails.
	 *
	 * @return void
	 */
	public function test_request_recalculation_returns_unavailable_when_enqueue_fails(): void {
		$scheduler                 = new FakeSingleActionScheduler();
		$scheduler->enqueue_result = false;
		$service                   = new StatusRefreshService( $scheduler );

		$result = $service->request_recalculation();

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'unavailable', $result->to_array()['result_code'] );
	}
}
