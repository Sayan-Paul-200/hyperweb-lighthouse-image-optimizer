<?php
/**
 * Tests for the attachment action service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentActionService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentDetailsService;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies attachment-scoped queueing and exclusion state changes.
 */
final class AttachmentActionServiceTest extends TestCase {

	private const NOW = 1783612800;

	/**
	 * Test optimize queues enabled formats with the manual reason.
	 *
	 * @return void
	 */
	public function test_optimize_queues_enabled_formats_and_updates_status(): void {
		$runtime = $this->build_runtime();

		$result = $runtime['service']->optimize( 123, false );

		self::assertTrue( $result->is_successful() );
		self::assertCount( 2, $runtime['queue']->jobs );
		self::assertSame( 'manual_optimize', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertFalse( $runtime['queue']->jobs[0]['job']->force() );
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test force=true uses the manual re-optimize reason.
	 *
	 * @return void
	 */
	public function test_optimize_with_force_uses_manual_reoptimize_reason(): void {
		$runtime = $this->build_runtime(
			array(
				'settings' => array(
					'enabled_formats' => array( 'webp' ),
				),
			)
		);

		$result = $runtime['service']->optimize( 123, true );

		self::assertTrue( $result->is_successful() );
		self::assertCount( 1, $runtime['queue']->jobs );
		self::assertSame( 'manual_reoptimize', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertTrue( $runtime['queue']->jobs[0]['job']->force() );
	}

	/**
	 * Test excluded attachments reject manual optimize.
	 *
	 * @return void
	 */
	public function test_optimize_rejects_excluded_attachments(): void {
		$runtime = $this->build_runtime();
		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array(), self::NOW, null, true ) );

		$result = $runtime['service']->optimize( 123, false );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'attachment_excluded', $result->error_code() );
	}

