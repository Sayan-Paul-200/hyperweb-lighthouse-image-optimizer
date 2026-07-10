<?php
/**
 * Tests for destination resolution.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\DestinationResolutionResult;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationResolver;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies deterministic, uploads-safe sidecar path resolution.
 */
final class DestinationResolverTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test source extension is preserved to avoid sidecar collisions.
	 *
	 * @return void
	 */
	public function test_logo_jpg_and_png_produce_distinct_sidecars(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/logo.jpg' );
		$this->add_source_file( $probe, '2026/07/logo.png' );

		$jpg = $this->resolver( $probe )->resolve( $this->source( '2026/07/logo.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$png = $this->resolver( $probe )->resolve( $this->source( '2026/07/logo.png' ), SourceMimePolicy::TARGET_WEBP );

		self::assertTrue( $jpg->is_resolved() );
		self::assertTrue( $png->is_resolved() );
		self::assertSame( '2026/07/logo.jpg.hwlio.webp', $jpg->destination()->relative_path() );
		self::assertSame( '2026/07/logo.png.hwlio.webp', $png->destination()->relative_path() );
		self::assertNotSame( $jpg->destination()->relative_path(), $png->destination()->relative_path() );
	}

	/**
	 * Test paths preserve uploads subdirectories across source roles.
	 *
	 * @return void
	 */
	public function test_preserves_subdirectories_for_full_subsize_and_original_sources(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );
		$this->add_source_file( $probe, '2026/07/hero-150x100.jpg' );
		$this->add_source_file( $probe, '2026/07/hero-original.jpg' );

		$resolver = $this->resolver( $probe );
		$full     = $resolver->resolve( $this->source( '2026/07/hero.jpg', SourceImage::ROLE_FULL, 'full' ), SourceMimePolicy::TARGET_AVIF );
		$subsize  = $resolver->resolve( $this->source( '2026/07/hero-150x100.jpg', SourceImage::ROLE_SUBSIZE, 'thumbnail' ), SourceMimePolicy::TARGET_AVIF );
		$original = $resolver->resolve( $this->source( '2026/07/hero-original.jpg', SourceImage::ROLE_ORIGINAL, 'original' ), SourceMimePolicy::TARGET_AVIF );

		self::assertSame( '2026/07/hero.jpg.hwlio.avif', $full->destination()->relative_path() );
		self::assertSame( '2026/07/hero-150x100.jpg.hwlio.avif', $subsize->destination()->relative_path() );
		self::assertSame( '2026/07/hero-original.jpg.hwlio.avif', $original->destination()->relative_path() );
	}

	/**
	 * Test target MIME and path mapping.
	 *
	 * @return void
	 */
	public function test_resolves_webp_and_avif_target_mimes_and_paths(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );
		$resolver = $this->resolver( $probe );

		$webp = $resolver->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$avif = $resolver->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_AVIF );

		self::assertSame( 'image/webp', $webp->destination()->target_mime() );
		self::assertSame( 'image/avif', $avif->destination()->target_mime() );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $webp->destination()->relative_path() );
		self::assertSame( '2026/07/hero.jpg.hwlio.avif', $avif->destination()->relative_path() );
	}

	/**
	 * Test repeated resolution is deterministic.
	 *
	 * @return void
	 */
	public function test_repeated_resolution_returns_identical_destination(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );
		$resolver = $this->resolver( $probe );
		$source   = $this->source( '2026/07/hero.jpg' );

		$first  = $resolver->resolve( $source, SourceMimePolicy::TARGET_WEBP );
		$second = $resolver->resolve( $source, SourceMimePolicy::TARGET_WEBP );

		self::assertSame( $first->to_array(), $second->to_array() );
		self::assertSame( $first->destination()->absolute_path(), $second->destination()->absolute_path() );
	}

	/**
	 * Test temporary path shape and location.
	 *
	 * @return void
	 */
	public function test_temporary_path_is_in_destination_directory_and_distinct(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );

		$result      = $this->resolver( $probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$destination = $result->destination();

		self::assertSame( '2026/07/hero.jpg.hwlio.webp.tmp', $destination->temporary_relative_path() );
		self::assertSame( dirname( $destination->relative_path() ), dirname( $destination->temporary_relative_path() ) );
		self::assertSame( dirname( $destination->absolute_path() ), dirname( $destination->temporary_absolute_path() ) );
		self::assertNotSame( $destination->relative_path(), $destination->temporary_relative_path() );
		self::assertNotSame( '2026/07/hero.jpg', $destination->temporary_relative_path() );
	}

	/**
	 * Test invalid target format rejection.
	 *
	 * @return void
	 */
	public function test_invalid_target_format_is_rejected(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );

		$result = $this->resolver( $probe )->resolve( $this->source( '2026/07/hero.jpg' ), 'gif' );

		self::assertFalse( $result->is_resolved() );
		self::assertSame( DestinationResolutionResult::CODE_INVALID_TARGET_FORMAT, $result->code() );
	}

	/**
	 * Test unsafe source relative paths are rejected.
	 *
	 * @return void
	 */
	public function test_unsafe_source_paths_are_rejected(): void {
		$paths = array(
			'../evil.jpg',
			'2026//07/hero.jpg',
			'https://example.com/evil.jpg',
			"2026/07/bad\0file.jpg",
			'C:/outside/evil.jpg',
		);

		foreach ( $paths as $path ) {
			$probe  = $this->probe();
			$result = $this->resolver( $probe )->resolve(
				$this->source( $path, SourceImage::ROLE_FULL, 'full', self::UPLOADS . '/2026/07/hero.jpg' ),
				SourceMimePolicy::TARGET_WEBP
			);

			self::assertSame( DestinationResolutionResult::CODE_UNSAFE_SOURCE_PATH, $result->code(), $path );
		}
	}

	/**
	 * Test source realpaths outside uploads are rejected.
	 *
	 * @return void
	 */
	public function test_source_realpath_outside_uploads_is_rejected(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 1000, 100, 'image/jpeg', 100, 100, true, true, 'D:/outside/hero.jpg' );

		$result = $this->resolver( $probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );

		self::assertSame( DestinationResolutionResult::CODE_SOURCE_OUTSIDE_UPLOADS, $result->code() );
	}

	/**
	 * Test resolved destination and temporary paths are inside uploads.
	 *
	 * @return void
	 */
	public function test_destination_and_temporary_paths_remain_inside_uploads(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );

		$result      = $this->resolver( $probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$destination = $result->destination();

		self::assertStringStartsWith( self::UPLOADS . '/', $destination->absolute_path() );
		self::assertStringStartsWith( self::UPLOADS . '/', $destination->temporary_absolute_path() );
	}

	/**
	 * Test existing destination or temporary realpaths outside uploads are rejected.
	 *
	 * @return void
	 */
	public function test_existing_destination_or_temporary_realpath_outside_uploads_is_rejected(): void {
		$destination_probe = $this->probe();
		$this->add_source_file( $destination_probe, '2026/07/hero.jpg' );
		$destination_probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 100, 100, 'image/webp', 100, 100, true, true, 'D:/outside/hero.webp' );

		$temporary_probe = $this->probe();
		$this->add_source_file( $temporary_probe, '2026/07/hero.jpg' );
		$temporary_probe->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp', 100, 100, 'image/webp', 100, 100, true, true, 'D:/outside/hero.tmp' );

		$destination = $this->resolver( $destination_probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$temporary   = $this->resolver( $temporary_probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );

		self::assertSame( DestinationResolutionResult::CODE_DESTINATION_REALPATH_OUTSIDE_UPLOADS, $destination->code() );
		self::assertSame( DestinationResolutionResult::CODE_TEMPORARY_REALPATH_OUTSIDE_UPLOADS, $temporary->code() );
	}

	/**
	 * Test serialization omits absolute paths.
	 *
	 * @return void
	 */
	public function test_serialization_omits_absolute_paths(): void {
		$probe = $this->probe();
		$this->add_source_file( $probe, '2026/07/hero.jpg' );

		$result     = $this->resolver( $probe )->resolve( $this->source( '2026/07/hero.jpg' ), SourceMimePolicy::TARGET_WEBP );
		$serialized = $result->to_array();

		self::assertArrayNotHasKey( 'absolute_path', $serialized['source'] );
		self::assertArrayNotHasKey( 'absolute_path', $serialized['destination'] );
		self::assertArrayNotHasKey( 'temporary_absolute_path', $serialized['destination'] );
		self::assertStringNotContainsString( self::UPLOADS, $serialized['destination']['relative_path'] );
		self::assertStringNotContainsString( self::UPLOADS, $serialized['destination']['temporary_relative_path'] );
	}

	/**
	 * Build resolver.
	 *
	 * @param FakeImageFileProbe $probe Probe.
	 * @return DestinationResolver
	 */
	private function resolver( FakeImageFileProbe $probe ): DestinationResolver {
		return new DestinationResolver( self::UPLOADS, $probe );
	}

	/**
	 * Build fake probe.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe(): FakeImageFileProbe {
		return new FakeImageFileProbe( array( self::UPLOADS ) );
	}

	/**
	 * Add source file.
	 *
	 * @param FakeImageFileProbe $probe Probe.
	 * @param string             $relative_path Relative path.
	 * @return void
	 */
	private function add_source_file( FakeImageFileProbe $probe, string $relative_path ): void {
		$probe->add_file( self::UPLOADS . '/' . $relative_path, 1000, 100, 'image/jpeg', 100, 100 );
	}

	/**
	 * Build source image.
	 *
	 * @param string      $relative_path Relative path.
	 * @param string      $role Role.
	 * @param string      $size_name Size name.
	 * @param string|null $absolute_path Absolute path.
	 * @return SourceImage
	 */
	private function source(
		string $relative_path,
		string $role = SourceImage::ROLE_FULL,
		string $size_name = 'full',
		?string $absolute_path = null
	): SourceImage {
		return new SourceImage(
			123,
			$size_name,
			$role,
			$relative_path,
			$absolute_path ?? self::UPLOADS . '/' . $relative_path,
			'image/jpeg',
			100,
			100,
			1000,
			100
		);
	}
}
