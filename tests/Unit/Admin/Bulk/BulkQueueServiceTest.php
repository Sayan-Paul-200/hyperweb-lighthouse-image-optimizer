<?php
/**
 * Tests for the bulk queue service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueProgress;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies persisted dry-run candidates can be queued in bounded chunks.
 */
final class BulkQueueServiceTest extends TestCase {

	/**
	 * Test queue continuation walks persisted candidate chunks page by page.
	 *
	 * @return void
	 */
	public function test_queue_continues_across_candidate_pages(): void {
		$runtime                   = $this->build_runtime();
		$runtime['bulk']->pages[0] = range( 1, 55 );

		$session = $runtime['scans']->start_scan( new BulkScanFilters(), 7 );
		$first   = $runtime['queues']->queue( $session->token(), 7 );
		$second  = $runtime['queues']->queue( $session->token(), 7 );

		self::assertTrue( $session->progress()->complete() );
		self::assertSame( BulkQueueProgress::STATUS_RUNNING, $first->queue_progress()->status() );
		self::assertSame( 2, $first->queue_progress()->next_page() );
		self::assertSame( 50, $first->queue_progress()->processed_candidates() );
		self::assertSame( 50, $first->queue_summary()->to_array()['queued'] );
		self::assertSame( BulkQueueProgress::STATUS_COMPLETE, $second->queue_progress()->status() );
		self::assertTrue( $second->queue_progress()->complete() );
		self::assertSame( 55, $second->queue_summary()->to_array()['queued'] );
		self::assertCount( 55, $runtime['queue']->jobs );
		self::assertSame( 'webp', $runtime['queue']->jobs[0]['job']->format() );
	}

	/**
	 * Test retry mode revalidates current state and respects stored target formats.
	 *
	 * @return void
	 */
	public function test_retry_mode_uses_target_format_and_skips_non_retryable_candidates(): void {
		$runtime                   = $this->build_runtime(
			array(
				'enabled_formats' => array( 'webp', 'avif' ),
			)
		);
		$runtime['bulk']->pages[0] = array( 20, 21, 22 );
		$runtime['store']->meta[20][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_PARTIAL,
			'formats' => array( 'webp' ),
		);
		$runtime['store']->meta[21][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_FAILED,
		);
		$runtime['store']->meta[22][ LifecyclePolicy::META_STATUS ] = array(
			'state' => AttachmentStatus::STATE_STALE,
		);

		$session = $runtime['scans']->start_scan(
			new BulkScanFilters( BulkScanFilters::SCOPE_ALL_ELIGIBLE, BulkScanFilters::TARGET_AVIF ),
			7
		);

		$runtime['store']->meta[20][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => AttachmentStatus::STATE_OPTIMIZED,
			'formats' => array( 'webp', 'avif' ),
		);

		$queued = $runtime['queues']->retry( $session->token(), 7 );

		self::assertSame( BulkQueueProgress::STATUS_COMPLETE, $queued->queue_progress()->status() );
		self::assertSame(
			array(
				'queued'            => 2,
				'already_queued'    => 0,
				'already_optimized' => 0,
				'skipped'           => 1,
				'failed_to_queue'   => 0,
			),
			$queued->queue_summary()->to_array()
		);
		self::assertCount( 2, $runtime['queue']->jobs );
		self::assertSame( 'avif', $runtime['queue']->jobs[0]['job']->format() );
		self::assertSame( 'bulk_retry', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame( 'avif', $runtime['queue']->jobs[1]['job']->format() );
	}

	/**
	 * Test paused queue state marks the session paused without queueing work.
	 *
	 * @return void
	 */
	public function test_queue_paused_state_stops_before_enqueuing_candidates(): void {
		$runtime                   = $this->build_runtime(
			array(
				'enabled_formats' => array( 'webp' ),
			),
			true
		);
		$runtime['bulk']->pages[0] = array( 30, 31 );

		$session = $runtime['scans']->start_scan( new BulkScanFilters(), 7 );
		$queued  = $runtime['queues']->queue( $session->token(), 7 );

		self::assertSame( BulkQueueProgress::MODE_QUEUE, $queued->queue_progress()->mode() );
		self::assertSame( BulkQueueProgress::STATUS_PAUSED, $queued->queue_progress()->status() );
		self::assertSame( 1, $queued->queue_progress()->next_page() );
		self::assertSame(
			array(
				'queued'            => 0,
				'already_queued'    => 0,
				'already_optimized' => 0,
				'skipped'           => 0,
				'failed_to_queue'   => 0,
			),
			$queued->queue_summary()->to_array()
		);
		self::assertCount( 0, $runtime['queue']->jobs );
	}

	/**
	 * Build a bulk queue runtime fixture.
	 *
	 * @param array<string,mixed> $settings Settings override.
	 * @param bool                $paused Whether the queue starts paused.
	 * @return array<string,mixed>
	 */
	private function build_runtime( array $settings = array(), bool $paused = false ): array {
		$bulk       = new FakeBulkScannerRuntime();
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( 1783612800 );
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$sessions   = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$statuses   = new AttachmentStatusReader( $store );
		$settings   = new FakeSettingsRepository(
			array_replace(
				array(
					'enabled_formats' => array( 'webp' ),
				),
				$settings
			)
		);
		$scans      = new BulkScanService(
			$bulk,
			$sessions,
			$statuses,
			$settings,
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				return 'feedfacefeedfacefeedfacefeedface';
			}
		);
		$probe      = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new LocalAttachmentSourceCollector(
			new SourceCollector(
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
			)
		);
		$options   = new FakeOptionStore(
			$paused
				? array(
					LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE => array(
						'paused'             => true,
						'updated_at_gmt'     => '2026-07-12 00:00:00',
						'updated_by_user_id' => 7,
					),
				)
				: array()
		);
		$controls  = new QueueControlStateStore(
			$options,
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$queue     = new FakeQueue();
		$queueing  = new AttachmentQueueService(
			$queue,
			$store,
			$repository,
			$collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$controls
		);

		return array(
			'bulk'     => $bulk,
			'store'    => $store,
			'queue'    => $queue,
			'scans'    => $scans,
			'queues'   => new BulkQueueService( $sessions, $scans, $statuses, $queueing, $settings, $controls ),
			'sessions' => $sessions,
		);
	}
}
