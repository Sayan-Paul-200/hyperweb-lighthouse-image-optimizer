<?php
/**
 * Tests for the attachment reconciliation queue service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies attachment reconciliation queueing stays centralized and safe.
 */
final class AttachmentReconciliationServiceTest extends TestCase {

	private const NOW = 1783612800;

	/**
	 * Test reconciliation queues one job and updates attachment state.
	 *
	 * @return void
	 */
	public function test_reconcile_queues_one_job_and_updates_status(): void {
		$runtime = $this->build_runtime();

		$result = $runtime['service']->reconcile( 123, 'cli_reconcile_stale' );

		self::assertTrue( $result->is_successful() );
		self::assertSame( 'queued', $result->code() );
		self::assertCount( 1, $runtime['queue']->reconciliation_jobs );
		self::assertSame( 'cli_reconcile_stale', $runtime['queue']->reconciliation_jobs[0]['job']->reason() );
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test paused queue control rejects reconciliation work.
	 *
	 * @return void
	 */
	public function test_reconcile_rejects_when_queue_is_paused(): void {
		$runtime = $this->build_runtime( true );

		$result = $runtime['service']->reconcile( 123 );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'queue_paused', $result->code() );
		self::assertCount( 0, $runtime['queue']->reconciliation_jobs );
	}

	/**
	 * Test already queued reconciliation returns a successful degraded-safe code.
	 *
	 * @return void
	 */
	public function test_reconcile_surfaces_already_queued_as_success(): void {
		$runtime                   = $this->build_runtime();
		$runtime['queue']->results = array(
			QueueStatus::already_queued( array( 'Already queued.' ) ),
		);

		$result = $runtime['service']->reconcile( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertSame( 'already_queued', $result->code() );
	}

	/**
	 * Build a real reconciliation service with fake dependencies.
	 *
	 * @param bool $paused Whether queue control starts paused.
	 * @return array<string,mixed>
	 */
	private function build_runtime( bool $paused = false ): array {
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( self::NOW );
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$queue      = new FakeQueue();
		$probe      = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );
		$probe->add_file( 'C:/site/wp-content/uploads/2026/07/hero.jpg', 5000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new LocalAttachmentSourceCollector(
			new SourceCollector(
				new FakeAttachmentSourceProvider(
					'C:/site/wp-content/uploads/2026/07/hero.jpg',
					array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'sizes'  => array(),
					),
					'C:/site/wp-content/uploads'
				),
				$probe
			)
		);
		$controls  = new QueueControlStateStore(
			new FakeOptionStore(
				$paused
					? array(
						LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE => array(
							'paused'             => true,
							'updated_at_gmt'     => '2026-07-12 00:00:00',
							'updated_by_user_id' => 7,
						),
					)
					: array()
			),
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$service   = new AttachmentReconciliationService(
			$queue,
			$store,
			$repository,
			$collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$controls
		);

		return array(
			'service' => $service,
			'queue'   => $queue,
			'store'   => $store,
		);
	}
}
