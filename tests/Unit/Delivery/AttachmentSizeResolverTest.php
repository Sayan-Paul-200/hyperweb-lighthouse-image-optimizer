<?php
/**
 * Tests for attachment size resolution.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative attachment-size resolution from markup and metadata.
 */
final class AttachmentSizeResolverTest extends TestCase {

	/**
	 * Test full-size src resolves to the full metadata candidate.
	 *
	 * @return void
	 */
	public function test_full_size_src_resolves_to_the_full_metadata_candidate(): void {
		$resolver = $this->resolver();
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">'
		);
		$result   = $resolver->resolve_from_analysis( $analysis, $this->image_meta() );

		self::assertIsArray( $result );
		self::assertSame( 'full', $result['size_name'] );
		self::assertSame( 2400, $result['width'] );
		self::assertSame( 1600, $result['height'] );
	}

	/**
	 * Test subsize src resolves to the matching metadata candidate.
	 *
	 * @return void
	 */
	public function test_subsize_src_resolves_to_the_matching_metadata_candidate(): void {
		$resolver = $this->resolver();
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<img src="https://example.test/wp-content/uploads/2026/07/hero-150x100.jpg" alt="Hero">'
		);
		$result   = $resolver->resolve_from_analysis( $analysis, $this->image_meta() );

		self::assertIsArray( $result );
		self::assertSame( 'thumbnail', $result['size_name'] );
		self::assertSame( 150, $result['width'] );
		self::assertSame( 100, $result['height'] );
	}

	/**
	 * Test unmatched src values remain unresolved.
	 *
	 * @return void
	 */
	public function test_unmatched_src_values_remain_unresolved(): void {
		$resolver = $this->resolver();
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<img src="https://cdn.example.test/hero.jpg" alt="Hero">'
		);

		self::assertNull( $resolver->resolve_from_analysis( $analysis, $this->image_meta() ) );
	}

	/**
	 * Test missing metadata dimensions prevent candidate resolution.
	 *
	 * @return void
	 */
	public function test_missing_metadata_dimensions_prevent_candidate_resolution(): void {
		$resolver = $this->resolver();
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<img src="https://example.test/wp-content/uploads/2026/07/hero.jpg" alt="Hero">'
		);
		$meta     = $this->image_meta();

		unset( $meta['width'], $meta['height'] );

		self::assertNull( $resolver->resolve_from_analysis( $analysis, $meta ) );
	}

	/**
	 * Build resolver.
	 *
	 * @return AttachmentSizeResolver
	 */
	private function resolver(): AttachmentSizeResolver {
		return new AttachmentSizeResolver( new DerivativeManifestSanitizer() );
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
