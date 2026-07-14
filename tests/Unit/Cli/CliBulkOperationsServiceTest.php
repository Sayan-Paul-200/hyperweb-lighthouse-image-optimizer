<?php
/**
 * Tests for the CLI bulk operations runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Cli\CliBulkOperationsService;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
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
 * Verifies CLI bulk scan and queue flows stream bounded progress safely.
 */
final class CliBulkOperationsServiceTest extends TestCase {

	/**
	 * Test scan runs to completion and deletes internal sessions.
	 *
	 * @return void
	 */
	public function test_scan_runs_to_completion_and_deletes_internal_sessions(): void {
		$runtime                   = $this->build_runtime();
		$runtime['bulk']->pages[0] = array( 1, 2, 3 );
		$messages                  = array();

		$result = $runtime['service']->scan(
			new BulkScanFilters(),
			static function ( string $message ) use ( &$messages ): void {
				$messages[] = $message;
			}
		);

		self::assertFalse( $result->is_degraded() );
		self::assertSame( 3, $result->payload()['summary']['eligible'] );
		self::assertSame( array(), $runtime['transients']->values );
		self::assertCount( 1, $messages );
	}

	/**
	 * Test queue scans first, then queues candidates page by page.
	 *
	 * @return void
	 */
	public function test_queue_scans_then_queues_candidates(): void {
		$runtime                   = $this->build_runtime();
		$runtime['bulk']->pages[0] = range( 1, 55 );
		$messages                  = array();

		$result = $runtime['service']->queue(
			new BulkScanFilters(),
			static function ( string $message ) use ( &$messages ): void {
				$messages[] = $message;
			}
		);

		self::assertFalse( $result->is_degraded() );
		self::assertSame( 55, $result->payload()['queue_summary']['queued'] );
		self::assertCount( 55, $runtime['queue']->jobs );
		self::assertGreaterThanOrEqual( 2, count( $messages ) );
	}

	/**
	 * Build a real CLI bulk service runtime.
	 *
	 * @return array<string,mixed>
	 */
	private function build_runtime(): array {
		$bulk       = new FakeBulkScannerRuntime();
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( 1783612800 );
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$transients = new FakeTransientStore();
		$sessions   = new WordPressTransientBulkScanSessionStore( $transients );
		$statuses   = new AttachmentStatusReader( $store );
		$settings   = new FakeSettingsRepository(
			array(
				'enabled_formats' => array( 'webp' ),
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
		$controls  = new QueueControlStateStore(
			new FakeOptionStore(),
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
			'bulk'       => $bulk,
			'queue'      => $queue,
			'transients' => $transients,
			'sessions'   => $sessions,
			'service'    => new CliBulkOperationsService(
				$scans,
				new BulkQueueService( $sessions, $scans, $statuses, $queueing, $settings, $controls ),
				$sessions
			),
		);
	}
}
