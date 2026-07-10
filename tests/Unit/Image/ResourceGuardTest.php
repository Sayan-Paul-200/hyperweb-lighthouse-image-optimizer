<?php
/**
 * Tests for resource guard.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ResourceGuard;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use PHPUnit\Framework\TestCase;

/**
 * Verifies pre-allocation pixel and memory safety boundaries.
 */
final class ResourceGuardTest extends TestCase {

	/**
	 * Test small image passes.
	 *
	 * @return void
	 */
	public function test_small_image_passes(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '256M' ), 40000000 );
		$source = $this->source( 100, 100 ); // Verify 10,000 pixels source.

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		self::assertFalse( $result->is_denied() );
		self::assertNull( $result->code() );
		self::assertNull( $result->reason() );
		self::assertSame( 10000, $result->pixel_count() );
		self::assertSame( 40000000, $result->max_pixel_count() );
		// Verify 10000 pixels with 4 channels and 1.8 overhead is 72000.
		self::assertSame( 72000, $result->estimated_memory_bytes() );
		// Verify 256MB with 0.8 margin is 204.8MB.
		self::assertSame( (int) floor( 256 * 1024 * 1024 * 0.8 ), $result->available_memory_bytes() );
	}

	/**
	 * Test oversized image is denied by pixel limit.
	 *
	 * @return void
	 */
	public function test_oversized_image_is_denied_by_pixel_limit(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '2G' ), 40000000 ); // Verify plenty of memory.
		$source = $this->source( 8000, 6000 ); // Verify 48 million pixels source.

		$result = $guard->check( $source );

		self::assertFalse( $result->is_allowed() );
		self::assertTrue( $result->is_denied() );
		self::assertSame( 'skipped_resource_limit', $result->code() );
		self::assertSame( 'pixel_limit_exceeded', $result->reason() );
		self::assertSame( 48000000, $result->pixel_count() );
	}

	/**
	 * Test memory estimate exceeding available memory is denied.
	 *
	 * @return void
	 */
	public function test_memory_estimate_exceeding_limit_is_denied(): void {
		$guard = new ResourceGuard( MemoryLimit::from_raw( '64M' ), 40000000 );
		// Verify 64MB with 0.8 margin is ~53.6MB available.
		// Verify 3000 by 3000 is 9M pixels, which estimates 64.8MB.
		$source = $this->source( 3000, 3000 );

		$result = $guard->check( $source );

		self::assertFalse( $result->is_allowed() );
		self::assertSame( 'memory_estimate_exceeded', $result->reason() );
		self::assertSame( 9000000, $result->pixel_count() );
		self::assertSame( 64800000, $result->estimated_memory_bytes() );
		self::assertSame( 53687091, $result->available_memory_bytes() );
	}

	/**
	 * Test unlimited memory (-1) allows any pixel count within limit.
	 *
	 * @return void
	 */
	public function test_unlimited_memory_allows_within_pixel_limit(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '-1' ), 40000000 );
		$source = $this->source( 6000, 6000 ); // Verify 36 million pixels source.

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		self::assertNull( $result->available_memory_bytes() );
	}

	/**
	 * Test unknown memory limit allows any pixel count within limit (fail-open).
	 *
	 * @return void
	 */
	public function test_unknown_memory_allows_within_pixel_limit(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( 'invalid_value' ), 40000000 );
		$source = $this->source( 6000, 6000 ); // Verify 36 million pixels source.

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		self::assertNull( $result->available_memory_bytes() );
	}

	/**
	 * Test custom max pixel count is respected.
	 *
	 * @return void
	 */
	public function test_custom_max_pixel_count_is_respected(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '2G' ), 1000000 ); // Verify 1 megapixel limit.
		$source = $this->source( 1200, 1000 ); // Verify 1.2 megapixels source.

		$result = $guard->check( $source );

		self::assertFalse( $result->is_allowed() );
		self::assertSame( 'pixel_limit_exceeded', $result->reason() );
	}

	/**
	 * Test custom channels, overhead, and margin are respected.
	 *
	 * @return void
	 */
	public function test_custom_multipliers_are_respected(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '100M' ), 40000000, 3, 2.0, 0.5 );
		$source = $this->source( 1000, 1000 ); // Verify 1 million pixels source.

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		// Verify 1 million with 3 channels and 2.0 overhead is 6 million.
		self::assertSame( 6000000, $result->estimated_memory_bytes() );
		// Verify 100MB with 0.5 margin is 50MB.
		self::assertSame( 52428800, $result->available_memory_bytes() );
	}

	/**
	 * Test 1x1 image passes even with very low memory limit.
	 *
	 * @return void
	 */
	public function test_minimum_dimensions_pass_low_memory(): void {
		$guard  = new ResourceGuard( MemoryLimit::from_raw( '1M' ), 40000000 );
		$source = $this->source( 1, 1 );

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		self::assertSame( 8, $result->estimated_memory_bytes() ); // Verify ceil 1 by 4 by 1.8.
	}

	/**
	 * Test WordPress factory is safe without WordPress bootstrap.
	 *
	 * @return void
	 */
	public function test_wordpress_factory_falls_back_without_apply_filters(): void {
		$guard  = ResourceGuard::for_wordpress( MemoryLimit::from_raw( '256M' ) );
		$source = $this->source( 100, 100 );

		$result = $guard->check( $source );

		self::assertTrue( $result->is_allowed() );
		self::assertSame( 40000000, $result->max_pixel_count() );
	}

	/**
	 * Build source image.
	 *
	 * @param int $width Width.
	 * @param int $height Height.
	 * @return SourceImage
	 */
	private function source( int $width, int $height ): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/test.jpg',
			'C:/site/wp-content/uploads/2026/07/test.jpg',
			'image/jpeg',
			$width,
			$height,
			1000,
			1783526400
		);
	}
}
