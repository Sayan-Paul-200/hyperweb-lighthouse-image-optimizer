<?php
/**
 * Tests for the bulk scan service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies bounded dry-run scan behavior.
 */
final class BulkScanServiceTest extends TestCase {

	/**
	 * Test one bounded page classifies states and persists candidate totals.
	 *
	 * @return void
	 */
	public function test_start_scan_classifies_states_and_completes_short_page(): void {
		$runtime                 = new FakeBulkScannerRuntime();
		$runtime->pages[0]       = array( 10, 11, 12, 13, 14, 15, 16 );
		$runtime->images[16]     = false;
		$store                   = new FakeAttachmentMetaStore();
		$store->meta[10][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => AttachmentStatus::STATE_EXCLUDED,
			'excluded' => true,
		);
		$store->meta[11][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_QUEUED,
		);
		$store->meta[12][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_OPTIMIZED,
			'formats' => array( 'webp', 'avif' ),
		);
		$store->meta[13][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_PARTIAL,
			'formats' => array( 'webp' ),
		);
		$store->meta[14][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_FAILED,
		);
		$store->meta[15][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_STALE,
		);
		$sessions = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$service  = new BulkScanService(
			$runtime,
			$sessions,
			new AttachmentStatusReader( $store ),
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp', 'avif' ),
				)
			),
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				return 'abc1234def5678abc1234def5678abc';
			}
		);

		$session = $service->start_scan( new BulkScanFilters(), 7 );

		self::assertTrue( $session->progress()->complete() );
		self::assertSame( 7, $session->summary()->scanned() );
		self::assertSame( 3, $session->summary()->eligible() );
		self::assertSame( 1, $session->summary()->excluded() );
		self::assertSame( 1, $session->summary()->active() );
		self::assertSame( 1, $session->summary()->already_optimized() );
		self::assertSame( 1, $session->summary()->skipped() );
		self::assertSame( 3, $session->progress()->candidate_total() );
		self::assertSame( array( 13, 14, 15 ), $sessions->read_candidate_page( $session, 1, 20 ) );
	}

	/**
	 * Test continuation resumes from the stored cursor in bounded pages.
	 *
	 * @return void
	 */
	public function test_continue_scan_resumes_from_cursor(): void {
		$runtime           = new FakeBulkScannerRuntime();
		$runtime->pages[0] = range( 1, 100 );
		$runtime->pages[100] = array( 101 );
		$store             = new FakeAttachmentMetaStore();
		$sessions          = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$service           = new BulkScanService(
			$runtime,
			$sessions,
			new AttachmentStatusReader( $store ),
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp' ),
				)
			),
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				return 'feed1234feed1234feed1234feed1234';
			}
		);

		$session = $service->start_scan( new BulkScanFilters(), 7 );
		self::assertFalse( $session->progress()->complete() );
		self::assertSame( 100, $session->progress()->last_processed_id() );

		$continued = $service->continue_scan( $session->token(), 7 );

		self::assertTrue( $continued->progress()->complete() );
		self::assertSame( 101, $continued->progress()->last_processed_id() );
		self::assertSame( 101, $continued->summary()->scanned() );
		self::assertSame( 101, $continued->summary()->eligible() );
	}

	/**
	 * Test missing-only scans require some ready formats already present.
	 *
	 * @return void
	 */
	public function test_missing_only_scope_filters_candidates_conservatively(): void {
		$runtime           = new FakeBulkScannerRuntime();
		$runtime->pages[0] = array( 20, 21, 22 );
		$store             = new FakeAttachmentMetaStore();
		$store->meta[20][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_PARTIAL,
			'formats' => array( 'webp' ),
		);
		$store->meta[21][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_UNPROCESSED,
		);
		$store->meta[22][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_OPTIMIZED,
			'formats' => array( 'webp', 'avif' ),
		);
		$sessions = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$service  = new BulkScanService(
			$runtime,
			$sessions,
			new AttachmentStatusReader( $store ),
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp', 'avif' ),
				)
			),
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				return 'deadbeefdeadbeefdeadbeefdeadbeef';
			}
		);

		$session = $service->start_scan(
			new BulkScanFilters( BulkScanFilters::SCOPE_MISSING_ONLY ),
			7
		);

		self::assertSame( 1, $session->summary()->eligible() );
		self::assertSame( 1, $session->summary()->already_optimized() );
		self::assertSame( 1, $session->summary()->skipped() );
		self::assertSame( array( 20 ), $sessions->read_candidate_page( $session, 1, 20 ) );
	}
}
