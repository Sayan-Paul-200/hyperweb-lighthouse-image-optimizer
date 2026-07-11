<?php
/**
 * Optimization worker tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationJob;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationRetryPolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationWorker;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentLockTokenGenerator;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OptimizationWorker.
 */
final class OptimizationWorkerTest extends TestCase {

	/**
	 * Test hook registration on optimize hook only.
	 *
	 * @return void
	 */
	public function test_registers_optimize_hook_only(): void {
		$hooks  = new HookRegistrar();
		$worker = $this->build_worker();

		$worker->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT, $hooks->actions()[0]['hook'] );
		self::assertSame( 6, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test callback reconstruction from positional args.
	 *
	 * @return void
	 */
	public function test_reconstructs_callback_payload_from_positional_args(): void {
		$runtime = $this->build_runtime();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) {
			unset( $request );

			return AttachmentProcessResult::success( new ConversionResultCollection() );
		};

		$runtime['worker']->handle_optimization_job( 123, 'webp', 2, true, 'manual', $runtime['fingerprint'] );

		self::assertCount( 1, $runtime['processor']->requests );
		self::assertSame( 123, $runtime['processor']->requests[0]->attachment_id() );
		self::assertSame( 'webp', $runtime['processor']->requests[0]->target_format() );
		self::assertSame( 2, $runtime['processor']->requests[0]->cursor() );
		self::assertTrue( $runtime['processor']->requests[0]->force() );
	}

	/**
	 * Test invalid payload fails safely.
	 *
	 * @return void
	 */
	public function test_invalid_payload_fails_safely(): void {
		$runtime = $this->build_runtime();

		$runtime['worker']->handle_optimization_job( 123, 'gif', 0, false, 'manual', 'bad' );

		self::assertSame( AttachmentStatus::STATE_FAILED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( ConversionResultCode::INVALID_JOB_PAYLOAD, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['error_code'] );
		self::assertSame( LogCode::WORKER_INVALID_JOB_PAYLOAD, $runtime['logger']->entries[0]['code'] );
	}

	/**
	 * Test stale fingerprint triggers fresh requeue.
	 *
	 * @return void
	 */
	public function test_stale_fingerprint_triggers_fresh_requeue(): void {
		$runtime = $this->build_runtime();
		$job     = new OptimizationJob( 123, 'webp', 4, false, 'manual', str_repeat( 'b', 20 ) );

		$runtime['worker']->run_job( $job );

		self::assertCount( 1, $runtime['queue']->jobs );
		self::assertSame( 0, $runtime['queue']->jobs[0]['job']->cursor() );
		self::assertSame( 'source_changed', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame( 0, count( $runtime['processor']->requests ) );
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test invalid current fingerprint delegates to processor.
	 *
	 * @return void
	 */
	public function test_invalid_current_fingerprint_delegates_to_processor(): void {
		$runtime = $this->build_runtime(
			array(
				'attached_file' => null,
				'metadata'      => array(
					'file'   => '2026/07/hero.jpg',
					'width'  => 2400,
					'height' => 1600,
					'sizes'  => array(
						'thumbnail' => array(
							'file'   => 'hero-150x150.jpg',
							'width'  => 150,
							'height' => 150,
						),
					),
				),
				'files'         => array(
					array( '/uploads/2026/07/hero-150x150.jpg', 100, 1783526400, 'image/jpeg', 150, 150 ),
				),
			)
		);

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) {
			self::assertNull( $request->fingerprint() );

			return AttachmentProcessResult::failure(
				AttachmentProcessResult::CODE_FINGERPRINT_FAILED,
				'Fingerprint failed.',
				'webp'
			);
		};

		$runtime['worker']->run_job( new OptimizationJob( 123, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) ) );

		self::assertCount( 1, $runtime['processor']->requests );
	}

	/**
	 * Test processing status is written before processor execution.
	 *
	 * @return void
	 */
	public function test_processing_status_is_written_before_processor_execution(): void {
		$runtime = $this->build_runtime();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime ) {
			unset( $request );

			self::assertSame( AttachmentStatus::STATE_PROCESSING, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );

			return AttachmentProcessResult::success( new ConversionResultCollection() );
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertSame( AttachmentStatus::STATE_PROCESSING, $runtime['store']->updates[0]['value']['state'] );
	}

	/**
	 * Test incomplete processor result enqueues continuation.
	 *
	 * @return void
	 */
	public function test_incomplete_processor_result_enqueues_continuation(): void {
		$runtime = $this->build_runtime();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime ) {
			unset( $request );
			$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_PARTIAL, array(), 1783526500, null, false ) );

