<?php
/**
 * Reconciliation worker tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionOutput;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationPath;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationJob;
use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationWorker;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentLockTokenGenerator;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeFilesystem;
use PHPUnit\Framework\TestCase;

/**
 * Verifies reconciliation worker behavior.
 */
final class ReconciliationWorkerTest extends TestCase {

	private const UPLOADS = '/uploads';

	/**
	 * Test hook registration on reconcile hook only.
	 *
	 * @return void
	 */
	public function test_registers_reconcile_hook_only(): void {
		$hooks  = new HookRegistrar();
		$worker = $this->build_runtime()['worker'];

		$worker->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( LifecyclePolicy::ACTION_RECONCILE_ATTACHMENT, $hooks->actions()[0]['hook'] );
		self::assertSame( 3, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test invalid payload fails safely.
	 *
	 * @return void
	 */
	public function test_invalid_payload_fails_safely(): void {
		$runtime = $this->build_runtime();

		$runtime['worker']->handle_reconciliation_job( 123, 'bad', 'metadata_update' );

		self::assertSame( AttachmentStatus::STATE_FAILED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( LogCode::WORKER_INVALID_JOB_PAYLOAD, $runtime['logger']->entries[0]['code'] );
	}

	/**
	 * Test stale queued fingerprint re-queues fresh reconciliation.
	 *
	 * @return void
	 */
	public function test_stale_queued_fingerprint_requeues_fresh_reconciliation(): void {
		$runtime = $this->build_runtime();

		$runtime['worker']->run_job( new ReconciliationJob( 123, str_repeat( 'b', 20 ), 'metadata_update' ) );

		self::assertCount( 1, $runtime['queue']->reconciliation_jobs );
		self::assertSame( 'source_changed', $runtime['queue']->reconciliation_jobs[0]['job']->reason() );
		self::assertSame( AttachmentStatus::STATE_STALE, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
	}

	/**
	 * Test reconciliation resets manifest and processes enabled formats with force.
	 *
	 * @return void
	 */
	public function test_begin_reconciliation_resets_manifest_and_processes_enabled_formats_with_force(): void {
		$runtime = $this->build_runtime();
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest(
			new AttachmentFingerprint( '2026/07/hero.jpg', 1000, 1783526400, str_repeat( 'b', 64 ) ),
			'webp'
		);
		$runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]      = array(
			'state'      => 'stale',
			'formats'    => array( 'webp' ),
			'updated_at' => 10,
			'error_code' => null,
			'excluded'   => false,
		);

		$invocations                    = 0;
		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime, &$invocations ) {
			$read = $runtime['repository']->read( 123 );
			self::assertTrue( $request->force() );
			if ( 0 === $invocations ) {
				self::assertSame( array(), $read->manifest()->sizes() );
				self::assertSame( $request->fingerprint()->signature(), $read->manifest()->fingerprint()->signature() );
			}

			$result = $this->success_result( $request->target_format() );
			$runtime['repository']->save_results(
				123,
				$request->fingerprint(),
				new ConversionResultCollection( array( $result ) ),
				AttachmentStatus::STATE_OPTIMIZED
			);
			++$invocations;

			return AttachmentProcessResult::success(
				new ConversionResultCollection( array( $result ) ),
				array( AttachmentProcessResult::CODE_PROCESSED ),
				array(),
				$request->target_format()
			);
		};

		$runtime['worker']->run_job( new ReconciliationJob( 123, $runtime['fingerprint']->signature(), 'metadata_update' ) );

		self::assertCount( 2, $runtime['processor']->requests );
		self::assertSame( AttachmentStatus::STATE_OPTIMIZED, $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( array( 'webp', 'avif' ), $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['formats'] );
	}

	/**
	 * Test obsolete derivatives are deleted after new manifest state exists.
	 *
	 * @return void
	 */
	public function test_obsolete_sidecars_are_removed_after_reconciliation(): void {
		$runtime = $this->build_runtime(
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'avif' ),
				)
			),
			new FakeFilesystem(
				array(
					self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				),
				array(
					self::UPLOADS,
					self::UPLOADS . '/2026',
					self::UPLOADS . '/2026/07',
				)
			)
		);
		$runtime['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest(
			new AttachmentFingerprint( '2026/07/hero.jpg', 1000, 1783526400, str_repeat( 'b', 64 ) ),
			'webp'
		);

		$runtime['processor']->callback = function ( AttachmentProcessRequest $request ) use ( $runtime ) {
			$result = $this->success_result( 'avif' );
			$runtime['repository']->save_results(
				123,
				$request->fingerprint(),
				new ConversionResultCollection( array( $result ) ),
				AttachmentStatus::STATE_OPTIMIZED
			);

			return AttachmentProcessResult::success(
				new ConversionResultCollection( array( $result ) ),
				array( AttachmentProcessResult::CODE_PROCESSED ),
				array(),
				$request->target_format()
			);
		};

		$runtime['worker']->run_job( new ReconciliationJob( 123, $runtime['fingerprint']->signature(), 'metadata_update' ) );

		self::assertContains( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', $runtime['filesystem']->deleted );
		self::assertSame( array( 'avif' ), $runtime['store']->meta[123][ LifecyclePolicy::META_STATUS ]['formats'] );
	}

	/**
	 * Build a test runtime.
	 *
	 * @param FakeSettingsRepository|null $settings Settings override.
	 * @param FakeFilesystem|null         $filesystem Filesystem override.
	 * @return array<string,mixed>
	 */
	private function build_runtime( ?FakeSettingsRepository $settings = null, ?FakeFilesystem $filesystem = null ): array {
		$store    = new FakeAttachmentMetaStore();
		$clock    = new FixedAttachmentClock( 1783526500 );
		$provider = new FakeAttachmentSourceProvider(
			self::UPLOADS . '/2026/07/hero.jpg',
			array(
				'file'   => '2026/07/hero.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(),
			),
			self::UPLOADS
		);
		$probe    = new FakeImageFileProbe( array( self::UPLOADS, self::UPLOADS . '/2026', self::UPLOADS . '/2026/07' ) );
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector     = new SourceCollector( $provider, $probe );
		$fingerprinter = new AttachmentFingerprintBuilder();
		$collection    = $collector->collect( 123 );
		$fingerprint   = $fingerprinter->build( $collection );
		$repository    = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$processor     = new FakeAttachmentProcessor();
		$queue         = new FakeQueue();
		$logger        = new FakeLogger();
		$settings      = $settings ?? new FakeSettingsRepository(
			array(
				'enabled_formats' => array( 'webp', 'avif' ),
			)
		);
		$filesystem    = $filesystem ?? new FakeFilesystem(
			array(),
			array(
				self::UPLOADS,
				self::UPLOADS . '/2026',
				self::UPLOADS . '/2026/07',
			)
		);

		$worker = new ReconciliationWorker(
			$queue,
			new AttachmentLockManager( $store, new FixedAttachmentLockTokenGenerator( array( 'lock-token' ) ), $clock ),
			$collector,
			$fingerprinter,
			$repository,
			$processor,
			$settings,
			new DerivativeFileCleaner( self::UPLOADS, $filesystem ),
			$logger,
			$clock
		);

		return array(
			'worker'      => $worker,
			'store'       => $store,
			'processor'   => $processor,
			'queue'       => $queue,
			'logger'      => $logger,
			'repository'  => $repository,
			'fingerprint' => $fingerprint,
			'filesystem'  => $filesystem,
		);
	}

	/**
	 * Build a stored manifest.
	 *
	 * @param AttachmentFingerprint $fingerprint Fingerprint.
	 * @param string                $format Ready format.
	 * @return array<string,mixed>
	 */
	private function stored_manifest( AttachmentFingerprint $fingerprint, string $format ): array {
		$mime = 'avif' === $format ? 'image/avif' : 'image/webp';

		return array(
			'schema_version' => 1,
			'fingerprint'    => $fingerprint->to_array(),
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
						$format => array(
							'file'         => '2026/07/hero.jpg.hwlio.' . $format,
							'mime'         => $mime,
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

	/**
	 * Build a successful conversion result.
	 *
	 * @param string $format Target format.
	 * @return ConversionResult
	 */
	private function success_result( string $format ): ConversionResult {
		$mime   = 'avif' === $format ? 'image/avif' : 'image/webp';
		$source = new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			self::UPLOADS . '/2026/07/hero.jpg',
			'image/jpeg',
			2400,
			1600,
			1000,
			1783526400
		);
		$output = new ConversionOutput(
			'2026/07/hero.jpg.hwlio.' . $format,
			$mime,
			2400,
			1600,
			300,
			82,
			1783526500
		);

		return ConversionResult::success(
			$source,
			new DestinationPath(
				$format,
				$mime,
				'2026/07/hero.jpg.hwlio.' . $format,
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.' . $format,
				'2026/07/hero.jpg.hwlio.' . $format . '.tmp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.' . $format . '.tmp'
			),
			$output,
			new ConversionSavings( 1000, 300, 5.0 )
		);
	}
}