	/**
	 * Test source exclusion meta is honored even when status is stale.
	 *
	 * @return void
	 */
	public function test_optimize_rejects_when_exclusion_meta_is_true(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_EXCLUDED ] = true;
		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_UNPROCESSED, array(), self::NOW, null, false ) );

		$result = $runtime['service']->optimize( 123, false );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'attachment_excluded', $result->error_code() );
	}

	/**
	 * Test already queued work still returns a successful result.
	 *
	 * @return void
	 */
	public function test_optimize_surfaces_already_queued_as_success(): void {
		$runtime = $this->build_runtime();
		$runtime['queue']->results = array(
			QueueStatus::already_queued( array( 'Already queued.' ) ),
			QueueStatus::already_queued( array( 'Already queued.' ) ),
		);

		$result = $runtime['service']->optimize( 123, false );

		self::assertTrue( $result->is_successful() );
		self::assertSame( QueueStatus::CODE_ALREADY_QUEUED, $result->queue()[0]['codes'][0] );
	}

	/**
	 * Test retry rejects non-retryable states.
	 *
	 * @return void
	 */
	public function test_retry_rejects_non_retryable_states(): void {
		$runtime = $this->build_runtime();
		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_OPTIMIZED, array( 'webp' ), self::NOW ) );

		$result = $runtime['service']->retry( 123 );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'attachment_not_retryable', $result->error_code() );
	}

	/**
	 * Test reconcile queues one reconciliation job.
	 *
	 * @return void
	 */
	public function test_reconcile_queues_one_reconciliation_job(): void {
		$runtime = $this->build_runtime();

		$result = $runtime['service']->reconcile( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertCount( 1, $runtime['queue']->reconciliation_jobs );
		self::assertSame( 'manual_reconcile', $runtime['queue']->reconciliation_jobs[0]['job']->reason() );
	}

	/**
	 * Test exclude sets the meta flag and preserves derivative metadata.
	 *
	 * @return void
	 */
	public function test_exclude_sets_meta_and_excluded_status_without_deleting_derivatives(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->valid_manifest();
		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_PARTIAL, array( 'webp' ), self::NOW, null, false ) );

		$result = $runtime['service']->exclude( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $runtime['store']->meta[123][ LifecyclePolicy::META_EXCLUDED ] );
		self::assertSame( AttachmentStatus::STATE_EXCLUDED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertTrue( $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['excluded'] );
		self::assertSame( $this->valid_manifest(), $runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] );
	}

	/**
	 * Test include restores conservative derived states.
	 *
	 * @return void
	 */
	public function test_include_restores_conservative_state_from_ready_formats(): void {
		$runtime = $this->build_runtime();

		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array( 'webp', 'avif' ), self::NOW, null, true ) );
		$runtime['service']->include( 123 );
		self::assertSame( AttachmentStatus::STATE_OPTIMIZED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertFalse( $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['excluded'] );

		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array( 'webp' ), self::NOW, null, true ) );
		$runtime['service']->include( 123 );
		self::assertSame( AttachmentStatus::STATE_PARTIAL, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );

		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_EXCLUDED, array(), self::NOW, null, true ) );
		$runtime['service']->include( 123 );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test exclusion-disabled settings reject include/exclude cleanly.
	 *
	 * @return void
	 */
	public function test_exclusion_disabled_rejects_include_and_exclude(): void {
		$runtime = $this->build_runtime(
			array(
				'settings' => array(
					'allow_attachment_exclusion' => false,
				),
			)
		);

		self::assertSame( 'attachment_exclusion_disabled', $runtime['service']->exclude( 123 )->error_code() );
		self::assertSame( 'attachment_exclusion_disabled', $runtime['service']->include( 123 )->error_code() );
	}

	/**
	 * Test paused queue control rejects manual optimize/retry/reconcile.
	 *
	 * @return void
	 */
	public function test_paused_queue_control_rejects_mutating_actions(): void {
		$runtime = $this->build_runtime(
			array(
				'paused' => true,
			)
		);
		$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_FAILED, array(), self::NOW ) );

		self::assertSame( 'queue_paused', $runtime['service']->optimize( 123, false )->error_code() );
		self::assertSame( 'queue_paused', $runtime['service']->retry( 123 )->error_code() );
		self::assertSame( 'queue_paused', $runtime['service']->reconcile( 123 )->error_code() );
	}

	/**
	 * Build the service runtime.
	 *
	 * @param array<string,mixed> $overrides Runtime overrides.
	 * @return array<string,mixed>
	 */
	private function build_runtime( array $overrides = array() ): array {
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( self::NOW );
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$queue      = new FakeQueue();
		$settings   = new FakeSettingsRepository(
			array_replace(
				array(
					'enabled_formats' => array( 'webp', 'avif' ),
				),
				$overrides['settings'] ?? array()
			)
		);
		$probe      = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );
		$probe->add_file( 'C:/site/wp-content/uploads/2026/07/hero.jpg', 5000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector  = new SourceCollector(
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
		);
		$details    = new AttachmentDetailsService( $repository );
		$controls   = new QueueControlStateStore(
			new FakeOptionStore(
				! empty( $overrides['paused'] )
					? array(
						'hwlio_queue_control_state' => array(
							'paused'             => true,
							'updated_at_gmt'     => '2026-07-12 00:00:00',
							'updated_by_user_id' => 7,
						),
					)
					: array()
			),
			'hwlio_queue_control_state',
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$queueing   = new AttachmentQueueService(
			$queue,
			$store,
			$repository,
			$collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$controls
		);
		$service    = new AttachmentActionService(
			$queue,
			$settings,
			$store,
			$repository,
			$collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$details,
			$queueing,
			$controls
		);

		return array(
			'service'    => $service,
			'queue'      => $queue,
			'settings'   => $settings,
			'store'      => $store,
			'repository' => $repository,
		);
	}

	/**
	 * Build a valid derivative manifest fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function valid_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => null,
			'updated_at'     => self::NOW,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'mime'   => 'image/jpeg',
						'width'  => 2400,
						'height' => 1600,
						'bytes'  => 5000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.webp',
							'mime'            => 'image/webp',
							'bytes'           => 3200,
							'quality'         => 82,
							'savings_bytes'   => 1800,
							'savings_percent' => 36,
							'status'          => 'ready',
							'generated_at'    => self::NOW,
						),
					),
				),
			),
		);
	}
}
