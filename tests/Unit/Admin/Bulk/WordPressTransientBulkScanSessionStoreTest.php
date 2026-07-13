<?php
/**
 * Tests for the bulk scan session store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies transient-backed scan-session persistence and paging.
 */
final class WordPressTransientBulkScanSessionStoreTest extends TestCase {

	/**
	 * Test sessions persist candidate chunks and preview pages safely.
	 *
	 * @return void
	 */
	public function test_append_candidate_ids_persists_chunked_preview_pages(): void {
		$transients = new FakeTransientStore();
		$store      = new WordPressTransientBulkScanSessionStore( $transients );
		$session    = BulkScanSession::start(
			'token1234abcd5678token1234abcd5678',
			7,
			'2026-07-12 00:00:00',
			new BulkScanFilters()
		);

		self::assertTrue( $store->save( $session ) );

		$session = $store->append_candidate_ids( $session, range( 1, 55 ) );
		self::assertSame( 2, $session->progress()->candidate_chunk_count() );
		self::assertSame( 55, $session->progress()->candidate_total() );
		self::assertSame( range( 1, 20 ), $store->read_candidate_page( $session, 1, 20 ) );
		self::assertSame( range( 21, 40 ), $store->read_candidate_page( $session, 2, 20 ) );
		self::assertSame( range( 41, 55 ), $store->read_candidate_page( $session, 3, 20 ) );
	}

	/**
	 * Test expired sessions disappear safely.
	 *
	 * @return void
	 */
	public function test_load_returns_null_after_expiration(): void {
		$transients = new FakeTransientStore();
		$store      = new WordPressTransientBulkScanSessionStore( $transients );
		$session    = BulkScanSession::start(
			'feed1234abcd5678feed1234abcd5678',
			7,
			'2026-07-12 00:00:00',
			new BulkScanFilters()
		);

		self::assertTrue( $store->save( $session ) );

		$transients->now += WordPressTransientBulkScanSessionStore::TTL_SECONDS + 1;

		self::assertNull( $store->load( $session->token() ) );
	}
}
