<?php
/**
 * Tests for attachment cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeCacheInvalidationDispatcher;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeFilesystem;
use PHPUnit\Framework\TestCase;

/**
 * Verifies attachment cleanup deletes only safe plugin-owned state.
 */
final class AttachmentCleanupTest extends TestCase {

	private const ATTACHMENT_ID = 15;
	private const UPLOADS       = 'C:/site/wp-content/uploads';

	/**
	 * Test the provider registers only the delete hook.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_delete_attachment_only(): void {
		$cleanup = $this->cleanup_service(
			new FakeAttachmentMetaStore(),
			new FakeFilesystem( array(), array( self::UPLOADS ) ),
			new FakeAttachmentJobCleaner()
		);
		$hooks   = new HookRegistrar();

		$cleanup->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'delete_attachment', $hooks->actions()[0]['hook'] );
		self::assertSame( 10, $hooks->actions()[0]['priority'] );
		self::assertSame( 1, $hooks->actions()[0]['accepted_args'] );
	}

	/**
	 * Test cleanup deletes only manifest-listed sidecars and removes plugin meta.
	 *
	 * @return void
	 */
	public function test_cleanup_attachment_deletes_manifest_sidecars_and_meta(): void {
		$store = $this->meta_store_with_manifest( $this->manifest() );
		$files = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);
		$jobs  = new FakeAttachmentJobCleaner(
			AttachmentCleanupResult::success(
				array( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_CANCELLED ),
				array( 'Cancelled 1 pending attachment job(s).' ),
				0,
				1
			)
		);

		$cleanup = $this->cleanup_service( $store, $files, $jobs );
		$result  = $cleanup->cleanup_attachment( self::ATTACHMENT_ID );

		self::assertTrue( $result->is_successful() );
		self::assertFalse( $result->has_warnings() );
		self::assertTrue( $result->has_code( AttachmentCleanupResult::CODE_COMPLETED ) );
		self::assertSame( 2, $result->deleted_files() );
		self::assertSame( 1, $result->cancelled_actions() );
		self::assertSame( 4, $result->deleted_meta() );
		self::assertSame(
			array(
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			$files->deleted
		);
		self::assertArrayHasKey( self::UPLOADS . '/2026/07/hero.jpg', $files->files );
		self::assertSame( array(), $store->meta[ self::ATTACHMENT_ID ] );
		self::assertSame( array( self::ATTACHMENT_ID ), $jobs->attachment_ids );
	}

	/**
	 * Test cleanup dispatches cache invalidation for deleted sidecars.
	 *
	 * @return void
	 */
	public function test_cleanup_dispatches_cache_invalidation_for_deleted_sidecars(): void {
		$store      = $this->meta_store_with_manifest( $this->manifest() );
		$files      = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);
		$dispatcher = new FakeCacheInvalidationDispatcher();

		$this->cleanup_service( $store, $files, new FakeAttachmentJobCleaner(), $dispatcher )->cleanup_attachment( self::ATTACHMENT_ID );

