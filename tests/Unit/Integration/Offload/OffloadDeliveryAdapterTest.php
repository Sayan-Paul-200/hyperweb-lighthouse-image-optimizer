<?php
/**
 * Tests for the offload delivery adapter provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryHookPolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadDeliveryAdapter;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies delivery URL rewriting stays scoped to supported offloaded attachments.
 */
final class OffloadDeliveryAdapterTest extends TestCase {

	/**
	 * Test provider registration adds only the two delivery URL hooks.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_two_delivery_filters(): void {
		$provider = new OffloadDeliveryAdapter( $this->support_service( new FakeWpOffloadMediaRuntime() ) );
		$hooks    = new HookRegistrar();

		$provider->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 2, $hooks->filters() );
		self::assertSame( DeliveryHookPolicy::FILTER_UPLOADS_BASE_URL, $hooks->filters()[0]['hook'] );
		self::assertSame( 6, $hooks->filters()[0]['accepted_args'] );
		self::assertSame( DeliveryHookPolicy::FILTER_DERIVATIVE_URL, $hooks->filters()[1]['hook'] );
		self::assertSame( 6, $hooks->filters()[1]['accepted_args'] );
	}

	/**
	 * Test local-native attachments keep the existing local delivery URLs.
	 *
	 * @return void
	 */
	public function test_local_native_attachment_keeps_existing_urls(): void {
		$runtime                      = new FakeWpOffloadMediaRuntime();
		$runtime->active_plugins      = array( WpOffloadMediaAdapter::PLUGIN_BASENAME );
		$runtime->attachment_urls[10] = 'https://example.test/wp-content/uploads/2026/07/hero.jpg';
		$runtime->metadata[10]        = array( 'file' => '2026/07/hero.jpg' );
		$provider                     = new OffloadDeliveryAdapter( $this->support_service( $runtime ) );

		self::assertSame(
			'https://example.test/wp-content/uploads',
			$provider->filter_uploads_base_url(
				'https://example.test/wp-content/uploads',
				'2026/07/hero.jpg.hwlio.webp',
				10,
				'full',
				'webp',
				array()
			)
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
			$provider->filter_derivative_url(
				'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
				'2026/07/hero.jpg.hwlio.webp',
				10,
				'full',
				'webp',
				array()
			)
		);
	}

	/**
	 * Test supported offloaded attachments rewrite derivative URLs to the inferred remote base.
	 *
	 * @return void
	 */
	public function test_supported_offloaded_attachment_rewrites_urls(): void {
		$runtime                               = new FakeWpOffloadMediaRuntime();
		$runtime->active_plugins               = array( WpOffloadMediaAdapter::PLUGIN_BASENAME );
		$runtime->attachment_urls[11]          = 'https://cdn.example.test/uploads/2026/07/hero.jpg';
		$runtime->metadata[11]                 = array( 'file' => '2026/07/hero.jpg' );
		$runtime->attached_files[11]           = 'C:/site/wp-content/uploads/2026/07/hero.jpg';
		$runtime->existing_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;
		$runtime->readable_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;
		$provider                              = new OffloadDeliveryAdapter( $this->support_service( $runtime ) );

		self::assertSame(
			'https://cdn.example.test/uploads',
			$provider->filter_uploads_base_url(
				'https://example.test/wp-content/uploads',
				'2026/07/hero.jpg.hwlio.avif',
				11,
				'large',
				'avif',
				array()
			)
		);
		self::assertSame(
			'https://cdn.example.test/uploads/2026/07/hero.jpg.hwlio.avif',
			$provider->filter_derivative_url(
				'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif',
				'2026/07/hero.jpg.hwlio.avif',
				11,
				'large',
				'avif',
				array()
			)
		);
	}

	/**
	 * Test unsupported attachments fail open to the existing local URL flow.
	 *
	 * @return void
	 */
	public function test_unsupported_attachment_fails_open_to_existing_urls(): void {
		$runtime                      = new FakeWpOffloadMediaRuntime();
		$runtime->active_plugins      = array( WpOffloadMediaAdapter::PLUGIN_BASENAME );
		$runtime->attachment_urls[12] = 'https://cdn.example.test/media/hero.jpg';
		$runtime->metadata[12]        = array( 'file' => '2026/07/hero.jpg' );
		$provider                     = new OffloadDeliveryAdapter( $this->support_service( $runtime ) );

		self::assertSame(
			'https://example.test/wp-content/uploads',
			$provider->filter_uploads_base_url(
				'https://example.test/wp-content/uploads',
				'2026/07/hero.jpg.hwlio.webp',
				12,
				'full',
				'webp',
				array()
			)
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
			$provider->filter_derivative_url(
				'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
				'2026/07/hero.jpg.hwlio.webp',
				12,
				'full',
				'webp',
				array()
			)
		);
	}

	/**
	 * Build a support service from one fake runtime.
	 *
	 * @param FakeWpOffloadMediaRuntime $runtime Runtime.
	 * @return OffloadSupportService
	 */
	private function support_service( FakeWpOffloadMediaRuntime $runtime ): OffloadSupportService {
		return new OffloadSupportService(
			new WpOffloadMediaAdapter(
				$runtime,
				new FakeImageFileProbe(),
				new DerivativeManifestSanitizer()
			)
		);
	}
}
