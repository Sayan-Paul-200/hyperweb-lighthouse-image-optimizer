<?php
/**
 * Tests for the content issue report service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Image\AnimationStatus;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentCriticalImageSelector;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentIssueReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAnimationDetector;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorDocumentDataStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the conservative 12.2 page-level issue rules.
 */
final class ContentIssueReportServiceTest extends TestCase {

	/**
	 * Test inline attachment rules reuse the conservative delivery certainty boundaries.
	 *
	 * @return void
	 */
	public function test_report_detects_inline_attachment_findings_conservatively(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[55] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Landing page',
			'body'   => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="300" loading="lazy"><img class="wp-image-124" src="https://example.test/wp-content/uploads/2026/07/hero-secondary.jpg" width="300" height="200" loading="eager"><img class="wp-image-125" src="https://example.test/wp-content/uploads/2026/07/card-300x200.jpg" width="300">',
		);

		$meta = new FakeAttachmentMetaStore();
		$meta->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'unprocessed',
			'formats'  => array(),
			'excluded' => false,
		);
		$meta->meta[124][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp', 'avif' ),
			'excluded' => false,
		);
		$meta->meta[125][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp', 'avif' ),
			'excluded' => false,
		);

		$attachment_runtime               = new FakeAttachmentImageRuntime();
		$attachment_runtime->metadata_map = array(
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
				'file'   => '2026/07/hero-secondary.jpg',
				'width'  => 1200,
				'height' => 800,
				'sizes'  => array(
					'medium' => array(
						'file'   => 'hero-secondary-300x200.jpg',
						'width'  => 300,
						'height' => 200,
					),
				),
			),
			125 => array(
				'file'   => '2026/07/card.jpg',
				'width'  => 900,
				'height' => 600,
				'sizes'  => array(
					'medium' => array(
						'file'   => 'card-300x200.jpg',
						'width'  => 300,
						'height' => 200,
					),
				),
			),
		);

		$critical_store             = new FakeCriticalImagePostMetaStore();
		$critical_store->values[55] = 123;

		$report = $this->issue_service(
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp', 'avif' ),
				)
			),
			$attachment_runtime,
			new FakeUploadsUrlRuntime(),
			new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) ),
			new FakeAnimationDetector(),
			$critical_store
		)->report( $this->inventory_service( $runtime, $meta )->inspect( 55 ) )->to_array();

		$codes = array_column( $report, 'code' );

		self::assertContains( 'missing_modern_derivative', $codes );
		self::assertContains( 'oversized_source_selection', $codes );
		self::assertContains( 'missing_responsive_candidates', $codes );
		self::assertContains( 'missing_intrinsic_dimensions', $codes );
		self::assertContains( 'critical_image_lazy_loaded', $codes );
		self::assertContains( 'below_the_fold_eager_loading', $codes );
	}

	/**
	 * Test external, duplicate, broken, and animated findings stay conservative.
	 *
	 * @return void
	 */
	public function test_report_detects_external_duplicate_broken_and_animated_findings(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[77] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Mixed images',
			'body'   => '<img class="wp-image-300" src="https://example.test/wp-content/uploads/2026/07/gallery-300x200.jpg" width="300" height="200"><img class="wp-image-300" src="https://example.test/wp-content/uploads/2026/07/gallery.jpg" width="1200" height="800"><img src="https://cdn.example.test/external.jpg"><img src="https://example.test/wp-content/uploads/2026/07/missing.jpg"><img class="wp-image-301" src="https://example.test/wp-content/uploads/2026/07/anim.gif" width="400" height="300">',
		);

		$meta = new FakeAttachmentMetaStore();
		$meta->meta[300][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp' ),
			'excluded' => false,
		);
		$meta->meta[301][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp' ),
			'excluded' => false,
		);

		$attachment_runtime               = new FakeAttachmentImageRuntime();
		$attachment_runtime->metadata_map = array(
			300 => array(
				'file'   => '2026/07/gallery.jpg',
				'width'  => 1200,
				'height' => 800,
				'sizes'  => array(
					'medium' => array(
						'file'   => 'gallery-300x200.jpg',
						'width'  => 300,
						'height' => 200,
					),
				),
			),
			301 => array(
				'file'   => '2026/07/anim.gif',
				'width'  => 400,
				'height' => 300,
			),
		);

		$uploads = new FakeUploadsUrlRuntime();
		$files   = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );
		$files->add_file( 'C:/site/wp-content/uploads/2026/07/gallery.jpg', 120000, 1783526400, 'image/jpeg', 1200, 800 );
		$files->add_file( 'C:/site/wp-content/uploads/2026/07/gallery-300x200.jpg', 24000, 1783526400, 'image/jpeg', 300, 200 );
		$files->add_file( 'C:/site/wp-content/uploads/2026/07/anim.gif', 480000, 1783526400, 'image/gif', 400, 300 );

		$animation = new FakeAnimationDetector();
		$animation->set_status( 'C:/site/wp-content/uploads/2026/07/anim.gif', AnimationStatus::animated( 'image/gif', 'animated_gif' ) );

		$report = $this->issue_service(
			new FakeSettingsRepository(
				array(
					'enabled_formats' => array( 'webp' ),
				)
			),
			$attachment_runtime,
			$uploads,
			$files,
			$animation,
			new FakeCriticalImagePostMetaStore()
		)->report( $this->inventory_service( $runtime, $meta )->inspect( 77 ) )->to_array();

		$codes = array_column( $report, 'code' );

		self::assertContains( 'external_image', $codes );
		self::assertContains( 'duplicate_source_downloads', $codes );
		self::assertContains( 'broken_image_url', $codes );
		self::assertContains( 'animated_gif', $codes );
	}

	/**
	 * Test excluded attachments skip derivative-missing findings while Elementor backgrounds still report advisory issues.
	 *
	 * @return void
	 */
	public function test_report_skips_excluded_derivative_findings_and_reports_css_backgrounds(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[88] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Elementor page',
			'body'   => '',
		);

		$meta = new FakeAttachmentMetaStore();
		foreach ( array( 901, 902, 903 ) as $attachment_id ) {
			$meta->meta[ $attachment_id ][ LifecyclePolicy::META_STATUS ] = array(
				'state'    => 'excluded',
				'formats'  => array(),
				'excluded' => true,
			);
		}

		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-classic-responsive.php' ) );

		$attachment_runtime               = new FakeAttachmentImageRuntime();
		$attachment_runtime->metadata_map = array(
			901 => array( 'file' => '2026/07/hero.jpg', 'width' => 1600, 'height' => 900 ),
			902 => array( 'file' => '2026/07/hero-tablet.jpg', 'width' => 1200, 'height' => 900 ),
			903 => array( 'file' => '2026/07/hero-mobile.jpg', 'width' => 800, 'height' => 900 ),
		);

		$report = $this->issue_service(
			new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) ),
			$attachment_runtime,
			new FakeUploadsUrlRuntime(),
			new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) ),
			new FakeAnimationDetector(),
			new FakeCriticalImagePostMetaStore()
		)->report( $this->inventory_service( $runtime, $meta, $store )->inspect( 88 ) )->to_array();

		$codes = array_column( $report, 'code' );

		self::assertContains( 'css_background_image', $codes );
		self::assertNotContains( 'missing_modern_derivative', $codes );
	}

	/**
	 * Build the inventory service.
	 *
	 * @param FakeContentInventoryRuntime|null     $runtime Runtime.
	 * @param FakeAttachmentMetaStore|null         $meta Meta store.
	 * @param FakeElementorDocumentDataStore|null  $store Elementor store.
	 * @return ContentInventoryService
	 */
	private function inventory_service(
		?FakeContentInventoryRuntime $runtime = null,
		?FakeAttachmentMetaStore $meta = null,
		?FakeElementorDocumentDataStore $store = null
	): ContentInventoryService {
		$runtime = $runtime ?? new FakeContentInventoryRuntime();
		$meta    = $meta ?? new FakeAttachmentMetaStore();
		$store   = $store ?? new FakeElementorDocumentDataStore();

		return new ContentInventoryService(
			$runtime,
			new AttachmentStatusReader( $meta ),
			$store,
			new ElementorBackgroundDiscovery( $store ),
			new TrustedAttachmentMarkerParser()
		);
	}

	/**
	 * Build the issue service.
	 *
	 * @param FakeSettingsRepository         $settings Settings.
	 * @param FakeAttachmentImageRuntime     $attachments Attachment runtime.
	 * @param FakeUploadsUrlRuntime          $uploads Uploads runtime.
	 * @param FakeImageFileProbe             $files File probe.
	 * @param FakeAnimationDetector          $animation Animation detector.
	 * @param FakeCriticalImagePostMetaStore $critical_store Critical-image store.
	 * @return ContentIssueReportService
	 */
	private function issue_service(
		FakeSettingsRepository $settings,
		FakeAttachmentImageRuntime $attachments,
		FakeUploadsUrlRuntime $uploads,
		FakeImageFileProbe $files,
		FakeAnimationDetector $animation,
		FakeCriticalImagePostMetaStore $critical_store
	): ContentIssueReportService {
		$analyzer        = new WordPressImageMarkupAnalyzer();
		$sanitizer       = new DerivativeManifestSanitizer();
		$size_resolver   = new AttachmentSizeResolver( $sanitizer );

		return new ContentIssueReportService(
			$settings,
			$attachments,
			$uploads,
			$files,
			$animation,
			$analyzer,
			new AttachmentImageSourceExtractor( $analyzer ),
			$size_resolver,
			new IntrinsicDimensionRepair( $size_resolver, $analyzer ),
			new ContentCriticalImageSelector( $critical_store ),
			$sanitizer
		);
	}

	/**
	 * Load one structured background fixture.
	 *
	 * @param string $file Fixture file.
	 * @return array<int,array<string,mixed>>
	 */
	private function fixture_elements( string $file ): array {
		$elements = require dirname( __DIR__, 2 ) . '/Fixtures/Elementor/BackgroundDiscovery/' . $file;

		self::assertIsArray( $elements );

		return $elements;
	}
}
