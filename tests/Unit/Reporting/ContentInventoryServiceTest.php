<?php
/**
 * Tests for the content inventory service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolver;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorDocumentDataStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative page inventory reporting.
 */
final class ContentInventoryServiceTest extends TestCase {

	/**
	 * Test core content URLs classify conservatively and preserve occurrence order.
	 *
	 * @return void
	 */
	public function test_core_content_inventory_classifies_occurrences_conservatively(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[55] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Landing page',
			'body'   => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero.jpg"><img src="https://example.test/wp-content/uploads/2026/07/unregistered.jpg"><img src="https://cdn.example.test/hero.jpg"><img src="/wp-content/uploads/relative.jpg">',
		);
		$meta                 = new FakeAttachmentMetaStore();
		$meta->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp', 'avif' ),
			'excluded' => false,
		);

		$report = $this->service( $runtime, $meta )->report( 55 )->to_array();

		self::assertSame( 'page', $report['content']['type'] );
		self::assertSame( 4, $report['summary']['total_items'] );
		self::assertSame( 1, $report['summary']['by_origin']['local_attachment'] );
		self::assertSame( 1, $report['summary']['by_origin']['local_unregistered_url'] );
		self::assertSame( 1, $report['summary']['by_origin']['external'] );
		self::assertSame( 1, $report['summary']['by_origin']['unknown'] );
		self::assertSame( 'occ-1', $report['items'][0]['id'] );
		self::assertSame( 123, $report['items'][0]['attachment_id'] );
		self::assertSame( 'optimized', $report['items'][0]['attachment']['state'] );
		self::assertSame( 'local_unregistered_url', $report['items'][1]['origin'] );
		self::assertSame( 'external', $report['items'][2]['origin'] );
		self::assertSame( 'unknown', $report['items'][3]['origin'] );
		self::assertCount( 1, $report['unsupported'] );
		self::assertSame( 'content_non_classifiable_reference', $report['unsupported'][0]['code'] );
	}

	/**
	 * Test raw local uploads URLs can resolve to trusted attachment-backed inventory.
	 *
	 * @return void
	 */
	public function test_raw_local_uploads_urls_can_resolve_to_attachment_backed_inventory(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[56] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Landing page',
			'body'   => '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="1200" alt="Hero"><img src="https://example.test/wp-content/uploads/2026/07/unregistered.jpg" alt="Unregistered">',
		);
		$meta                 = new FakeAttachmentMetaStore();
		$meta->meta[321][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp' ),
			'excluded' => false,
		);
		$resolver                                        = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function ( string $url ): int {
				return 'https://example.test/wp-content/uploads/2026/07/hero.jpg' === $url ? 321 : 0;
			}
		);

		$report = $this->service( $runtime, $meta, null, $resolver )->report( 56 )->to_array();

		self::assertSame( 2, $report['summary']['total_items'] );
		self::assertSame( 1, $report['summary']['by_origin']['local_attachment'] );
		self::assertSame( 1, $report['summary']['by_origin']['local_unregistered_url'] );
		self::assertSame( 321, $report['items'][0]['attachment_id'] );
		self::assertSame( 'resolved_upload_url', $report['items'][0]['evidence']['marker'] );
		self::assertSame( 'resolved_upload_url', $report['items'][0]['evidence']['url_resolution_code'] );
		self::assertSame( '2026/07/hero.jpg', $report['items'][0]['evidence']['resolved_relative_path'] );
		self::assertSame( 'local_unregistered_url', $report['items'][1]['origin'] );
		self::assertSame( 'unresolved', $report['items'][1]['evidence']['url_resolution_code'] );
	}

	/**
	 * Test Elementor structured backgrounds are inventoried and unsupported cases are preserved.
	 *
	 * @return void
	 */
	public function test_elementor_background_inventory_uses_existing_discovery_results(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[77] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Elementor page',
			'body'   => '',
		);
		$meta                 = new FakeAttachmentMetaStore();
		$meta->meta[901][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'partial',
			'formats'  => array( 'webp' ),
			'excluded' => true,
		);
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-classic-responsive.php' ) );

		$report = $this->service( $runtime, $meta, $store )->report( 77 )->to_array();

		self::assertTrue( $report['content']['has_elementor_document'] );
		self::assertCount( 3, $report['items'] );
		self::assertSame( 'background', $report['items'][0]['presentation'] );
		self::assertSame( 'tablet', $report['items'][1]['evidence']['device'] );
		self::assertSame( 'mobile', $report['items'][2]['evidence']['device'] );
	}

	/**
	 * Test unsupported Elementor cases are labeled safely.
	 *
	 * @return void
	 */
	public function test_unsupported_elementor_cases_are_reported_safely(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[88] = array(
			'type'   => 'page',
			'status' => 'publish',
			'title'  => 'Elementor page',
			'body'   => '',
		);
		$store                = new FakeElementorDocumentDataStore();
		$store->document      = ElementorDocumentData::valid( $this->fixture_elements( 'background-url-only.php' ) );

		$report = $this->service( $runtime, new FakeAttachmentMetaStore(), $store )->report( 88 )->to_array();

		self::assertCount( 1, $report['unsupported'] );
		self::assertSame( 'elementor_unsupported_background_value', $report['unsupported'][0]['code'] );
		self::assertSame( 'elementor_background', $report['unsupported'][0]['source'] );
	}

	/**
	 * Test WooCommerce product media is inventoried in stable order.
	 *
	 * @return void
	 */
	public function test_product_featured_and_gallery_media_are_inventoried(): void {
		$runtime              = new FakeContentInventoryRuntime();
		$runtime->content[99] = array(
			'type'              => 'product',
			'status'            => 'publish',
			'title'             => 'Sample product',
			'body'              => '',
			'featured_image_id' => 501,
			'gallery_ids'       => array( 502, 503 ),
		);

		$report = $this->service( $runtime, new FakeAttachmentMetaStore() )->report( 99 )->to_array();

		self::assertTrue( $report['content']['is_woo_product'] );
		self::assertCount( 3, $report['items'] );
		self::assertSame( 'woocommerce_featured_image', $report['items'][0]['source'] );
		self::assertSame( 1, $report['items'][1]['evidence']['gallery_index'] );
		self::assertSame( 2, $report['items'][2]['evidence']['gallery_index'] );
	}

	/**
	 * Build the service under test.
	 *
	 * @param FakeContentInventoryRuntime|null    $runtime Runtime.
	 * @param FakeAttachmentMetaStore|null        $meta Meta store.
	 * @param FakeElementorDocumentDataStore|null $store Elementor store.
	 * @param LocalUploadAttachmentResolver|null  $local_uploads Local uploads resolver.
	 * @return ContentInventoryService
	 */
	private function service(
		?FakeContentInventoryRuntime $runtime = null,
		?FakeAttachmentMetaStore $meta = null,
		?FakeElementorDocumentDataStore $store = null,
		?LocalUploadAttachmentResolver $local_uploads = null
	): ContentInventoryService {
		$runtime = $runtime ?? new FakeContentInventoryRuntime();
		$meta    = $meta ?? new FakeAttachmentMetaStore();
		$store   = $store ?? new FakeElementorDocumentDataStore();

		return new ContentInventoryService(
			$runtime,
			new AttachmentStatusReader( $meta ),
			$store,
			new ElementorBackgroundDiscovery( $store ),
			new TrustedAttachmentMarkerParser(),
			$local_uploads
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
