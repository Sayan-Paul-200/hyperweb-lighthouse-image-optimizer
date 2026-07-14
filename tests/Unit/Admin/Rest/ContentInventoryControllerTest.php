<?php
// phpcs:ignoreFile -- Test doubles in this fixture-heavy file intentionally trade strict docblock verbosity for readability.
/**
 * Tests for the content inventory REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\ContentInventoryController;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentByteReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentCriticalImageSelector;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentIssueReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\OccurrenceAssetMapper;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAnimationDetector;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorDocumentDataStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting\FakeContentInventoryRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration and content inventory callback behavior.
 */
final class ContentInventoryControllerTest extends TestCase {

	/**
	 * Test route registration registers only the content inventory route.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_content_inventory_route_only(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->controller( $runtime );

		$controller->register_routes();

		self::assertCount( 1, $runtime->routes );
		self::assertSame( 'hwlio/v1', $runtime->routes[0]['namespace'] );
		self::assertSame( '/content/(?P<content_id>[\\d]+)/inventory', $runtime->routes[0]['route'] );
		self::assertSame( 'GET', $runtime->routes[0]['definitions'][0]['methods'] );
	}

	/**
	 * Test manage_options is required for page inventory reads.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_manage_options(): void {
		$runtime                                 = new FakeRestRuntime();
		$runtime->capabilities['manage_options'] = false;
		$controller                              = $this->controller( $runtime );

		$result = $controller->can_manage_options();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test invalid IDs are rejected cleanly.
	 *
	 * @return void
	 */
	public function test_invalid_content_ids_are_rejected(): void {
		$response = $this->controller( new FakeRestRuntime() )->get_inventory( array( 'content_id' => 0 ) );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'invalid_content_id', $response['code'] );
	}

	/**
	 * Test missing content returns a stable not-found error.
	 *
	 * @return void
	 */
	public function test_missing_content_returns_not_found(): void {
		$response = $this->controller( new FakeRestRuntime(), false )->get_inventory( array( 'content_id' => 55 ) );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'content_not_found', $response['code'] );
	}

	/**
	 * Test report payload is returned through the runtime seam.
	 *
	 * @return void
	 */
	public function test_get_inventory_returns_report_payload(): void {
		$response = $this->controller( new FakeRestRuntime(), true )->get_inventory( array( 'content_id' => 55 ) );

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertSame( 55, $response['data']['content']['id'] );
		self::assertSame( 1, $response['data']['summary']['total_items'] );
		self::assertArrayHasKey( 'issue_summary', $response['data'] );
		self::assertArrayHasKey( 'issues', $response['data'] );
		self::assertArrayHasKey( 'byte_summary', $response['data'] );
		self::assertArrayHasKey( 'byte_occurrences', $response['data'] );
	}

	/**
	 * Test runtime failures are normalized safely.
	 *
	 * @return void
	 */
	public function test_get_inventory_returns_safe_unavailable_error_when_report_fails(): void {
		$runtime    = new FakeRestRuntime();
		$service    = new ContentInventoryService(
			new class() implements ContentInventoryRuntimeInterface {
				public function content_exists( int $content_id ): bool {
					return 55 === $content_id;
				}

				public function content_type( int $content_id ): string {
					return 'page';
				}

				public function content_status( int $content_id ): string {
					return 'publish';
				}

				public function content_title( int $content_id ): string {
					return 'Broken page';
				}

				public function content_body( int $content_id ): string {
					throw new \RuntimeException( 'boom' );
				}

				public function featured_image_id( int $content_id ): int {
					return 0;
				}

				public function product_gallery_image_ids( int $content_id ): array {
					return array();
				}

				public function site_url(): string {
					return 'https://example.test/wp/';
				}

				public function home_url(): string {
					return 'https://example.test/';
				}

				public function uploads_base_url(): string {
					return 'https://example.test/wp-content/uploads';
				}

				public function content_public_url( int $content_id ): string {
					return 55 === $content_id ? 'https://example.test/broken-page/' : '';
				}
			},
			new AttachmentStatusReader( new FakeAttachmentMetaStore() ),
			new FakeElementorDocumentDataStore(),
			new ElementorBackgroundDiscovery( new FakeElementorDocumentDataStore() ),
			new TrustedAttachmentMarkerParser()
		);
		$controller = new ContentInventoryController(
			$runtime,
			new RestErrorFactory( $runtime ),
			$service,
			$this->issue_service(),
			$this->byte_service()
		);

		$response = $controller->get_inventory( array( 'content_id' => 55 ) );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'content_inventory_unavailable', $response['code'] );
	}

	/**
	 * Build controller fixture.
	 *
	 * @param FakeRestRuntime $runtime Runtime.
	 * @param bool            $exists Whether content exists.
	 * @return ContentInventoryController
	 */
	private function controller( FakeRestRuntime $runtime, bool $exists = true ): ContentInventoryController {
		return new ContentInventoryController(
			$runtime,
			new RestErrorFactory( $runtime ),
			$this->service( $exists ),
			$this->issue_service(),
			$this->byte_service()
		);
	}

	/**
	 * Build a real inventory service backed by unit-test fakes.
	 *
	 * @param bool $exists Whether the requested content exists.
	 * @return ContentInventoryService
	 */
	private function service( bool $exists = true ): ContentInventoryService {
		$runtime = new FakeContentInventoryRuntime();
		$meta    = new FakeAttachmentMetaStore();
		$store   = new FakeElementorDocumentDataStore();

		if ( $exists ) {
			$runtime->content[55]                            = array(
				'type'   => 'page',
				'status' => 'publish',
				'title'  => 'Landing page',
				'body'   => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/hero.jpg">',
			);
			$meta->meta[123][ LifecyclePolicy::META_STATUS ] = array(
				'state'    => 'optimized',
				'formats'  => array( 'webp' ),
				'excluded' => false,
			);
		}

		return new ContentInventoryService(
			$runtime,
			new AttachmentStatusReader( $meta ),
			$store,
			new ElementorBackgroundDiscovery( $store ),
			new TrustedAttachmentMarkerParser()
		);
	}

	/**
	 * Build the issue-reporting service.
	 *
	 * @return ContentIssueReportService
	 */
	private function issue_service(): ContentIssueReportService {
		$analyzer      = new WordPressImageMarkupAnalyzer();
		$sanitizer     = new DerivativeManifestSanitizer();
		$size_resolver = new AttachmentSizeResolver( $sanitizer );

		return new ContentIssueReportService(
			new FakeSettingsRepository(),
			new FakeAttachmentImageRuntime(),
			new FakeUploadsUrlRuntime(),
			new FakeImageFileProbe(),
			new FakeAnimationDetector(),
			$analyzer,
			new AttachmentImageSourceExtractor( $analyzer ),
			$size_resolver,
			new IntrinsicDimensionRepair( $size_resolver, $analyzer ),
			new ContentCriticalImageSelector( new FakeCriticalImagePostMetaStore() ),
			$sanitizer
		);
	}

	/**
	 * Build the byte-reporting service.
	 *
	 * @return ContentByteReportService
	 */
	private function byte_service(): ContentByteReportService {
		$analyzer  = new WordPressImageMarkupAnalyzer();
		$sanitizer = new DerivativeManifestSanitizer();

		return new ContentByteReportService(
			new FakeSettingsRepository(),
			new DerivativeRepository( new FakeAttachmentMetaStore(), $sanitizer, new \HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock() ),
			new FakeImageFileProbe(),
			new OccurrenceAssetMapper(
				new FakeAttachmentImageRuntime(),
				new FakeUploadsUrlRuntime(),
				$analyzer,
				new AttachmentImageSourceExtractor( $analyzer ),
				new AttachmentSizeResolver( $sanitizer ),
				$sanitizer
			)
		);
	}
}
