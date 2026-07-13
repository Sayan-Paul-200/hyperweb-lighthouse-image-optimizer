<?php
/**
 * Tests for the status summary service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatisticsCacheReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusRefreshService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusSummaryService;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogReadDatabase;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies status payload aggregation from queue, cache, and settings.
 */
final class StatusSummaryServiceTest extends TestCase {

	/**
	 * Test the service uses the cached statistics payload.
	 *
	 * @return void
	 */
	public function test_summary_uses_cached_statistics_and_settings(): void {
		$queue             = new FakeQueue();
		$queue->available  = false;
		$options           = new FakeOptionStore(
			array(
				LifecyclePolicy::OPTION_STATISTICS_CACHE => array(
					'schema_version'    => 1,
					'generated_at_gmt'  => '2026-07-12 00:00:00',
					'attachment_states' => array(
						'optimized' => 3,
					),
					'totals'            => array(
						'source_bytes' => 1200,
					),
					'formats'           => array(
						'webp' => array(
							'sources_ready' => 2,
						),
					),
				),
			)
		);
		$jobs              = new FakeAttachmentJobControl();
		$jobs->pending     = 4;
		$jobs->in_progress = 1;
		$service           = new StatusSummaryService(
			$queue,
			new StatisticsCacheReader( $options ),
			new FakeSettingsRepository(
				array(
					'automatic_optimization' => true,
					'enabled_formats'        => array( 'avif', 'webp' ),
					'delivery_enabled'       => true,
				)
			),
			new DashboardEnvironmentSummaryService(
				new EnvironmentInspector( new FakeEnvironmentProbe(), '7.4', '6.5' ),
				new FakeSettingsRepository(
					array(
						'automatic_optimization' => true,
						'enabled_formats'        => array( 'avif', 'webp' ),
						'delivery_enabled'       => true,
					)
				)
			),
			new RecentFailureLogReader(
				$this->log_database(
					array(
						array(
							'created_at_gmt' => '2026-07-12 00:10:00',
							'level'          => 'warning',
							'code'           => 'maintenance_statistics_reconcile_failed',
							'message'        => 'Statistics reconciliation could not be saved.',
							'attachment_id'  => 12,
						),
					)
				),
				'wp_hwlio_logs'
			),
			new StatusRefreshService( new FakeSingleActionScheduler() ),
			new QueueControlService(
				new QueueControlStateStore(
					$options,
					LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
					static function (): string {
						return '2026-07-12 00:00:00';
					}
				),
				$jobs
			)
		);

		$summary = $service->summary();

		self::assertFalse( $summary['queue']['available'] );
		self::assertTrue( $summary['settings']['automatic_optimization'] );
		self::assertSame( array( 'avif', 'webp' ), $summary['settings']['enabled_formats'] );
		self::assertSame( '2026-07-12 00:00:00', $summary['statistics']['generated_at_gmt'] );
		self::assertSame( 3, $summary['statistics']['attachment_states']['optimized'] );
		self::assertSame( 1200, $summary['statistics']['totals']['source_bytes'] );
		self::assertSame( 2, $summary['statistics']['formats']['webp']['sources_ready'] );
		self::assertTrue( $summary['settings']['delivery_enabled'] );
		self::assertArrayHasKey( 'environment', $summary );
		self::assertArrayHasKey( 'recentFailures', $summary );
		self::assertArrayHasKey( 'conflicts', $summary );
		self::assertArrayHasKey( 'refresh', $summary );
		self::assertSame( 4, $summary['queueControl']['pending'] );
		self::assertSame( 1, $summary['queueControl']['inProgress'] );
		self::assertSame( 'maintenance_statistics_reconcile_failed', $summary['recentFailures'][0]['code'] );
	}

	/**
	 * Test missing cache data returns a normalized empty payload.
	 *
	 * @return void
	 */
	public function test_summary_returns_normalized_empty_statistics_when_cache_is_missing(): void {
		$probe                               = new FakeEnvironmentProbe();
		$probe->mime_support['image/avif']   = false;
		$probe->action_scheduler_initialized = false;
		$scheduler                           = new FakeSingleActionScheduler();
		$scheduler->scheduled                = true;
		$jobs                                = new FakeAttachmentJobControl();
		$jobs->pending                       = 2;
		$jobs->in_progress                   = 0;
		$service                             = new StatusSummaryService(
			new FakeQueue(),
			new StatisticsCacheReader( new FakeOptionStore() ),
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'avif' ),
				)
			),
			new DashboardEnvironmentSummaryService(
				new EnvironmentInspector( $probe, '7.4', '6.5' ),
				new FakeSettingsRepository(
					array(
						'enabled_formats' => array( 'avif' ),
					)
				)
			),
			new RecentFailureLogReader( $this->log_database(), 'wp_hwlio_logs' ),
			new StatusRefreshService( $scheduler ),
			new QueueControlService(
				new QueueControlStateStore(
					new FakeOptionStore(),
					LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
					static function (): string {
						return '2026-07-12 00:00:00';
					}
				),
				$jobs
			)
		);

		$summary = $service->summary();

		self::assertSame( 1, $summary['statistics']['schema_version'] );
		self::assertSame( 0, $summary['statistics']['totals']['source_bytes'] );
		self::assertSame( 0, $summary['statistics']['attachment_states']['optimized'] );
		self::assertSame( 0, $summary['statistics']['formats']['webp']['sources_ready'] );
		self::assertSame( 0, $summary['statistics']['formats']['avif']['sources_ready'] );
		self::assertTrue( $summary['refresh']['pending'] );
		self::assertSame( 2, $summary['queueControl']['pending'] );
		self::assertSame( 'unsupported', $summary['environment']['formats']['avif']['status'] );
		self::assertCount( 2, $summary['conflicts'] );
	}

	/**
	 * Create a fake log database with the given rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Rows.
	 * @return FakeLogReadDatabase
	 */
	private function log_database( array $rows = array() ): FakeLogReadDatabase {
		$database       = new FakeLogReadDatabase();
		$database->rows = $rows;

		return $database;
	}
}
