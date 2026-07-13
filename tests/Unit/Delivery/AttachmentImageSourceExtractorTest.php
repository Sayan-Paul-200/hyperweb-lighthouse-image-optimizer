<?php
/**
 * Tests for attachment image source extraction.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative extraction of original responsive candidates.
 */
final class AttachmentImageSourceExtractorTest extends TestCase {

	/**
	 * Test standard core-style srcset strings become width-keyed candidates in original order.
	 *
	 * @return void
	 */
	public function test_standard_core_style_srcset_strings_become_width_keyed_candidates_in_original_order(): void {
		$extraction = $this->extractor()->extract(
			'<img src="https://example.test/uploads/hero.jpg" srcset="https://example.test/uploads/hero-150x100.jpg 150w, https://example.test/uploads/hero.jpg 2400w" sizes="100vw" alt="Hero">'
		);

		self::assertTrue( $extraction->has_sources() );
		self::assertSame( array( 150, 2400 ), array_keys( $extraction->sources() ) );
		self::assertSame( 'https://example.test/uploads/hero-150x100.jpg', $extraction->sources()[150]['url'] );
		self::assertSame( 'https://example.test/uploads/hero.jpg', $extraction->sources()[2400]['url'] );
	}

	/**
	 * Test malformed or unsupported srcset candidates are skipped conservatively.
	 *
	 * @return void
	 */
	public function test_malformed_or_unsupported_srcset_candidates_are_skipped_conservatively(): void {
		$extraction = $this->extractor()->extract(
			'<img src="https://example.test/uploads/hero.jpg" srcset="https://example.test/uploads/hero.jpg 2x, invalid, https://example.test/uploads/hero-150x100.jpg 150w, https://example.test/uploads/hero.jpg 2400w, https://example.test/uploads/dupe.jpg 2400w" alt="Hero">'
		);

		self::assertSame( array( 150, 2400 ), array_keys( $extraction->sources() ) );
		self::assertSame( 'https://example.test/uploads/hero-150x100.jpg', $extraction->sources()[150]['url'] );
		self::assertSame( 'https://example.test/uploads/hero.jpg', $extraction->sources()[2400]['url'] );
	}

	/**
	 * Test src fallback uses known width when no usable srcset exists.
	 *
	 * @return void
	 */
	public function test_src_fallback_uses_known_width_when_no_usable_srcset_exists(): void {
		$extraction = $this->extractor()->extract(
			'<img src="https://example.test/uploads/hero.jpg" alt="Hero">',
			2400
		);

		self::assertSame(
			array(
				2400 => array(
					'url'        => 'https://example.test/uploads/hero.jpg',
					'descriptor' => 'w',
					'value'      => 2400,
				),
			),
			$extraction->sources()
		);
	}

	/**
	 * Test no parseable source data fails open with no candidates.
	 *
	 * @return void
	 */
	public function test_no_parseable_source_data_fails_open_with_no_candidates(): void {
		$extraction = $this->extractor()->extract( '<span>Hero</span><img src="hero.jpg" alt="Hero">' );

		self::assertFalse( $extraction->has_sources() );
		self::assertSame( array(), $extraction->sources() );
	}

	/**
	 * Build extractor.
	 *
	 * @return AttachmentImageSourceExtractor
	 */
	private function extractor(): AttachmentImageSourceExtractor {
		return new AttachmentImageSourceExtractor( new WordPressImageMarkupAnalyzer() );
	}
}
