<?php
/**
 * Tests for the content byte report service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentByteReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventorySnapshot;
use HyperWeb\LighthouseImageOptimizer\Reporting\InventoryOccurrence;
use HyperWeb\LighthouseImageOptimizer\Reporting\OccurrenceAssetMapper;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageInventoryItem;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative measured and theoretical byte reporting.
 */
final class ContentByteReportServiceTest extends TestCase {

	/**
	 * Test measured conversion totals and conservative theoretical transfer rows are aggregated safely.
	 *
	 * @return void
	 */
	public function test_report_builds_actual_and_theoretical_byte_sections_conservatively(): void {
		$store      = new FakeAttachmentMetaStore();
		$attachments = new FakeAttachmentImageRuntime();
		$uploads    = new FakeUploadsUrlRuntime();
		$files      = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );

		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->manifest(
			array(
				'full'   => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 400,
							'savings_bytes'   => 600,
							'savings_percent' => 60.0,
							'status'          => 'ready',
						),
						'avif' => array(
							'file'            => '2026/07/hero.jpg.hwlio.avif',
							'mime'            => 'image/avif',
							'bytes'           => 350,
							'savings_bytes'   => 650,
							'savings_percent' => 65.0,
							'status'          => 'ready',
						),
					),
				),
				'medium' => array(
					'source'  => array(
						'file'   => '2026/07/hero-300x200.jpg',
						'width'  => 300,
						'height' => 200,
						'mime'   => 'image/jpeg',
						'bytes'  => 250,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero-300x200.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 120,
							'savings_bytes'   => 130,
							'savings_percent' => 52.0,
							'status'          => 'ready',
						),
						'avif' => array(
							'file'            => '2026/07/hero-300x200.jpg.hwlio.avif',
							'mime'            => 'image/avif',
							'bytes'           => 100,
							'savings_bytes'   => 150,
							'savings_percent' => 60.0,
							'status'          => 'ready',
						),
					),
				),
			)
		);
		$store->meta[125][ LifecyclePolicy::META_DERIVATIVES ] = $this->manifest(
			array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/bg.jpg',
						'width'  => 1600,
						'height' => 900,
						'mime'   => 'image/jpeg',
						'bytes'  => 900,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/bg.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 500,
							'savings_bytes'   => 400,
							'savings_percent' => 44.44,
							'status'          => 'ready',
						),
					),
				),
			)
		);

		$attachments->metadata_map = array(
			123 => array(
				'file'   => '2026/07/hero.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(
					'medium' => array(
						'file'   => 'hero-300x200.jpg',
						'width'  => 300,
						'height' => 200,
					),
				),
			),
			124 => array(
				'file'   => '2026/07/ambiguous.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(
					'medium' => array(
						'file'   => 'ambiguous-300x200.jpg',
						'width'  => 300,
						'height' => 200,
					),
					'large'  => array(
						'file'   => 'ambiguous-1024x683.jpg',
						'width'  => 1024,
						'height' => 683,
					),
				),
			),
			125 => array(
				'file'   => '2026/07/bg.jpg',
				'width'  => 1600,
				'height' => 900,
			),
			126 => array(
				'file'   => '2026/07/gallery.jpg',
				'width'  => 1200,
				'height' => 800,
			),
		);

		$files->add_file( 'C:/site/wp-content/uploads/2026/07/hero-300x200.jpg', 250, 1783526400, 'image/jpeg', 300, 200 );
		$files->add_file( 'C:/site/wp-content/uploads/2026/07/unregistered.jpg', 600, 1783526400, 'image/jpeg', 800, 600 );
		$files->add_file( 'C:/site/wp-content/uploads/2026/07/bg.jpg', 900, 1783526400, 'image/jpeg', 1600, 900 );

		$report = $this->service( $store, $attachments, $uploads, $files )->report(
			new ContentInventorySnapshot(
				array(
					'id'             => 55,
					'type'           => 'page',
					'title'          => 'Landing page',
					'status'         => 'publish',
					'is_woo_product' => false,
				),
				array(
					new InventoryOccurrence(
						'hero-primary',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
						123,
						'https://example.test/wp-content/uploads/2026/07/hero-300x200.jpg',
						array(
							'state'         => 'optimized',
							'ready_formats' => array( 'webp', 'avif' ),
							'excluded'      => false,
						),
						array(),
						array(
							'raw_img_html' => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero-300x200.jpg" width="300" height="200">',
						)
					),
					new InventoryOccurrence(
						'hero-repeat',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
						123,
						'https://example.test/wp-content/uploads/2026/07/hero-300x200.jpg',
						array(
							'state'         => 'optimized',
							'ready_formats' => array( 'webp', 'avif' ),
							'excluded'      => false,
						),
						array(),
						array(
							'raw_img_html' => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero-300x200.jpg" width="300" height="200">',
						)
					),
					new InventoryOccurrence(
						'hero-ambiguous',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
						124,
						'https://example.test/wp-content/uploads/2026/07/ambiguous-300x200.jpg',
						array(
							'state'         => 'partial',
							'ready_formats' => array( 'webp' ),
							'excluded'      => false,
						),
						array(),
						array(
							'raw_img_html' => '<img class="wp-image-124" src="https://example.test/wp-content/uploads/2026/07/ambiguous-300x200.jpg" srcset="https://example.test/wp-content/uploads/2026/07/ambiguous-300x200.jpg 300w, https://example.test/wp-content/uploads/2026/07/ambiguous-1024x683.jpg 1024w" width="300" height="200">',
						)
					),
					new InventoryOccurrence(
						'local-unregistered',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_LOCAL_UNREGISTERED,
						null,
						'https://example.test/wp-content/uploads/2026/07/unregistered.jpg'
					),
					new InventoryOccurrence(
						'external-image',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_EXTERNAL,
						null,
						'https://cdn.example.test/external.jpg'
					),
					new InventoryOccurrence(
						'background-hero',
						'elementor_background',
						PageInventoryItem::PRESENTATION_BACKGROUND,
						PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
						125,
						'https://example.test/wp-content/uploads/2026/07/bg.jpg',
						array(
							'state'         => 'optimized',
							'ready_formats' => array( 'webp' ),
							'excluded'      => false,
						)
					),
					new InventoryOccurrence(
						'woo-gallery',
						'woocommerce_gallery',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
						126,
						null,
						array(
							'state'         => 'unprocessed',
							'ready_formats' => array(),
							'excluded'      => false,
						)
					),
					new InventoryOccurrence(
						'unknown-relative',
						'core_content',
						PageInventoryItem::PRESENTATION_INLINE,
						PageInventoryItem::ORIGIN_UNKNOWN,
						null,
						'images/relative.jpg'
					),
				)
			)
		);

		$summary = $report->summary();
		$rows    = $report->occurrences();
		$json    = json_encode(
			array(
				'summary' => $summary,
				'rows'    => $rows,
			)
		);

		self::assertSame( 4, $summary['actual_conversion']['attachments_considered'] );
		self::assertSame( 3, $summary['actual_conversion']['source_sizes_represented'] );
		self::assertSame( 2150, $summary['actual_conversion']['source_bytes'] );
		self::assertSame( 950, $summary['actual_conversion']['generated_bytes'] );
		self::assertSame( 1200, $summary['actual_conversion']['savings_bytes'] );
		self::assertSame( 55.81, $summary['actual_conversion']['savings_percent'] );
		self::assertSame( 1020, $summary['actual_conversion']['formats']['webp']['generated_bytes'] );
		self::assertSame( 450, $summary['actual_conversion']['formats']['avif']['generated_bytes'] );
		self::assertSame( 3, $summary['theoretical_page_transfer']['unique_downloads_considered'] );
		self::assertSame( 2, $summary['theoretical_page_transfer']['estimated_downloads'] );
		self::assertSame( 1, $summary['theoretical_page_transfer']['estimate_unavailable_downloads'] );
		self::assertSame( 1750, $summary['theoretical_page_transfer']['source_bytes'] );
		self::assertSame( 600, $summary['theoretical_page_transfer']['modern_bytes'] );
		self::assertSame( 550, $summary['theoretical_page_transfer']['savings_bytes'] );
		self::assertSame( 31.43, $summary['theoretical_page_transfer']['savings_percent'] );

		self::assertSame( 'estimated', $rows[0]['estimate_status'] );
		self::assertSame( 'medium', $rows[0]['matched_size_name'] );
		self::assertSame( 'avif', $rows[0]['best_ready_format'] );
		self::assertSame( 'estimated', $rows[1]['estimate_status'] );
		self::assertSame( 'responsive_candidate_uncertain', $rows[2]['estimate_reason'] );
		self::assertSame( 'source_only', $rows[3]['estimate_status'] );
		self::assertSame( 600, $rows[3]['source_bytes'] );
		self::assertSame( 'unavailable', $rows[4]['estimate_status'] );
		self::assertSame( 'estimated', $rows[5]['estimate_status'] );
		self::assertSame( 'webp', $rows[5]['best_ready_format'] );
		self::assertSame( 'unavailable', $rows[6]['estimate_status'] );
		self::assertSame( 'attachment_unmapped', $rows[6]['estimate_reason'] );
		self::assertSame( 'unavailable', $rows[7]['estimate_status'] );
		self::assertIsString( $json );
		self::assertStringNotContainsString( 'C:/site/wp-content/uploads', $json );
	}

	/**
	 * Build the byte report service.
	 *
	 * @param FakeAttachmentMetaStore    $store Meta store.
	 * @param FakeAttachmentImageRuntime $attachments Attachment runtime.
	 * @param FakeUploadsUrlRuntime      $uploads Uploads runtime.
	 * @param FakeImageFileProbe         $files File probe.
	 * @return ContentByteReportService
	 */
	private function service(
		FakeAttachmentMetaStore $store,
		FakeAttachmentImageRuntime $attachments,
		FakeUploadsUrlRuntime $uploads,
		FakeImageFileProbe $files
	): ContentByteReportService {
		$analyzer  = new WordPressImageMarkupAnalyzer();
		$sanitizer = new DerivativeManifestSanitizer();

		return new ContentByteReportService(
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp', 'avif' ),
				)
			),
			new DerivativeRepository( $store, $sanitizer, new SystemAttachmentClock() ),
			$files,
			new OccurrenceAssetMapper(
				$attachments,
				$uploads,
				$analyzer,
				new AttachmentImageSourceExtractor( $analyzer ),
				new AttachmentSizeResolver( $sanitizer ),
				$sanitizer
			)
		);
	}

	/**
	 * Build a stored manifest payload.
	 *
	 * @param array<string,array<string,mixed>> $sizes Manifest sizes.
	 * @return array<string,mixed>
	 */
	private function manifest( array $sizes ): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => null,
			'updated_at'     => 1783526500,
			'sizes'          => $sizes,
		);
	}
}