		self::assertCount( 1, $dispatcher->requests );
		self::assertSame( 'derivatives_deleted', $dispatcher->requests[0]['event'] );
		self::assertSame( self::ATTACHMENT_ID, $dispatcher->requests[0]['attachment_id'] );
		self::assertSame(
			array(
				'2026/07/hero.jpg.hwlio.webp',
				'2026/07/hero.jpg.hwlio.avif',
			),
			$dispatcher->requests[0]['relative_paths']
		);
		self::assertSame( array( 'webp', 'avif' ), $dispatcher->requests[0]['formats'] );
	}

	/**
	 * Test tampered paths are rejected and meta cleanup still runs.
	 *
	 * @return void
	 */
	public function test_cleanup_attachment_rejects_tampered_paths_and_still_removes_meta(): void {
		$manifest = $this->manifest();
		$manifest['sizes']['full']['formats']['webp']['file'] = '../outside.webp';
		$store = $this->meta_store_with_manifest( $manifest );
		$files = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);

		$cleanup = $this->cleanup_service( $store, $files, new FakeAttachmentJobCleaner() );
		$result  = $cleanup->cleanup_attachment( self::ATTACHMENT_ID );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertSame( 1, $result->deleted_files() );
		self::assertSame( 4, $result->deleted_meta() );
		self::assertSame( array( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif' ), $files->deleted );
		self::assertArrayHasKey( self::UPLOADS . '/2026/07/hero.jpg', $files->files );
		self::assertSame( array(), $store->meta[ self::ATTACHMENT_ID ] );
	}

	/**
	 * Test partial delete problems do not block the remaining cleanup.
	 *
	 * @return void
	 */
	public function test_partial_delete_failures_do_not_block_remaining_cleanup_or_meta_removal(): void {
		$store = $this->meta_store_with_manifest( $this->manifest() );
		$files = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);

		$cleanup = $this->cleanup_service( $store, $files, new FakeAttachmentJobCleaner() );
		$result  = $cleanup->cleanup_attachment( self::ATTACHMENT_ID );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( AttachmentCleanupResult::CODE_DERIVATIVE_MISSING ) );
		self::assertSame( 1, $result->deleted_files() );
		self::assertSame( 4, $result->deleted_meta() );
		self::assertSame( array( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif' ), $files->deleted );
	}

	/**
	 * Test dry-run orphan reconciliation reports deterministic untracked sidecars.
	 *
	 * @return void
	 */
	public function test_dry_run_orphan_reconciliation_reports_untracked_deterministic_sidecars(): void {
		$store   = $this->meta_store_with_manifest( $this->manifest_without_avif() );
		$files   = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);
		$cleanup = $this->cleanup_service( $store, $files, new FakeAttachmentJobCleaner() );
		$result  = $cleanup->dry_run_orphan_reconciliation( self::ATTACHMENT_ID );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( AttachmentCleanupResult::CODE_ORPHAN_DERIVATIVES_DETECTED ) );
		self::assertSame( 1, $result->orphan_files() );
		self::assertSame( array( '2026/07/hero.jpg.hwlio.avif' ), $result->orphan_file_samples() );
	}

	/**
	 * Build the cleanup service.
	 *
	 * @param FakeAttachmentMetaStore              $store Meta store.
	 * @param FakeFilesystem                       $filesystem Filesystem.
	 * @param FakeAttachmentJobCleaner             $jobs Job cleaner.
	 * @param FakeCacheInvalidationDispatcher|null $dispatcher Cache invalidation dispatcher.
	 * @return AttachmentCleanup
	 */
	private function cleanup_service(
		FakeAttachmentMetaStore $store,
		FakeFilesystem $filesystem,
		FakeAttachmentJobCleaner $jobs,
		?FakeCacheInvalidationDispatcher $dispatcher = null
	): AttachmentCleanup {
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
		$probe    = new FakeImageFileProbe( array( self::UPLOADS ) );
		$probe->add_file(
			self::UPLOADS . '/2026/07/hero.jpg',
			920000,
			1783526400,
			'image/jpeg',
			2400,
			1600
		);

		return new AttachmentCleanup(
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			$store,
			new DerivativeFileCleaner( self::UPLOADS, $filesystem ),
			$jobs,
			new LocalAttachmentSourceCollector( new SourceCollector( $provider, $probe ) ),
			$dispatcher
		);
	}

	/**
	 * Build a fake meta store seeded with the canonical attachment keys.
	 *
	 * @param array<string,mixed> $manifest Manifest.
	 * @return FakeAttachmentMetaStore
	 */
	private function meta_store_with_manifest( array $manifest ): FakeAttachmentMetaStore {
		$store                              = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ] = array(
			LifecyclePolicy::META_DERIVATIVES => $manifest,
			LifecyclePolicy::META_STATUS      => array(
				'state'      => 'optimized',
				'formats'    => array( 'webp', 'avif' ),
				'updated_at' => 1783526510,
				'error_code' => null,
				'excluded'   => false,
			),
			LifecyclePolicy::META_EXCLUDED    => true,
			LifecyclePolicy::META_LOCK        => array(
				'token'      => 'token',
				'created_at' => 1783526400,
				'expires_at' => 1783527000,
			),
		);

		return $store;
	}

	/**
	 * Build a representative manifest with WebP and AVIF.
	 *
	 * @return array<string,mixed>
	 */
	private function manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/hero.jpg',
				'file_size'     => 920000,
				'modified_time' => 1783526400,
				'metadata_hash' => str_repeat( 'a', 64 ),
				'signature'     => 'aaaaaaaaaaaaaaaaaaaa',
			),
			'updated_at'     => 1783526510,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 920000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 310000,
							'quality'         => 82,
							'savings_bytes'   => 610000,
							'savings_percent' => 66.3,
							'status'          => 'ready',
							'generated_at'    => 1783526500,
						),
						'avif' => array(
							'file'            => '2026/07/hero.jpg.hwlio.avif',
							'mime'            => 'image/avif',
							'bytes'           => 218000,
							'quality'         => 60,
							'savings_bytes'   => 702000,
							'savings_percent' => 76.3,
							'status'          => 'ready',
							'generated_at'    => 1783526510,
						),
					),
				),
			),
		);
	}

	/**
	 * Build a representative manifest without AVIF.
	 *
	 * @return array<string,mixed>
	 */
	private function manifest_without_avif(): array {
		$manifest = $this->manifest();
		unset( $manifest['sizes']['full']['formats']['avif'] );

		return $manifest;
	}
}
