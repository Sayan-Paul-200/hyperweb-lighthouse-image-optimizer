<?php
/**
 * Tests for attachment image delivery manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\MarkupEligibility;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderer;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Delivery\TransformedMarkupRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies active attachment-image delivery orchestration.
 */
final class DeliveryManagerTest extends TestCase {

	private const ATTACHMENT_ID = 123;
	private const UPLOADS       = 'C:/site/wp-content/uploads';

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test hook registration adds only the attachment and post-content image hooks.
	 *
	 * @return void
	 */
	public function test_hook_registration_adds_only_attachment_and_post_content_image_hooks(): void {
		$hooks   = new HookRegistrar();
		$runtime = $this->runtime();

		$this->manager( $runtime )->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 2, $hooks->filters() );
		self::assertSame( 'wp_get_attachment_image', $hooks->filters()[0]['hook'] );
		self::assertSame( 10, $hooks->filters()[0]['priority'] );
		self::assertSame( 5, $hooks->filters()[0]['accepted_args'] );
		self::assertSame( 'wp_content_img_tag', $hooks->filters()[1]['hook'] );
		self::assertSame( 10, $hooks->filters()[1]['priority'] );
		self::assertSame( 3, $hooks->filters()[1]['accepted_args'] );
	}

	/**
	 * Test disabled delivery returns the original markup unchanged.
	 *
	 * @return void
	 */
	public function test_disabled_delivery_returns_the_original_markup_unchanged(): void {
		$html    = $this->img_html();
		$runtime = $this->runtime();
		$result  = $this->manager( $runtime, new FakeSettingsRepository( array( 'delivery_enabled' => false ) ) )
			->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertSame( $html, $result );
	}

	/**
	 * Test emergency delivery rollback returns original markup even when normal delivery is enabled.
	 *
	 * @return void
	 */
	public function test_emergency_delivery_rollback_returns_the_original_markup_unchanged(): void {
		$html     = $this->img_html();
		$settings = new FakeSettingsRepository(
			array(
				'delivery_enabled'            => true,
				'delivery_emergency_disabled' => true,
			)
		);

		$result = $this->manager( $this->runtime(), $settings )->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertSame( $html, $result );
	}

	/**
	 * Test delivery filter cannot bypass the emergency rollback switch.
	 *
	 * @return void
	 */
	public function test_delivery_filter_cannot_bypass_the_emergency_rollback_switch(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_delivery_is_enabled' => static function (): bool {
				return true;
			},
		);

		$html     = $this->img_html();
		$settings = new FakeSettingsRepository(
			array(
				'delivery_enabled'            => false,
				'delivery_emergency_disabled' => true,
			)
		);

		$result = $this->manager( $this->runtime(), $settings )->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertSame( $html, $result );
	}

	/**
	 * Test delivery filter can disable transformation even when settings enable it.
	 *
	 * @return void
	 */
	public function test_delivery_filter_can_disable_transformation_even_when_settings_enable_it(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_delivery_is_enabled' => static function ( bool $enabled, int $attachment_id, string $html, array $context ): bool {
				TestCase::assertTrue( $enabled );
				TestCase::assertSame( self::ATTACHMENT_ID, $attachment_id );
				TestCase::assertSame( 'full', $context['size'] );
				TestCase::assertSame( '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w" sizes="100vw" width="2400" height="1600" alt="Hero" loading="lazy">', $html );

				return false;
			},
		);

		$html   = $this->img_html();
		$result = $this->manager( $this->runtime() )->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertSame( $html, $result );
	}

	/**
	 * Test non-image attachments and icon requests return original markup.
	 *
	 * @return void
	 */
	public function test_non_image_attachments_and_icon_requests_return_original_markup(): void {
		$html          = $this->img_html();
		$non_image     = $this->runtime();
		$non_image->is_image = false;
		$manager       = $this->manager( $non_image );

		self::assertSame( $html, $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() ) );
		self::assertSame( $html, $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', true, array() ) );
	}

	/**
	 * Test admin, feed, AJAX, and REST contexts return the original markup unchanged.
	 *
	 * @return void
	 */
	public function test_admin_feed_ajax_and_rest_contexts_return_the_original_markup_unchanged(): void {
		$html = $this->img_html();

		foreach ( array( 'is_admin', 'is_feed', 'is_ajax', 'is_rest' ) as $flag ) {
			$runtime = $this->runtime();
			$runtime->request_context[ $flag ] = true;
			$manager = $this->manager( $runtime );

			self::assertSame( $html, $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() ), $flag );
		}
	}

	/**
	 * Test eligibility filter can veto transformation.
	 *
	 * @return void
	 */
	public function test_eligibility_filter_can_veto_transformation(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_markup_is_eligible' => static function ( bool $eligible ): bool {
				TestCase::assertTrue( $eligible );

				return false;
			},
		);

		$html   = $this->img_html();
		$result = $this->manager( $this->runtime() )->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertSame( $html, $result );
	}

	/**
	 * Test valid attachment image markup renders picture markup using configured format order.
	 *
	 * @return void
	 */
	public function test_valid_attachment_image_markup_renders_picture_markup_using_configured_format_order(): void {
		$runtime  = $this->runtime();
		$settings = new FakeSettingsRepository(
			array(
				'delivery_enabled'  => true,
				'format_preference' => array( 'webp', 'avif' ),
			)
		);
		$result   = $this->manager( $runtime, $settings )->filter_attachment_image(
			$this->img_html(),
			self::ATTACHMENT_ID,
			'full',
			false,
			array()
		);

		self::assertStringStartsWith( '<picture class="hwlio-picture"><source type="image/webp"', $result );
		self::assertStringContainsString( $this->img_html(), $result );
		self::assertStringContainsString( '<source type="image/avif"', $result );
		self::assertStringNotContainsString( self::UPLOADS, $result );
	}

	/**
	 * Test missing valid derivatives returns the original markup unchanged.
	 *
	 * @return void
	 */
	public function test_missing_valid_derivatives_returns_the_original_markup_unchanged(): void {
		$html    = $this->img_html();
		$runtime = $this->runtime();
		$manager = $this->manager( $runtime, null, false );

		self::assertSame( $html, $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() ) );
	}

	/**
	 * Test malformed HTML returns the original markup unchanged.
	 *
	 * @return void
	 */
	public function test_malformed_html_returns_the_original_markup_unchanged(): void {
		$html    = '<span>Hero</span><img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">';
		$runtime = $this->runtime();
		$manager = $this->manager( $runtime );

		self::assertSame( $html, $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() ) );
	}

	/**
	 * Test request-local duplicate signatures prevent second-pass wrapping.
	 *
	 * @return void
	 */
	public function test_request_local_duplicate_signatures_prevent_second_pass_wrapping(): void {
		$html    = $this->img_html();
		$runtime = $this->runtime();
		$manager = $this->manager( $runtime );
		$first   = $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );
		$second  = $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );

		self::assertStringStartsWith( '<picture', $first );
		self::assertSame( $html, $second );
	}

	/**
	 * Test content images with valid attachments render picture markup and preserve the fallback image verbatim.
	 *
	 * @return void
	 */
	public function test_content_images_with_valid_attachments_render_picture_markup_and_preserve_fallback_image(): void {
		$html    = '<img class="wp-image-123 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w" sizes="100vw" width="2400" height="1600" alt="Hero" loading="lazy" fetchpriority="high" decoding="async" data-id="123">';
		$runtime = $this->runtime();
		$result  = $this->manager( $runtime )->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID );

		self::assertStringStartsWith( '<picture class="hwlio-picture">', $result );
		self::assertStringContainsString( $html, $result );
		self::assertStringContainsString( '<source type="image/avif"', $result );
		self::assertStringContainsString( '<source type="image/webp"', $result );
		self::assertStringContainsString( 'fetchpriority="high"', $result );
		self::assertStringContainsString( 'decoding="async"', $result );
	}

	/**
	 * Test unresolved content images remain unchanged.
	 *
	 * @return void
	 */
	public function test_unresolved_content_images_remain_unchanged(): void {
		$html    = '<img class="alignnone size-full" src="https://cdn.example.test/hero.jpg" width="1200" alt="Hero">';
		$runtime = $this->runtime();

		self::assertSame( $html, $this->manager( $runtime )->filter_content_img_tag( $html, 'the_content', 0 ) );
	}

	/**
	 * Test content images without valid derivatives remain unchanged.
	 *
	 * @return void
	 */
	public function test_content_images_without_valid_derivatives_remain_unchanged(): void {
		$html    = '<img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">';
		$runtime = $this->runtime();

		self::assertSame( $html, $this->manager( $runtime, null, false )->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID ) );
	}

	/**
	 * Test malformed content image markup remains unchanged.
	 *
	 * @return void
	 */
	public function test_malformed_content_image_markup_remains_unchanged(): void {
		$html    = '<span class="wp-image-123">Hero</span><img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">';
		$runtime = $this->runtime();

		self::assertSame( $html, $this->manager( $runtime )->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID ) );
	}

	/**
	 * Test content-image filters receive the richer context payload.
	 *
	 * @return void
	 */
	public function test_content_image_filters_receive_the_richer_context_payload(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_delivery_is_enabled' => static function ( bool $enabled, int $attachment_id, string $html, array $context ): bool {
				TestCase::assertTrue( $enabled );
				TestCase::assertSame( self::ATTACHMENT_ID, $attachment_id );
				TestCase::assertSame( 'wp_content_img_tag', $context['hook'] );
				TestCase::assertSame( 'the_content', $context['content_context'] );
				TestCase::assertNull( $context['size'] );
				TestCase::assertFalse( $context['icon'] );
				TestCase::assertSame( array(), $context['attr'] );
				TestCase::assertSame( '<img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">', $html );

				return false;
			},
		);

		$html = '<img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">';

		self::assertSame( $html, $this->manager( $this->runtime() )->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID ) );
	}

	/**
	 * Test content-image eligibility filter can veto transformation with content-hook context.
	 *
	 * @return void
	 */
	public function test_content_image_eligibility_filter_can_veto_transformation_with_content_hook_context(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_markup_is_eligible' => static function ( bool $eligible, int $attachment_id, string $html, array $context ): bool {
				TestCase::assertTrue( $eligible );
				TestCase::assertSame( self::ATTACHMENT_ID, $attachment_id );
				TestCase::assertSame( 'wp_content_img_tag', $context['hook'] );
				TestCase::assertSame( 'the_content', $context['content_context'] );
				TestCase::assertSame( '<img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">', $html );

				return false;
			},
		);

		$html = '<img class="wp-image-123 size-full" src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">';

		self::assertSame( $html, $this->manager( $this->runtime() )->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID ) );
	}

	/**
	 * Test shared request-local duplicate protection works across the attachment and content hook paths.
	 *
	 * @return void
	 */
	public function test_shared_request_local_duplicate_protection_works_across_hook_paths(): void {
		$html    = $this->img_html();
		$runtime = $this->runtime();
		$manager = $this->manager( $runtime );
		$first   = $manager->filter_attachment_image( $html, self::ATTACHMENT_ID, 'full', false, array() );
		$second  = $manager->filter_content_img_tag( $html, 'the_content', self::ATTACHMENT_ID );

		self::assertStringStartsWith( '<picture', $first );
		self::assertSame( $html, $second );
	}

	/**
	 * Build manager.
	 *
	 * @param FakeAttachmentImageRuntime $runtime Runtime seam.
	 * @param FakeSettingsRepository|null $settings Settings repository.
	 * @param bool                       $with_derivatives Whether derivatives exist.
	 * @return DeliveryManager
	 */
	private function manager(
		FakeAttachmentImageRuntime $runtime,
		?FakeSettingsRepository $settings = null,
		bool $with_derivatives = true
	): DeliveryManager {
		$settings = $settings ?? new FakeSettingsRepository( array( 'delivery_enabled' => true ) );
		$runtime->metadata = $this->image_meta();
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_url = 'https://example.test/wp-content/uploads';
		$uploads->base_dir = self::UPLOADS;
		$store             = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $with_derivatives ? $this->stored_manifest() : array();
		$analyzer          = new WordPressImageMarkupAnalyzer();

		return new DeliveryManager(
			$settings,
			$runtime,
			new MarkupEligibility( $settings, $runtime, $analyzer ),
			new AttachmentImageSourceExtractor( $analyzer ),
			new SourceSetBuilder(
				new DerivativeRepository(
					$store,
					new DerivativeManifestSanitizer(),
					new FixedAttachmentClock( 1783526500 )
				),
				new DerivativeUrlResolver( $uploads, new DerivativeManifestSanitizer() ),
				$uploads,
				$this->probe_with_derivatives( $with_derivatives ),
				new DerivativeManifestSanitizer()
			),
			new PictureRenderer( $analyzer ),
			new TransformedMarkupRegistry()
		);
	}

	/**
	 * Build runtime seam.
	 *
	 * @return FakeAttachmentImageRuntime
	 */
	private function runtime(): FakeAttachmentImageRuntime {
		$runtime           = new FakeAttachmentImageRuntime();
		$runtime->metadata = $this->image_meta();

		return $runtime;
	}

	/**
	 * Build a probe with current derivative files.
	 *
	 * @param bool $include_full Whether to include derivatives.
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives( bool $include_full = true ): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );

		if ( $include_full ) {
			$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 300, 100, 'image/webp', 2400, 1600 );
			$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif', 220, 100, 'image/avif', 2400, 1600 );
			$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.webp', 25, 100, 'image/webp', 150, 100 );
			$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.avif', 20, 100, 'image/avif', 150, 100 );
		}

		return $probe;
	}

	/**
	 * Build source HTML.
	 *
	 * @return string
	 */
	private function img_html(): string {
		return '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" srcset="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg 2400w" sizes="100vw" width="2400" height="1600" alt="Hero" loading="lazy">';
	}

	/**
	 * Build image metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function image_meta(): array {
		return array(
			'file'   => '2026/07/hero.jpg',
			'width'  => 2400,
			'height' => 1600,
			'sizes'  => array(
				'thumbnail' => array(
					'file'   => 'hero-150x100.jpg',
					'width'  => 150,
					'height' => 100,
				),
			),
		);
	}

	/**
	 * Build stored derivative manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function stored_manifest(): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/hero.jpg',
				'file_size'     => 1000,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'a', 64 ),
			),
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full'      => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 300,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/hero.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 220,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
				'thumbnail' => array(
					'source'  => array(
						'file'   => '2026/07/hero-150x100.jpg',
						'width'  => 150,
						'height' => 100,
						'mime'   => 'image/jpeg',
						'bytes'  => 125,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero-150x100.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 25,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
						'avif' => array(
							'file'         => '2026/07/hero-150x100.jpg.hwlio.avif',
							'mime'         => 'image/avif',
							'bytes'        => 20,
							'quality'      => 68,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			),
		);
	}
}
