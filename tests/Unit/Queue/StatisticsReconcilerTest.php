<?php
/**
 * Statistics reconciler tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsReconciliationResult;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsReconciler;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative statistics reconciliation.
 */
final class StatisticsReconcilerTest extends TestCase {

	/**
	 * Test mixed attachments aggregate correctly and avoid double counting best formats.
	 *
	 * @return void
	 */
	public function test_reconcile_aggregates_mixed_attachment_states_and_totals(): void {
		$store  = new FakeAttachmentMetaStore();
		$store->meta[101][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_OPTIMIZED,
			'formats'    => array( 'webp', 'avif' ),
			'updated_at' => 1783526500,
			'error_code' => null,
			'excluded'   => false,
		);
		$store->meta[101][ LifecyclePolicy::META_DERIVATIVES ] = $this->manifest(
			array(
				'full'   => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2000,
						'height' => 1200,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 600,
							'quality'         => 82,
							'savings_bytes'   => 400,
							'savings_percent' => 40.0,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526501,
						),
						'avif' => array(
							'file'            => '2026/07/hero.jpg.hwlio.avif',
							'mime'            => 'image/avif',
							'bytes'           => 500,
							'quality'         => 60,
							'savings_bytes'   => 500,
							'savings_percent' => 50.0,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526502,
						),
					),
				),
				'medium' => array(
					'source'  => array(
						'file'   => '2026/07/hero-768x461.jpg',
						'width'  => 768,
						'height' => 461,
						'mime'   => 'image/jpeg',
						'bytes'  => 400,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero-768x461.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 250,
							'quality'         => 82,
							'savings_bytes'   => 150,
							'savings_percent' => 37.5,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526503,
						),
					),
				),
			)
		);
		$store->meta[102][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_PARTIAL,
			'formats'    => array( 'webp', 'avif' ),
			'updated_at' => 1783526600,
			'error_code' => 'conversion_failed',
			'excluded'   => false,
		);
		$store->meta[102][ LifecyclePolicy::META_DERIVATIVES ] = $this->manifest(
			array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/product.png',
						'width'  => 1600,
						'height' => 1600,
						'mime'   => 'image/png',
						'bytes'  => 800,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/product.png.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 700,
							'quality'         => 82,
							'savings_bytes'   => 100,
							'savings_percent' => 12.5,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526601,
						),
						'avif' => array(
							'file'            => '2026/07/product.png.hwlio.avif',
							'mime'            => 'image/avif',
							'bytes'           => 650,
							'quality'         => 60,
							'savings_bytes'   => 150,
							'savings_percent' => 18.75,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526602,
						),
					),
				),
			)
		);
		$store->meta[103][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_FAILED,
			'formats'    => array(),
			'updated_at' => 1783526700,
			'error_code' => 'source_missing',
			'excluded'   => false,
		);
		$store->meta[103][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 999,
		);
		$store->meta[104][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_STALE,
			'formats'    => array(),
			'updated_at' => 1783526800,
			'error_code' => null,
			'excluded'   => false,
		);

		$options    = new FakeOptionStore();
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new FixedAttachmentClock( 1783526900 ) );
		$scanner    = new FakeAttachmentStatisticsScanner(
			array(
				array( 101, 102, 103, 104 ),
			)
		);
		$reconciler = new StatisticsReconciler(
			$scanner,
			$repository,
			$options,
			static function (): string {
				return '2026-07-11 12:00:00';
			}
		);

		$result = $reconciler->reconcile();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( StatisticsReconciliationResult::CODE_RECONCILED ) );
		self::assertTrue( $result->has_code( StatisticsReconciliationResult::CODE_METADATA_IGNORED ) );
		self::assertSame( 100, $scanner->last_page_size );
		self::assertSame( array( 1 ), $scanner->requested_pages );
		self::assertSame( 'no', $options->autoload[ LifecyclePolicy::OPTION_STATISTICS_CACHE ] );
		self::assertSame( 4, $result->cache()->totals()['attachments_considered'] );
		self::assertSame( 2, $result->cache()->totals()['attachments_with_ready_derivatives'] );
		self::assertSame( 3, $result->cache()->totals()['sources_represented'] );
		self::assertSame( 2200, $result->cache()->totals()['source_bytes'] );
		self::assertSame( 1400, $result->cache()->totals()['generated_bytes'] );
		self::assertSame( 800, $result->cache()->totals()['savings_bytes'] );
		self::assertSame( 36.36, $result->cache()->totals()['savings_percent'] );
		self::assertSame( 1, $result->cache()->attachment_states()['optimized'] );
		self::assertSame( 1, $result->cache()->attachment_states()['partial'] );
		self::assertSame( 1, $result->cache()->attachment_states()['failed'] );
		self::assertSame( 1, $result->cache()->attachment_states()['stale'] );
		self::assertSame( 3, $result->cache()->formats()['webp']['sources_ready'] );
		self::assertSame( 2200, $result->cache()->formats()['webp']['source_bytes'] );
		self::assertSame( 1550, $result->cache()->formats()['webp']['generated_bytes'] );
		self::assertSame( 650, $result->cache()->formats()['webp']['savings_bytes'] );
		self::assertSame( 2, $result->cache()->formats()['avif']['sources_ready'] );
		self::assertSame( 1800, $result->cache()->formats()['avif']['source_bytes'] );
		self::assertSame( 1150, $result->cache()->formats()['avif']['generated_bytes'] );
		self::assertSame( 650, $result->cache()->formats()['avif']['savings_bytes'] );
	}

	/**
	 * Test failed writes preserve the previous cache.
	 *
	 * @return void
	 */
	public function test_reconcile_preserves_previous_cache_when_write_fails(): void {
		$store                          = new FakeAttachmentMetaStore();
		$store->meta[101][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_OPTIMIZED,
			'formats'    => array( 'webp' ),
			'updated_at' => 1783526500,
			'error_code' => null,
			'excluded'   => false,
		);
		$store->meta[101][ LifecyclePolicy::META_DERIVATIVES ] = $this->manifest(
			array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2000,
						'height' => 1200,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 600,
							'quality'         => 82,
							'savings_bytes'   => 400,
							'savings_percent' => 40.0,
							'status'          => DerivativeManifest::FORMAT_STATUS_READY,
							'generated_at'    => 1783526501,
						),
					),
				),
			)
		);

		$options = new FakeOptionStore(
			array(
				LifecyclePolicy::OPTION_STATISTICS_CACHE => array(
					'schema_version'    => 1,
					'generated_at_gmt'  => '2026-07-10 00:00:00',
					'attachment_states' => array(
						'optimized' => 99,
					),
					'totals'            => array(
						'attachments_considered' => 99,
					),
					'formats'           => array(),
				),
			)
		);
		$options->autoload[ LifecyclePolicy::OPTION_STATISTICS_CACHE ] = 'no';
		$options->fail_updates = true;

		$reconciler = new StatisticsReconciler(
			new FakeAttachmentStatisticsScanner( array( array( 101 ) ) ),
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new FixedAttachmentClock( 1783526900 ) ),
			$options,
			static function (): string {
				return '2026-07-11 12:00:00';
			}
		);

		$result = $reconciler->reconcile();

		self::assertFalse( $result->is_successful() );
		self::assertTrue( $result->has_code( StatisticsReconciliationResult::CODE_WRITE_FAILED ) );
		self::assertSame( '2026-07-10 00:00:00', $options->options[ LifecyclePolicy::OPTION_STATISTICS_CACHE ]['generated_at_gmt'] );
		self::assertSame( 99, $options->options[ LifecyclePolicy::OPTION_STATISTICS_CACHE ]['totals']['attachments_considered'] );
	}

	/**
	 * Build a raw stored manifest.
	 *
	 * @param array<string,array<string,mixed>> $sizes Sizes.
	 * @return array<string,mixed>
	 */
	private function manifest( array $sizes ): array {
		return array(
			'schema_version' => DerivativeManifest::SCHEMA_VERSION,
			'fingerprint'    => array(
				'relative_file' => '2026/07/source.jpg',
				'file_size'     => 1000,
				'modified_time' => 1783526400,
				'metadata_hash' => str_repeat( 'a', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => $sizes,
		);
	}
}
