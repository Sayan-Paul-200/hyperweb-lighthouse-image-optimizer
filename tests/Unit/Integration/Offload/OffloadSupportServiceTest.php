<?php
/**
 * Tests for the offload support service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadAttachmentSupport;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Multisite\FakeSiteContextRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies offload support caching is site-affine.
 */
final class OffloadSupportServiceTest extends TestCase {

	/**
	 * Test attachment support cache is reset after a site switch.
	 *
	 * @return void
	 */
	public function test_attachment_support_cache_is_flushed_after_site_switch(): void {
		$runtime                               = new FakeWpOffloadMediaRuntime();
		$runtime->active_plugins               = array( WpOffloadMediaAdapter::PLUGIN_BASENAME );
		$runtime->attachment_urls[10]          = 'https://example.test/wp-content/uploads/2026/07/hero.jpg';
		$runtime->metadata[10]                 = array( 'file' => '2026/07/hero.jpg' );
		$runtime->attached_files[10]           = 'C:/site/wp-content/uploads/2026/07/hero.jpg';
		$runtime->existing_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;
		$runtime->readable_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;

		$site_context = new FakeSiteContextRuntime();
		$service      = new OffloadSupportService(
			new WpOffloadMediaAdapter(
				$runtime,
				new FakeImageFileProbe(),
				new DerivativeManifestSanitizer()
			),
			$site_context
		);

		self::assertSame( OffloadAttachmentSupport::MODE_LOCAL_NATIVE, $service->attachment_support( 10 )->mode() );

		$site_context->current_site_id = 2;
		$runtime->attachment_urls[10]  = 'https://cdn.example.test/uploads/2026/07/hero.jpg';

		self::assertSame( OffloadAttachmentSupport::MODE_OFFLOADED_KEEP_LOCAL, $service->attachment_support( 10 )->mode() );
	}
}
