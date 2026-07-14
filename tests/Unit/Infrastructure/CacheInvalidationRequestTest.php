<?php
/**
 * Tests for the cache invalidation request contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;
use PHPUnit\Framework\TestCase;

/**
 * Verifies cache invalidation requests stay normalized and path-safe.
 */
final class CacheInvalidationRequestTest extends TestCase {

	/**
	 * Test requests expose the formal 11.2 payload shape through getters and arrays.
	 *
	 * @return void
	 */
	public function test_requests_expose_the_formal_payload_shape(): void {
		$request = new CacheInvalidationRequest(
			CacheInvalidationRequest::EVENT_DERIVATIVES_DELETED,
			77,
			'Reconciliation Obsolete Derivatives',
			array( '2026/07/hero.jpg.hwlio.webp' ),
			array( 'webp' ),
			'2026-07-13 12:00:00'
		);

		self::assertSame( CacheInvalidationRequest::EVENT_DERIVATIVES_DELETED, $request->event() );
		self::assertSame( 77, $request->attachment_id() );
		self::assertSame( 'reconciliation_obsolete_derivatives', $request->reason() );
		self::assertSame( array( '2026/07/hero.jpg.hwlio.webp' ), $request->relative_paths() );
		self::assertSame( array( 'webp' ), $request->formats() );
		self::assertSame( '2026-07-13 12:00:00', $request->timestamp_gmt() );
		self::assertSame(
			array(
				'event'          => 'derivatives_deleted',
				'reason'         => 'reconciliation_obsolete_derivatives',
				'attachment_id'  => 77,
				'relative_paths' => array( '2026/07/hero.jpg.hwlio.webp' ),
				'formats'        => array( 'webp' ),
				'timestamp_gmt'  => '2026-07-13 12:00:00',
			),
			$request->to_array()
		);
	}

	/**
	 * Test invalid event, format, and path data are normalized conservatively.
	 *
	 * @return void
	 */
	public function test_invalid_event_format_and_path_data_are_normalized_conservatively(): void {
		$request = new CacheInvalidationRequest(
			'unsupported',
			-4,
			'  ',
			array(
				'../outside.webp',
				'https://example.test/hero.webp',
				'2026/07/hero.jpg.hwlio.avif',
				'2026/07/hero.jpg.hwlio.avif',
			),
			array( 'AVIF', 'gif', 'webp', 'webp' ),
			'2026-07-13 13:00:00'
		);

		self::assertSame( CacheInvalidationRequest::EVENT_DERIVATIVES_SAVED, $request->event() );
		self::assertSame( 0, $request->attachment_id() );
		self::assertSame( 'unspecified', $request->reason() );
		self::assertSame( array( '2026/07/hero.jpg.hwlio.avif' ), $request->relative_paths() );
		self::assertSame( array( 'avif', 'webp' ), $request->formats() );
	}
}
