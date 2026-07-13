<?php
/**
 * Statistics cache tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;
use PHPUnit\Framework\TestCase;

/**
 * Verifies statistics cache normalization.
 */
final class StatisticsCacheTest extends TestCase {

	/**
	 * Test cache normalizes states, totals, and formats.
	 *
	 * @return void
	 */
	public function test_cache_normalizes_payload(): void {
		$cache = new StatisticsCache(
			'2026-07-11 12:00:00',
			array(
				'optimized' => 2,
				'failed'    => '3',
			),
			array(
				'attachments_considered' => '5',
				'source_bytes'           => 1500,
				'savings_percent'        => 12.345,
			),
			array(
				'avif' => array(
					'sources_ready'   => 1,
					'source_bytes'    => 900,
					'generated_bytes' => 500,
					'savings_bytes'   => 400,
				),
			)
		);

		self::assertSame( '2026-07-11 12:00:00', $cache->generated_at_gmt() );
		self::assertSame( 2, $cache->attachment_states()['optimized'] );
		self::assertSame( 3, $cache->attachment_states()['failed'] );
		self::assertSame( 0, $cache->attachment_states()['queued'] );
		self::assertSame( 5, $cache->totals()['attachments_considered'] );
		self::assertSame( 1500, $cache->totals()['source_bytes'] );
		self::assertSame( 12.35, $cache->totals()['savings_percent'] );
		self::assertSame( 1, $cache->formats()['avif']['sources_ready'] );
		self::assertSame( 0, $cache->formats()['webp']['sources_ready'] );
		self::assertSame( StatisticsCache::SCHEMA_VERSION, $cache->to_array()['schema_version'] );
	}
}
