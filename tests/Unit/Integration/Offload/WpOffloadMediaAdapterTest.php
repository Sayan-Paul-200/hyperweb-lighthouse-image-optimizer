<?php
/**
 * Tests for the WP Offload Media adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadAttachmentSupport;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSiteSupport;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative offload support classification.
 */
final class WpOffloadMediaAdapterTest extends TestCase {

	/**
	 * Test inactive environments remain local-native.
	 *
	 * @return void
	 */
	public function test_inactive_environment_returns_local_native_support(): void {
		$runtime                      = new FakeWpOffloadMediaRuntime();
		$runtime->attachment_urls[15] = 'https://example.test/wp-content/uploads/2026/07/hero.jpg';
		$runtime->metadata[15]        = array(
			'file' => '2026/07/hero.jpg',
		);

		$adapter = new WpOffloadMediaAdapter(
			$runtime,
			new FakeImageFileProbe(),
			new DerivativeManifestSanitizer()
		);

		self::assertSame( OffloadSiteSupport::CODE_INACTIVE, $adapter->site_support()->code() );
		self::assertSame( OffloadAttachmentSupport::MODE_LOCAL_NATIVE, $adapter->attachment_support( 15 )->mode() );
		self::assertTrue( $adapter->attachment_support( 15 )->is_supported() );
	}

	/**
	 * Test remote keep-local attachments are classified conservatively when the URL suffix matches metadata.
	 *
	 * @return void
	 */
	public function test_remote_keep_local_attachment_is_supported_when_base_can_be_inferred(): void {
		$runtime                      = $this->active_runtime();
		$runtime->attachment_urls[21] = 'https://cdn.example.test/uploads/2026/07/hero.jpg';
		$runtime->metadata[21]        = array( 'file' => '2026/07/hero.jpg' );
		$runtime->attached_files[21]  = 'C:/site/wp-content/uploads/2026/07/hero.jpg';
		$runtime->existing_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;
		$runtime->readable_files['C:/site/wp-content/uploads/2026/07/hero.jpg'] = true;

		$adapter = new WpOffloadMediaAdapter(
			$runtime,
			new FakeImageFileProbe(),
			new DerivativeManifestSanitizer()
		);
		$support = $adapter->attachment_support( 21 );

		self::assertTrue( $support->is_supported() );
		self::assertTrue( $support->is_offloaded() );
		self::assertSame( OffloadAttachmentSupport::MODE_OFFLOADED_KEEP_LOCAL, $support->mode() );
		self::assertSame( 'https://cdn.example.test/uploads', $support->remote_base_url() );
	}

	/**
	 * Test remote-only attachments are classified when the local source file is gone.
	 *
	 * @return void
	 */
	public function test_remote_only_attachment_is_classified_when_local_source_is_missing(): void {
		$runtime                      = $this->active_runtime();
		$runtime->attachment_urls[22] = 'https://cdn.example.test/uploads/2026/07/hero.jpg';
		$runtime->metadata[22]        = array( 'file' => '2026/07/hero.jpg' );
		$runtime->attached_files[22]  = 'C:/site/wp-content/uploads/2026/07/hero.jpg';

		$adapter = new WpOffloadMediaAdapter(
			$runtime,
			new FakeImageFileProbe(),
			new DerivativeManifestSanitizer()
		);

		self::assertSame(
			OffloadAttachmentSupport::MODE_OFFLOADED_REMOTE_ONLY,
			$adapter->attachment_support( 22 )->mode()
		);
	}

	/**
	 * Test ambiguous remote URL layouts are treated as unsupported instead of guessed.
	 *
	 * @return void
	 */
	public function test_ambiguous_remote_url_is_treated_as_unsupported(): void {
		$runtime                      = $this->active_runtime();
		$runtime->attachment_urls[23] = 'https://cdn.example.test/media/hero.jpg';
		$runtime->metadata[23]        = array( 'file' => '2026/07/hero.jpg' );

		$adapter = new WpOffloadMediaAdapter(
			$runtime,
			new FakeImageFileProbe(),
			new DerivativeManifestSanitizer()
		);
		$support = $adapter->attachment_support( 23 );

		self::assertFalse( $support->is_supported() );
		self::assertSame( OffloadAttachmentSupport::MODE_UNSUPPORTED, $support->mode() );
		self::assertSame( 'offload_remote_base_unresolved', $support->code() );
	}

	/**
	 * Build an active runtime baseline.
	 *
	 * @return FakeWpOffloadMediaRuntime
	 */
	private function active_runtime(): FakeWpOffloadMediaRuntime {
		$runtime                 = new FakeWpOffloadMediaRuntime();
		$runtime->active_plugins = array( WpOffloadMediaAdapter::PLUGIN_BASENAME );

		return $runtime;
	}
}
