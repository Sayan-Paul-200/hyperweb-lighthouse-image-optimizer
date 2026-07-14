<?php
/**
 * New-upload integration tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentExclusionRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Queue\NewUploadIntegration;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use PHPUnit\Framework\TestCase;

/**
 * Tests for new-upload queue integration.
 */
final class NewUploadIntegrationTest extends TestCase {

	/**
	 * Test hook registration on wp_generate_attachment_metadata only.
	 *
	 * @return void
	 */
	public function test_registers_generated_metadata_filter_only(): void {
		$hooks       = new HookRegistrar();
		$integration = $this->build_runtime()['integration'];

		$integration->register_hooks( $hooks );

		self::assertCount( 1, $hooks->filters() );
		self::assertSame( 'wp_generate_attachment_metadata', $hooks->filters()[0]['hook'] );
		self::assertSame( 3, $hooks->filters()[0]['accepted_args'] );
		self::assertSame( array(), $hooks->actions() );
	}

	/**
	 * Test create context queues all enabled formats and preserves metadata.
	 *
	 * @return void
	 */
	public function test_create_context_queues_all_enabled_formats(): void {
		$runtime  = $this->build_runtime();
		$metadata = array(
			'file'   => '2026/07/hero.jpg',
			'width'  => 2400,
			'height' => 1600,
			'sizes'  => array(),
		);

		$returned = $runtime['integration']->handle_generated_metadata( $metadata, 123, 'create' );

		self::assertSame( $metadata, $returned );
		self::assertCount( 2, $runtime['queue']->jobs );
		self::assertSame( 'webp', $runtime['queue']->jobs[0]['job']->format() );
		self::assertSame( 'avif', $runtime['queue']->jobs[1]['job']->format() );
		self::assertSame( 'new_upload', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame(
			$runtime['queue']->jobs[0]['job']->fingerprint(),
			$runtime['queue']->jobs[1]['job']->fingerprint()
		);
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( LogCode::NEW_UPLOAD_QUEUED, $runtime['logger']->entries[0]['code'] );
		self::assertCount( 1, $runtime['refreshes']->entries );
		self::assertSame( array( 'webp', 'avif' ), $runtime['refreshes']->entries[0]['payload']['queued_formats'] );
	}

	/**
	 * Test automatic optimization disabled leaves attachment unprocessed.
	 *
	 * @return void
	 */
	public function test_automatic_optimization_disabled_leaves_attachment_unprocessed(): void {
		$runtime = $this->build_runtime(
			array(),
			null,
			new FakeSettingsRepository(
				array(
					'automatic_optimization' => false,
					'enabled_formats'        => array( 'webp', 'avif' ),
				)
			)
		);
		$runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => 'optimized',
			'formats'    => array( 'webp' ),
			'updated_at' => 10,
			'error_code' => null,
			'excluded'   => true,
		);

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'create' );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( array( 'webp' ), $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['formats'] );
		self::assertFalse( $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['excluded'] );
	}

	/**
	 * Test excluded attachments are not automatically queued.
	 *
	 * @return void
	 */
	public function test_excluded_attachment_is_not_queued(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_EXCLUDED ] = true;

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'create' );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertSame( AttachmentStatus::STATE_EXCLUDED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertTrue( $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['excluded'] );
		self::assertSame( LogCode::NEW_UPLOAD_EXCLUDED, $runtime['logger']->entries[0]['code'] );
	}

	/**
	 * Test queue failures do not fail the upload and record the first failure code.
	 *
	 * @return void
	 */
	public function test_queue_failure_keeps_upload_non_blocking_and_records_failure_code(): void {
		$queue          = new FakeQueue();
		$queue->results = array(
			QueueStatus::queue_unavailable(),
			QueueStatus::enqueue_failed(),
		);

		$runtime  = $this->build_runtime( array(), $queue );
		$metadata = array( 'file' => '2026/07/hero.jpg' );

		$returned = $runtime['integration']->handle_generated_metadata( $metadata, 123, 'create' );

		self::assertSame( $metadata, $returned );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( QueueStatus::CODE_QUEUE_UNAVAILABLE, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['error_code'] );
		self::assertSame( LogCode::NEW_UPLOAD_QUEUE_FAILED, $runtime['logger']->entries[0]['code'] );
	}

	/**
	 * Test already-queued counts as a queued success.
	 *
	 * @return void
	 */
	public function test_already_queued_counts_as_success(): void {
		$queue          = new FakeQueue();
		$queue->results = array(
			QueueStatus::already_queued(),
			QueueStatus::enqueue_failed(),
		);

		$runtime = $this->build_runtime( array(), $queue );

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'create' );

		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( array( 'webp' ), $runtime['refreshes']->entries[0]['payload']['queued_formats'] );
		self::assertSame( array( 'avif' ), $runtime['refreshes']->entries[0]['payload']['failed_formats'] );
	}

	/**
	 * Test missing valid sources leaves attachment unprocessed.
	 *
	 * @return void
	 */
	public function test_missing_valid_sources_leaves_attachment_unprocessed(): void {
		$runtime = $this->build_runtime(
			array(
				'attached_file' => null,
				'metadata'      => null,
				'files'         => array(),
			)
		);

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'create' );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( LogCode::NEW_UPLOAD_IGNORED, $runtime['logger']->entries[0]['code'] );
	}

	/**
	 * Test update context does not queue and does not overwrite existing state.
	 *
	 * @return void
	 */
	public function test_update_context_does_not_queue_or_overwrite_existing_state(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest( $runtime['fingerprint']->metadata_hash() );
		$runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]      = array(
			'state'      => 'optimized',
			'formats'    => array( 'webp', 'avif' ),
			'updated_at' => 50,
			'error_code' => null,
			'excluded'   => false,
		);

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'update' );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertCount( 0, $runtime['queue']->reconciliation_jobs );
		self::assertSame( AttachmentStatus::STATE_OPTIMIZED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( LogCode::NEW_UPLOAD_IGNORED, $runtime['logger']->entries[0]['code'] );
		self::assertCount( 1, $runtime['refreshes']->entries );
		self::assertSame( 'update', $runtime['refreshes']->entries[0]['context'] );
	}

	/**
	 * Test stale update queues reconciliation and marks stale state.
	 *
	 * @return void
	 */
	public function test_update_context_queues_reconciliation_for_stale_derivatives(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest( str_repeat( 'b', 64 ) );
		$runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]      = array(
			'state'      => 'optimized',
			'formats'    => array( 'webp' ),
			'updated_at' => 50,
			'error_code' => null,
			'excluded'   => false,
		);

		$returned = $runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'update' );

		self::assertSame( array( 'file' => '2026/07/hero.jpg' ), $returned );
		self::assertCount( 1, $runtime['queue']->reconciliation_jobs );
		self::assertSame( 'metadata_update', $runtime['queue']->reconciliation_jobs[0]['job']->reason() );
		self::assertSame( AttachmentStatus::STATE_STALE, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( LogCode::RECONCILE_STALE_DETECTED, $runtime['logger']->entries[0]['code'] );
		self::assertSame( LogCode::RECONCILE_QUEUED, $runtime['logger']->entries[1]['code'] );
	}

	/**
	 * Test stale update does not queue when automation is disabled.
	 *
	 * @return void
	 */
	public function test_stale_update_does_not_queue_when_automation_is_disabled(): void {
		$runtime = $this->build_runtime(
			array(),
			null,
			new FakeSettingsRepository(
				array(
					'automatic_optimization' => false,
					'enabled_formats'        => array( 'webp', 'avif' ),
				)
			)
		);
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest( str_repeat( 'b', 64 ) );
		$runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]      = array(
			'state'      => 'optimized',
			'formats'    => array( 'webp' ),
			'updated_at' => 50,
			'error_code' => null,
			'excluded'   => false,
		);

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'update' );

		self::assertCount( 0, $runtime['queue']->reconciliation_jobs );
		self::assertSame( AttachmentStatus::STATE_STALE, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test global pause leaves uploads usable without queueing.
	 *
	 * @return void
	 */
	public function test_paused_queue_control_skips_new_upload_queueing(): void {
		$controls = new QueueControlStateStore(
			new FakeOptionStore(
				array(
					'hwlio_queue_control_state' => array(
						'paused'             => true,
						'updated_at_gmt'     => '2026-07-12 00:00:00',
						'updated_by_user_id' => 7,
					),
				)
			),
			'hwlio_queue_control_state',
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$runtime  = $this->build_runtime( array(), null, null, $controls );

		$runtime['integration']->handle_generated_metadata( array( 'file' => '2026/07/hero.jpg' ), 123, 'create' );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Build a runtime harness.
	 *
	 * @param array<string,mixed>         $config Runtime config.
	 * @param FakeQueue|null              $queue Queue override.
	 * @param FakeSettingsRepository|null $settings Settings override.
	 * @param QueueControlStateStore|null $controls Queue control state store.
	 * @return array<string,mixed>
	 */
	private function build_runtime(
		array $config = array(),
		?FakeQueue $queue = null,
		?FakeSettingsRepository $settings = null,
		?QueueControlStateStore $controls = null
	): array {
		$store         = new FakeAttachmentMetaStore();
		$clock         = new FixedAttachmentClock( 1783526500 );
		$provider      = new FakeAttachmentSourceProvider(
			$config['attached_file'] ?? '/uploads/2026/07/hero.jpg',
			$config['metadata'] ?? array(
				'file'   => '2026/07/hero.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(),
			),
			'/uploads'
		);
		$probe         = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$files         = $config['files'] ?? array(
			array( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 ),
		);
		$refresh_state = (object) array(
			'entries' => array(),
		);

		foreach ( $files as $file ) {
			$probe->add_file( $file[0], $file[1], $file[2], $file[3], $file[4], $file[5] );
		}

		$queue       = $queue ?? new FakeQueue();
		$settings    = $settings ?? new FakeSettingsRepository(
			array(
				'automatic_optimization' => true,
				'enabled_formats'        => array( 'webp', 'avif' ),
			)
		);
		$repository  = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$logger      = new FakeLogger();
		$integration = new NewUploadIntegration(
			$queue,
			$settings,
			new AttachmentExclusionRepository( $store ),
			new LocalAttachmentSourceCollector( new SourceCollector( $provider, $probe ) ),
			new AttachmentFingerprintBuilder(),
			$repository,
			$logger,
			$clock,
			static function ( int $attachment_id ): bool {
				return 123 === $attachment_id;
			},
			static function ( int $attachment_id, string $context, array $status, array $payload ) use ( $refresh_state ): void {
				$refresh_state->entries[] = array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
					'status'        => $status,
					'payload'       => $payload,
				);
			},
			$controls
		);

		$fingerprint = ( new AttachmentFingerprintBuilder() )->build(
			( new LocalAttachmentSourceCollector( new SourceCollector( $provider, $probe ) ) )->collect( 123 )->collection()
		);

		return array(
			'integration' => $integration,
			'queue'       => $queue,
			'settings'    => $settings,
			'store'       => $store,
			'logger'      => $logger,
			'refreshes'   => $refresh_state,
			'repository'  => $repository,
			'fingerprint' => $fingerprint,
		);
	}

	/**
	 * Build a stored manifest with one ready WebP derivative.
	 *
	 * @param string $metadata_hash Metadata hash.
	 * @return array<string,mixed>
	 */
	private function stored_manifest( string $metadata_hash ): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/hero.jpg',
				'file_size'     => 1000,
				'modified_time' => 1783526400,
				'metadata_hash' => $metadata_hash,
				'signature'     => substr( hash( 'sha256', '2026/07/hero.jpg|1000|1783526400|' . $metadata_hash ), 0, 20 ),
			),
			'updated_at'     => 1783526401,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 300,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526401,
						),
					),
				),
			),
		);
	}
}
