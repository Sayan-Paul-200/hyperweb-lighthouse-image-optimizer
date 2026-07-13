<?php
/**
 * Tests for responsive source-set building.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuildRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuildResult;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies modern responsive source mapping from WordPress candidates.
 */
final class SourceSetBuilderTest extends TestCase {

	private const ATTACHMENT_ID = 123;
	private const UPLOADS       = 'C:/site/wp-content/uploads';

	/**
	 * Test full-size and subsize candidates map to WebP and AVIF derivatives.
	 *
	 * @return void
	 */
	public function test_full_and_subsize_candidates_map_to_webp_and_avif_derivatives(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), $this->image_meta() )
		);

		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_BUILT ) );
		self::assertSame(
			array( 150, 2400 ),
			array_keys( $result->format( 'webp' )->sources() )
		);
		self::assertSame(
			array( 150, 2400 ),
			array_keys( $result->format( 'avif' )->sources() )
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg.hwlio.webp 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp 2400w',
			$result->format( 'webp' )->srcset()
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg.hwlio.avif 150w, https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif 2400w',
			$result->format( 'avif' )->srcset()
		);
	}

	/**
	 * Test missing derivative files are omitted from the built formats.
	 *
	 * @return void
	 */
	public function test_missing_derivative_files_are_omitted(): void {
		$probe = $this->probe_with_derivatives( false );
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), $this->image_meta() )
		);

		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED ) );
		self::assertSame( array( 150 ), array_keys( $result->format( 'webp' )->sources() ) );
		self::assertSame( array( 150 ), array_keys( $result->format( 'avif' )->sources() ) );
	}

	/**
	 * Test invalid manifest entries are ignored through repository sanitization.
	 *
	 * @return void
	 */
	public function test_invalid_manifest_entries_are_ignored_through_repository_sanitization(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->dirty_manifest();

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), $this->image_meta() )
		);

		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_BUILT ) );
		self::assertNull( $result->format( 'gif' ) );
		self::assertSame( array( 150, 2400 ), array_keys( $result->format( 'webp' )->sources() ) );
		self::assertSame( array( 150 ), array_keys( $result->format( 'avif' )->sources() ) );
	}

	/**
	 * Test unsupported descriptors and malformed original candidates are skipped.
	 *
	 * @return void
	 */
	public function test_unsupported_descriptors_and_malformed_original_candidates_are_skipped(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();
		$sources = array(
			2400 => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero.jpg',
				'descriptor' => 'w',
				'value'      => 2400,
			),
			'bad-x' => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero.jpg',
				'descriptor' => 'x',
				'value'      => 2,
			),
			'bad-url' => array(
				'descriptor' => 'w',
				'value'      => 150,
			),
			'dup-width' => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg',
				'descriptor' => 'w',
				'value'      => 2400,
			),
		);

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $sources, $this->image_meta() )
		);

		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED ) );
		self::assertSame( array( 2400 ), array_keys( $result->format( 'webp' )->sources() ) );
	}

	/**
	 * Test unmatched candidates are skipped and widths are never invented.
	 *
	 * @return void
	 */
	public function test_unmatched_candidates_are_skipped_and_widths_are_never_invented(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();
		$sources = array(
			2400 => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero.jpg',
				'descriptor' => 'w',
				'value'      => 2400,
			),
			999 => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/not-generated.jpg',
				'descriptor' => 'w',
				'value'      => 999,
			),
		);

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $sources, $this->image_meta() )
		);

		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED ) );
		self::assertSame( array( 2400 ), array_keys( $result->format( 'webp' )->sources() ) );
		self::assertArrayNotHasKey( 999, $result->format( 'webp' )->sources() );
	}

	/**
	 * Test empty or invalid format results are omitted cleanly.
	 *
	 * @return void
	 */
	public function test_format_output_is_omitted_cleanly_when_no_valid_candidates_remain(): void {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), $this->image_meta() )
		);

		self::assertSame( array(), $result->formats() );
		self::assertFalse( $result->has_code( SourceSetBuildResult::CODE_BUILT ) );
		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED ) );
	}

	/**
	 * Test invalid image metadata returns the expected failure code.
	 *
	 * @return void
	 */
	public function test_invalid_image_meta_returns_the_expected_failure_code(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		$result = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), array() )
		);

		self::assertSame( array(), $result->formats() );
		self::assertTrue( $result->has_code( SourceSetBuildResult::CODE_INVALID_IMAGE_META ) );
	}

	/**
	 * Test result serialization emits width-keyed source arrays and no absolute paths.
	 *
	 * @return void
	 */
	public function test_result_serialization_emits_width_keyed_sources_without_absolute_paths(): void {
		$probe = $this->probe_with_derivatives();
		$store = new FakeAttachmentMetaStore();
		$store->meta[ self::ATTACHMENT_ID ][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest();

		$payload = $this->builder( $store, $probe )->build(
			new SourceSetBuildRequest( self::ATTACHMENT_ID, $this->original_sources(), $this->image_meta() )
		)->to_array();

		self::assertSame( array( 150, 2400 ), array_keys( $payload['formats']['webp']['sources'] ) );
		self::assertStringNotContainsString( self::UPLOADS, json_encode( $payload ) ?: '' );
	}

	/**
	 * Build builder.
	 *
	 * @param FakeAttachmentMetaStore $store Store.
	 * @param FakeImageFileProbe      $probe Probe.
	 * @return SourceSetBuilder
	 */
	private function builder( FakeAttachmentMetaStore $store, FakeImageFileProbe $probe ): SourceSetBuilder {
		$runtime = new FakeUploadsUrlRuntime();
		$runtime->base_url = 'https://example.test/wp-content/uploads';
		$runtime->base_dir = self::UPLOADS;

		return new SourceSetBuilder(
			new DerivativeRepository(
				$store,
				new DerivativeManifestSanitizer(),
				new FixedAttachmentClock( 1783526500 )
			),
			new DerivativeUrlResolver( $runtime, new DerivativeManifestSanitizer() ),
			$runtime,
			$probe,
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Build a probe with current derivative files.
	 *
	 * @param bool $include_full Whether to include full-size derivatives.
	 * @return FakeImageFileProbe
	 */
	private function probe_with_derivatives( bool $include_full = true ): FakeImageFileProbe {
		$probe = new FakeImageFileProbe( array( self::UPLOADS ) );

		if ( $include_full ) {
			$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 300, 100, 'image/webp', 2400, 1600 );
			$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif', 220, 100, 'image/avif', 2400, 1600 );
		}

		$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.webp', 25, 100, 'image/webp', 150, 100 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg.hwlio.avif', 20, 100, 'image/avif', 150, 100 );

		return $probe;
	}

	/**
	 * Build original WordPress sources.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function original_sources(): array {
		return array(
			150  => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg',
				'descriptor' => 'w',
				'value'      => 150,
			),
			2400 => array(
				'url'        => 'https://example.test/wp-content/uploads/2026/07/hero.jpg',
				'descriptor' => 'w',
				'value'      => 2400,
			),
		);
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
	 * Build stored manifest.
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

	/**
	 * Build a dirty manifest with tampered entries.
	 *
	 * @return array<string,mixed>
	 */
	private function dirty_manifest(): array {
		$manifest = $this->stored_manifest();

		$manifest['sizes']['full']['formats']['gif'] = array(
			'file'   => '2026/07/hero.jpg.hwlio.gif',
			'mime'   => 'image/gif',
			'status' => 'ready',
		);
		$manifest['sizes']['thumbnail']['formats']['avif'] = array(
			'file'   => 'https://example.test/hero-150x100.jpg.hwlio.avif',
			'mime'   => 'image/avif',
			'status' => 'ready',
		);

		return $manifest;
	}
}
