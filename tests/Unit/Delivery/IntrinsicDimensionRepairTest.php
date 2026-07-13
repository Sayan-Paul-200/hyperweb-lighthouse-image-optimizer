<?php
/**
 * Tests for intrinsic-dimension repair.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderResult;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative intrinsic-dimension repair behavior.
 */
final class IntrinsicDimensionRepairTest extends TestCase {

	/**
	 * Test full-size images missing both dimensions are repaired.
	 *
	 * @return void
	 */
	public function test_full_size_images_missing_both_dimensions_are_repaired(): void {
		$result = $this->repair()->repair(
			123,
			'<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">',
			$this->image_meta()
		);

		self::assertTrue( $result->is_repaired() );
		self::assertStringContainsString( 'width="2400"', $result->html() );
		self::assertStringContainsString( 'height="1600"', $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_REPAIRED ) );
	}

	/**
	 * Test subsize images missing both dimensions are repaired.
	 *
	 * @return void
	 */
	public function test_subsize_images_missing_both_dimensions_are_repaired(): void {
		$result = $this->repair()->repair(
			123,
			'<img src="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg" alt="Hero">',
			$this->image_meta()
		);

		self::assertTrue( $result->is_repaired() );
		self::assertStringContainsString( 'width="150"', $result->html() );
		self::assertStringContainsString( 'height="100"', $result->html() );
	}

	/**
	 * Test width-only missing is repaired when the existing height matches metadata.
	 *
	 * @return void
	 */
	public function test_width_only_missing_is_repaired_when_existing_height_matches_metadata(): void {
		$result = $this->repair()->repair(
			123,
			'<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" height="1600" alt="Hero">',
			$this->image_meta()
		);

		self::assertTrue( $result->is_repaired() );
		self::assertStringContainsString( 'width="2400"', $result->html() );
		self::assertStringContainsString( 'height="1600"', $result->html() );
	}

	/**
	 * Test height-only missing is repaired when the existing width matches metadata.
	 *
	 * @return void
	 */
	public function test_height_only_missing_is_repaired_when_existing_width_matches_metadata(): void {
		$result = $this->repair()->repair(
			123,
			'<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" alt="Hero">',
			$this->image_meta()
		);

		self::assertTrue( $result->is_repaired() );
		self::assertStringContainsString( 'width="2400"', $result->html() );
		self::assertStringContainsString( 'height="1600"', $result->html() );
	}

	/**
	 * Test conflicting existing dimensions remain unchanged and uncertain.
	 *
	 * @return void
	 */
	public function test_conflicting_existing_dimensions_remain_unchanged_and_uncertain(): void {
		$html   = '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="1000" alt="Hero">';
		$result = $this->repair()->repair( 123, $html, $this->image_meta() );

		self::assertFalse( $result->is_repaired() );
		self::assertSame( $html, $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN ) );
	}

	/**
	 * Test unmatched src values remain unchanged and uncertain.
	 *
	 * @return void
	 */
	public function test_unmatched_src_values_remain_unchanged_and_uncertain(): void {
		$html   = '<img src="https://cdn.example.test/hero.jpg" alt="Hero">';
		$result = $this->repair()->repair( 123, $html, $this->image_meta() );

		self::assertFalse( $result->is_repaired() );
		self::assertSame( $html, $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN ) );
	}

	/**
	 * Test invalid existing intrinsic attributes remain unchanged and uncertain.
	 *
	 * @return void
	 */
	public function test_invalid_existing_intrinsic_attributes_remain_unchanged_and_uncertain(): void {
		$html   = '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="wide" alt="Hero">';
		$result = $this->repair()->repair( 123, $html, $this->image_meta() );

		self::assertFalse( $result->is_repaired() );
		self::assertSame( $html, $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN ) );
	}

	/**
	 * Test invalid or incomplete metadata remains unchanged and uncertain.
	 *
	 * @return void
	 */
	public function test_invalid_or_incomplete_metadata_remains_unchanged_and_uncertain(): void {
		$html = '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">';
		$meta = $this->image_meta();

		unset( $meta['width'], $meta['height'] );

		$result = $this->repair()->repair( 123, $html, $meta );

		self::assertFalse( $result->is_repaired() );
		self::assertSame( $html, $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN ) );
	}

	/**
	 * Test already-complete intrinsic dimensions remain unchanged.
	 *
	 * @return void
	 */
	public function test_already_complete_intrinsic_dimensions_remain_unchanged(): void {
		$html   = '<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" width="2400" height="1600" alt="Hero">';
		$result = $this->repair()->repair( 123, $html, $this->image_meta() );

		self::assertFalse( $result->is_repaired() );
		self::assertSame( $html, $result->html() );
		self::assertSame( array(), $result->codes() );
	}

	/**
	 * Build repair service.
	 *
	 * @return IntrinsicDimensionRepair
	 */
	private function repair(): IntrinsicDimensionRepair {
		$analyzer = new WordPressImageMarkupAnalyzer();

		return new IntrinsicDimensionRepair(
			new AttachmentSizeResolver( new DerivativeManifestSanitizer() ),
			$analyzer
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
}
