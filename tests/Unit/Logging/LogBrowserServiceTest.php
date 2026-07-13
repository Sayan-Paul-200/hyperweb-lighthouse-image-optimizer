<?php
/**
 * Tests for paginated log browsing.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogBrowserService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogQuery;
use PHPUnit\Framework\TestCase;

/**
 * Verifies logs-screen pagination and safe row projection.
 */
final class LogBrowserServiceTest extends TestCase {

	/**
	 * Test pagination metadata and safe projected fields are returned.
	 *
	 * @return void
	 */
	public function test_page_returns_safe_paginated_rows(): void {
		$database        = new FakeLogReadDatabase();
		$database->rows  = array(
			array(
				'created_at_gmt' => '2026-07-12 12:00:00',
				'level'          => 'warning',
				'code'           => 'worker_result_failed',
				'message'        => 'Conversion failed for one image size.',
				'attachment_id'  => 25,
				'job_id'         => 'job-123',
				'context_json'   => '{"path":"D:\\\\secret.txt"}',
			),
		);
		$database->count = 42;
		$service         = new LogBrowserService( $database, 'wp_hwlio_logs' );

		$page = $service->page(
			new LogQuery( 'warning', 'worker_result_failed', 25, 2, 20 )
		)->to_array();

		self::assertSame( 2, $page['page'] );
		self::assertSame( 20, $page['perPage'] );
		self::assertSame( 42, $page['totalItems'] );
		self::assertSame( 3, $page['totalPages'] );
		self::assertSame( 'warning', $page['filters']['level'] );
		self::assertSame( 'worker_result_failed', $page['items'][0]['code'] );
		self::assertSame( 'job-123', $page['items'][0]['job_id'] );
		self::assertArrayNotHasKey( 'context_json', $page['items'][0] );
		self::assertSame( 'page', $database->calls[0]['type'] );
		self::assertSame( 'count', $database->calls[1]['type'] );
	}
}
