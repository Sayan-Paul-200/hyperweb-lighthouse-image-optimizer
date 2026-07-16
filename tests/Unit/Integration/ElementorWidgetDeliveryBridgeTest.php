<?php
/**
 * Tests for Elementor widget delivery bridge.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryImageTransformer;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\LoadingAttributeManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\MarkupEligibility;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderer;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Delivery\TransformedMarkupRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetDeliveryBridge;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies safe static Elementor widget content delivery.
 */
final class ElementorWidgetDeliveryBridgeTest extends TestCase {

	private const ATTACHMENT_ID = 6545;
	private const UPLOADS       = 'C:/site/wp-content/uploads';

	/**
	 * Clean up filters.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test hook registration adds only Elementor widget render-content filter.
	 *
	 * @return void
	 */
	public function test_registers_only_elementor_widget_render_content_filter(): void {
		$hooks = new HookRegistrar();

		$this->bridge()->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 1, $hooks->filters() );
		self::assertSame( 'elementor/widget/render_content', $hooks->filters()[0]['hook'] );
		self::assertSame( 10, $hooks->filters()[0]['priority'] );
		self::assertSame( 2, $hooks->filters()[0]['accepted_args'] );
	}

	/**
	 * Test actual-page style static image widget renders picture and preserves wrapper.
	 *
	 * @return void
	 */
	public function test_static_image_widget_renders_picture_and_preserves_wrapper(): void {
		$content = '<div class="elementor-widget-container"><a href="https://example.test/shop/"><img width="198" height="202" src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="attachment-large size-large wp-image-6545" alt="" data-custom="keep" /></a></div>';

		$result = $this->bridge()->filter_widget_content( $content, new FakeElementorWidget( 'image' ) );

		self::assertStringContainsString( '<div class="elementor-widget-container"><a href="https://example.test/shop/">', $result );
		self::assertStringContainsString( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( '<img width="198" height="202" src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="attachment-large size-large wp-image-6545" alt="" data-custom="keep" />', $result );
		self::assertStringEndsWith( '</a></div>', $result );
	}

	/**
	 * Test markerless local uploads URL can render through resolver.
	 *
	 * @return void
	 */
	public function test_markerless_local_uploads_url_can_render_through_resolver(): void {
		$content = '<div class="elementor-widget-container"><img width="198" height="202" src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="attachment-large size-large" alt=""></div>';

		$result = $this->bridge()->filter_widget_content( $content, new FakeElementorWidget( 'image-box' ) );

		self::assertStringContainsString( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( 'class="attachment-large size-large"', $result );
	}

	/**
	 * Test excluded, editor, preview, multi-image, unsupported, and missing-derivative cases remain unchanged.
	 *
	 * @return void
	 */
	public function test_unsafe_or_unsupported_cases_remain_unchanged(): void {
		$gallery = '<div><img width="198" height="202" src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="e-gallery-image wp-image-6545" alt=""></div>';
		self::assertSame( $gallery, $this->bridge()->filter_widget_content( $gallery, new FakeElementorWidget( 'image' ) ) );

		$multi = '<div><img src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="wp-image-6545"><img src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="wp-image-6545"></div>';
		self::assertSame( $multi, $this->bridge()->filter_widget_content( $multi, new FakeElementorWidget( 'image' ) ) );

		$unsupported = '<div><img width="198" height="202" src="https://example.test/wp-content/uploads/2026/04/Group-3.png" class="attachment-large size-large wp-image-6545" alt=""></div>';
		self::assertSame( $unsupported, $this->bridge()->filter_widget_content( $unsupported, new FakeElementorWidget( 'gallery' ) ) );

		$editor_runtime              = new FakeElementorRuntime();
		$editor_runtime->editor_mode = true;
		self::assertSame( $unsupported, $this->bridge( $editor_runtime )->filter_widget_content( $unsupported, new FakeElementorWidget( 'image' ) ) );

		self::assertSame( $unsupported, $this->bridge( null, false )->filter_widget_content( $unsupported, new FakeElementorWidget( 'image' ) ) );
	}

	/**
	 * Build bridge.
	 *
	 * @param FakeElementorRuntime|null $elementor_runtime Elementor runtime.
	 * @param bool                      $with_derivatives Whether derivatives exist.
	 * @return ElementorWidgetDeliveryBridge
	 */
	private function bridge( ?FakeElementorRuntime $elementor_runtime = null, bool $with_derivatives = true ): ElementorWidgetDeliveryBridge {
		$elementor_runtime                            = $elementor_runtime ?? new FakeElementorRuntime();
		$settings                                     = new FakeSettingsRepository( array( 'delivery_enabled' => true ) );
		$runtime                                      = new FakeAttachmentImageRuntime();
		$runtime->metadata_map[ self::ATTACHMENT_ID ] = $this->image_meta();
		$uploads                                      = new FakeUploadsUrlRuntime();
		$uploads->base_url                            = 'https://example.test/wp-content/uploads';
		$uploads->base_dir                            = self::UPLOADS;
		$store                                        = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $with_derivatives ? $this->stored_manifest() : array();
		$analyzer      = new WordPressImageMarkupAnalyzer();
		$sanitizer     = new DerivativeManifestSanitizer();
		$resolver      = new AttachmentSizeResolver( $sanitizer );
		$markers       = new TrustedAttachmentMarkerParser();
		$local_uploads = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function ( string $url ): int {
				return false !== strpos( $url, 'Group-3.png' ) ? self::ATTACHMENT_ID : 0;
			},
			$markers
		);
		$transformer   = new DeliveryImageTransformer(
			$settings,
			new MarkupEligibility( $settings, $runtime, $analyzer ),
			new AttachmentImageSourceExtractor( $analyzer ),
			new SourceSetBuilder(
				new DerivativeRepository(
					$store,
					$sanitizer,
					new FixedAttachmentClock( 1783526500 )
				),
				new DerivativeUrlResolver( $uploads, $sanitizer ),
				$uploads,
				$this->probe_with_derivatives( $with_derivatives ),
				$sanitizer,
				$resolver
			),
			new PictureRenderer( $analyzer ),
			new TransformedMarkupRegistry(),
			new IntrinsicDimensionRepair( $resolver, $analyzer ),
			new LoadingAttributeManager(
				$settings,
				new CriticalImageRegistry( $runtime, $settings, new FakeCriticalImagePostMetaStore() ),
				$runtime,
				$analyzer
			)
		);

		return new ElementorWidgetDeliveryBridge(
			$elementor_runtime,
			$runtime,
			new ElementorWidgetMatcher( $elementor_runtime, $analyzer ),
			$transformer,
			$markers,
			$local_uploads
		);
	}

	/**
	 * Build image metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function image_meta(): array {
		return array(
			'file'   => '2026/04/Group-3.png',
			'width'  => 198,
			'height' => 202,
			'sizes'  => array(),
		);
	}

	/**
	 * Build derivative probe.
	 *
	 * @param bool $with_derivatives Whether derivatives exist.
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives( bool $with_derivatives ): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );

		if ( $with_derivatives ) {
			$probe->add_file( self::UPLOADS . '/2026/04/Group-3.png.hwlio.webp', 2200, 100, 'image/webp', 198, 202 );
		}

		return $probe;
	}

	/**
	 * Build stored manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function stored_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/04/Group-3.png',
				'file_size'     => 7555,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'b', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/04/Group-3.png',
						'width'  => 198,
						'height' => 202,
						'mime'   => 'image/png',
						'bytes'  => 7555,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/04/Group-3.png.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 2200,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			),
		);
	}
}