			return AttachmentProcessResult::success( new ConversionResultCollection(), array(), array(), 'webp', 0, 3, false );
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertCount( 1, $runtime['queue']->jobs );
		self::assertSame( 3, $runtime['queue']->jobs[0]['job']->cursor() );
		self::assertSame( 'continuation', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame( AttachmentStatus::STATE_PROCESSING, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test continuation enqueue failure leaves partial status intact.
	 *
	 * @return void
	 */
	public function test_continuation_enqueue_failure_leaves_partial_status_intact(): void {
		$runtime = $this->build_runtime( array(), new FakeQueue( QueueStatus::enqueue_failed() ) );

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime ) {
			unset( $request );
			$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_PARTIAL, array(), 1783526500, null, false ) );

			return AttachmentProcessResult::success( new ConversionResultCollection(), array(), array(), 'webp', 0, 2, false );
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertSame( AttachmentStatus::STATE_PARTIAL, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test retryable transient failure enqueues retry.
	 *
	 * @return void
	 */
	public function test_retryable_transient_failure_enqueues_retry(): void {
		$runtime = $this->build_runtime();
		$source  = $this->source();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime, $source ) {
			unset( $request );
			$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_FAILED, array(), 1783526500, ConversionResultCode::TEMPORARY_WRITE_FAILED, false ) );

			return AttachmentProcessResult::success(
				new ConversionResultCollection(
					array(
						ConversionResult::failed(
							$source,
							'webp',
							'image/webp',
							ConversionResultCode::TEMPORARY_WRITE_FAILED,
							'Temporary write failed.'
						),
					)
				)
			);
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertCount( 1, $runtime['queue']->jobs );
		self::assertSame( 'retry_1', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame( 60, $runtime['queue']->jobs[0]['delay_seconds'] );
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test max retries stop further retry queueing.
	 *
	 * @return void
	 */
	public function test_max_retries_stop_further_retry_queueing(): void {
		$runtime = $this->build_runtime(
			array(),
			null,
			new FakeSettingsRepository(
				array(
					'max_retries'        => 3,
					'worker_time_budget' => 20,
				)
			)
		);
		$source  = $this->source();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime, $source ) {
			unset( $request );
			$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_FAILED, array(), 1783526500, ConversionResultCode::TEMPORARY_WRITE_FAILED, false ) );

			return AttachmentProcessResult::success(
				new ConversionResultCollection(
					array(
						ConversionResult::failed(
							$source,
							'webp',
							'image/webp',
							ConversionResultCode::TEMPORARY_WRITE_FAILED,
							'Temporary write failed.'
						),
					)
				)
			);
		};

		$runtime['worker']->run_job( new OptimizationJob( 123, 'webp', 0, false, 'retry_3', $runtime['fingerprint'] ) );

