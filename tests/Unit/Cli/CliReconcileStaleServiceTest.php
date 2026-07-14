<?php
/**
 * Tests for the CLI stale reconciliation runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Cli\CliReconcileStaleService;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies stale reconciliation stays bounded and revalidates attachment state.
 */
final class CliReconcileStaleServiceTest extends TestCase {

	/**
	 * Test stale reconciliation queues only stale attachments and skips others.
	 *
	 * @return void
	 */
	public function test_reconcile_queues_only_stale_attachments(): void {
		$bulk                    = new FakeBulkScannerRuntime();
		$bulk->pages[0]          = array( 10, 11, 12 );
		$store                   = new FakeAttachmentMetaStore();
		$store->meta[10][ LifecyclePolicy::META_STATUS ] = array( 'state' => AttachmentStatus::STATE_STALE );
		$store->meta[11][ LifecyclePolicy::META_STATUS ] = array( 'state' => AttachmentStatus::STATE_FAILED );
		$store->meta[12][ LifecyclePolicy::META_STATUS ] = array( 'state' => AttachmentStatus::STATE_STALE, 'excluded' => true );
		$clock                   = new FixedAttachmentClock( 1783612800 );
		$repository              = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$probe                   = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new SourceCollector(
			new FakeAttachmentSourceProvider(
				'/uploads/2026/07/hero.jpg',
				array(
					'file'   => '2026/07/hero.jpg',
					'width'  => 2400,
					'height' => 1600,
					'sizes'  => array(),
				),
				'/uploads'
			),
			$probe
		);
		$queue      = new FakeQueue();
		$controls   = new QueueControlStateStore(
			new FakeOptionStore(),
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$service    = new CliReconcileStaleService(
			$bulk,
			new AttachmentStatusReader( $store ),
			new AttachmentReconciliationService(
				$queue,
				$store,
				$repository,
				$collector,
				new AttachmentFingerprintBuilder(),
				$clock,
				$controls
			)
		);

		$result = $service->reconcile( new BulkScanFilters( BulkScanFilters::SCOPE_STALE_ONLY ) );

		self::assertFalse( $result->is_degraded() );
		self::assertSame( 1, $result->payload()['summary']['queued'] );
		self::assertSame( 2, $result->payload()['summary']['skipped'] );
		self::assertCount( 1, $queue->reconciliation_jobs );
		self::assertSame( 'cli_reconcile_stale', $queue->reconciliation_jobs[0]['job']->reason() );
	}
}
