<?php
/**
 * Tests for the CLI cleanup dry-run runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Cli\CliCleanupDryRunService;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentJobCleaner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeFilesystem;
use PHPUnit\Framework\TestCase;

/**
 * Verifies CLI cleanup dry-run aggregates bounded orphan reports safely.
 */
final class CliCleanupDryRunServiceTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test cleanup dry-run degrades when orphan derivatives are detected.
	 *
	 * @return void
	 */
	public function test_cleanup_dry_run_reports_orphan_sidecars(): void {
		$bulk = new FakeBulkScannerRuntime();
		$bulk->pages[0] = array( 15 );
		$store = new FakeAttachmentMetaStore();
		$store->meta[15] = array(
			LifecyclePolicy::META_DERIVATIVES => array(
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
						),
					),
				),
			),
			LifecyclePolicy::META_STATUS      => array(
				'state'      => 'optimized',
				'formats'    => array( 'webp' ),
				'updated_at' => 1783526510,
				'error_code' => null,
				'excluded'   => false,
			),
		);
		$filesystem = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );
		$probe->add_file(
			self::UPLOADS . '/2026/07/hero.jpg',
			920000,
			1783526400,
			'image/jpeg',
			2400,
			1600
		);
		$cleanup = new AttachmentCleanup(
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			$store,
			new DerivativeFileCleaner( self::UPLOADS, $filesystem ),
			new FakeAttachmentJobCleaner(),
			new SourceCollector(
				new FakeAttachmentSourceProvider(
					self::UPLOADS . '/2026/07/hero.jpg',
					array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'sizes'  => array(),
					),
					self::UPLOADS
				),
				$probe
			)
		);
		$service = new CliCleanupDryRunService( $bulk, $cleanup );

		$result = $service->dry_run( new BulkScanFilters() );

		self::assertTrue( $result->is_degraded() );
		self::assertSame( 1, $result->payload()['summary']['orphan_files'] );
		self::assertSame( array( '2026/07/hero.jpg.hwlio.avif' ), $result->payload()['summary']['orphan_file_samples'] );
	}
}