		self::assertCount( 0, $runtime['queue']->jobs );
		self::assertSame( AttachmentStatus::STATE_FAILED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test permanent failures do not retry.
	 *
	 * @return void
	 */
	public function test_permanent_failure_does_not_retry(): void {
		$runtime = $this->build_runtime();
		$source  = $this->source();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime, $source ) {
			unset( $request );
			$runtime['repository']->save_status( 123, new AttachmentStatus( AttachmentStatus::STATE_FAILED, array(), 1783526500, ConversionResultCode::SOURCE_CORRUPT, false ) );

			return AttachmentProcessResult::success(
				new ConversionResultCollection(
					array(
						ConversionResult::failed(
							$source,
							'webp',
							'image/webp',
							ConversionResultCode::SOURCE_CORRUPT,
							'Source is corrupt.'
						),
					)
				)
			);
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertCount( 0, $runtime['queue']->jobs );
	}

	/**
	 * Test lock collision is retried through queue policy.
	 *
	 * @return void
	 */
	public function test_lock_collision_is_retried(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_LOCK ] = array(
			'token'      => 'other-token',
			'created_at' => 1783526400,
			'expires_at' => 1783527100,
		);

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertCount( 1, $runtime['queue']->jobs );
		self::assertSame( 'retry_1', $runtime['queue']->jobs[0]['job']->reason() );
		self::assertSame( AttachmentStatus::STATE_QUEUED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test logger records sanitized outcome context.
	 *
	 * @return void
	 */
	public function test_logger_records_sanitized_outcome_context(): void {
		$runtime = $this->build_runtime();

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) {
			unset( $request );
			return AttachmentProcessResult::success( new ConversionResultCollection() );
		};

		$runtime['worker']->run_job( $this->job( $runtime['fingerprint'] ) );

		self::assertNotEmpty( $runtime['logger']->entries );
		self::assertSame( LogCode::WORKER_RESULT_COMPLETED, $runtime['logger']->entries[0]['code'] );
		self::assertSame( 123, $runtime['logger']->entries[0]['context']['attachment_id'] );
	}

	/**
	 * Build a canonical optimization job.
	 *
	 * @param string $fingerprint Fingerprint.
	 * @return OptimizationJob
	 */
	private function job( string $fingerprint ): OptimizationJob {
		return new OptimizationJob( 123, 'webp', 0, false, 'manual', $fingerprint );
	}

	/**
	 * Build a canonical source image.
	 *
	 * @return SourceImage
	 */
	private function source(): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			'/uploads/2026/07/hero.jpg',
			'image/jpeg',
			2400,
			1600,
			1000,
			1783526400
		);
	}

	/**
	 * Build worker runtime with real collection/fingerprint services and fake orchestration seams.
	 *
	 * @param array<string,mixed>         $config Runtime config.
	 * @param FakeQueue|null              $queue Queue override.
	 * @param FakeSettingsRepository|null $settings Settings override.
	 * @return array<string,mixed>
	 */
	private function build_runtime( array $config = array(), ?FakeQueue $queue = null, ?FakeSettingsRepository $settings = null ): array {
		$store    = new FakeAttachmentMetaStore();
		$clock    = new FixedAttachmentClock( 1783526500 );
		$provider = new FakeAttachmentSourceProvider(
			$config['attached_file'] ?? '/uploads/2026/07/hero.jpg',
			$config['metadata'] ?? array(
				'file'   => '2026/07/hero.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(),
			),
			'/uploads'
		);
		$probe    = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );

		foreach ( $config['files'] ?? array(
			array( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 ),
		) as $file ) {
			$probe->add_file( $file[0], $file[1], $file[2], $file[3], $file[4], $file[5] );
		}

		$collector     = new SourceCollector( $provider, $probe );
		$fingerprinter = new AttachmentFingerprintBuilder();
		$collection    = $collector->collect( 123 );
		$fingerprint   = $fingerprinter->build( $collection );
		$repository    = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$processor     = new FakeAttachmentProcessor();
		$logger        = new FakeLogger();
		$queue         = $queue ?? new FakeQueue();
		$settings      = $settings ?? new FakeSettingsRepository(
			array(
				'max_retries'        => 3,
				'worker_time_budget' => 20,
			)
		);

		$worker = new OptimizationWorker(
			$queue,
			new AttachmentLockManager( $store, new FixedAttachmentLockTokenGenerator( array( 'lock-token' ) ), $clock ),
			$collector,
			$fingerprinter,
			$repository,
			$processor,
			$settings,
			$logger,
			new OptimizationRetryPolicy(),
			$clock
		);

		return array(
			'worker'      => $worker,
			'store'       => $store,
			'processor'   => $processor,
			'logger'      => $logger,
			'queue'       => $queue,
			'repository'  => $repository,
			'fingerprint' => $fingerprint instanceof \HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint ? $fingerprint->signature() : '',
		);
	}

	/**
	 * Build a minimal worker for hook tests.
	 *
	 * @return OptimizationWorker
	 */
	private function build_worker(): OptimizationWorker {
		$runtime = $this->build_runtime();

		return $runtime['worker'];
	}
}
