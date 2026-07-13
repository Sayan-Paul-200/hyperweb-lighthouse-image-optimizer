<?php
/**
 * Tests for image markup analysis.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative image-fragment analysis.
 */
final class WordPressImageMarkupAnalyzerTest extends TestCase {

	/**
	 * Test exact standalone image fragments are accepted.
	 *
	 * @return void
	 */
	public function test_exact_standalone_image_fragments_are_accepted(): void {
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			" \n<img src=\"hero.jpg\" alt=\"Hero\" sizes=\"(max-width: 600px) 100vw, 600px\" loading=\"eager\" fetchpriority=\"high\" decoding=\"async\"> \n"
		);

		self::assertTrue( $analysis->is_renderable_img() );
		self::assertFalse( $analysis->is_picture() );
		self::assertSame( '(max-width: 600px) 100vw, 600px', $analysis->sizes() );
		self::assertSame( 'eager', $analysis->loading() );
		self::assertSame( 'high', $analysis->fetchpriority() );
		self::assertSame( 'async', $analysis->decoding() );
		self::assertFalse( $analysis->has_loading_priority_conflict() );
	}

	/**
	 * Test already-picture fragments are rejected for rendering.
	 *
	 * @return void
	 */
	public function test_picture_fragments_are_rejected_for_rendering(): void {
		$analysis = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<picture><source type="image/webp" srcset="hero.webp 100w"><img src="hero.jpg" alt="Hero"></picture>'
		);

		self::assertFalse( $analysis->is_renderable_img() );
		self::assertTrue( $analysis->is_picture() );
		self::assertNull( $analysis->sizes() );
	}

	/**
	 * Test malformed and multi-node fragments are rejected.
	 *
	 * @return void
	 */
	public function test_malformed_and_multi_node_fragments_are_rejected(): void {
		$analyzer = new WordPressImageMarkupAnalyzer();

		self::assertFalse( $analyzer->analyze( '<img src="hero.jpg"' )->is_renderable_img() );
		self::assertFalse( $analyzer->analyze( '<span>Before</span><img src="hero.jpg" alt="Hero">' )->is_renderable_img() );
		self::assertFalse( $analyzer->analyze( '<img src="hero.jpg"></img>' )->is_renderable_img() );
	}

	/**
	 * Test analysis remains path-safe.
	 *
	 * @return void
	 */
	public function test_sizes_extraction_is_path_safe(): void {
		$payload = ( new WordPressImageMarkupAnalyzer() )->analyze(
			'<img src="hero.jpg" sizes="100vw" data-path="C:/secret/file.jpg">'
		)->to_array();

		self::assertSame( '100vw', $payload['sizes'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $payload );
		self::assertStringNotContainsString( 'C:/secret', is_string( $json ) ? $json : '' );
	}

	/**
	 * Test loading-priority conflict detection is narrow and conservative.
	 *
	 * @return void
	 */
	public function test_loading_priority_conflict_detection_is_narrow_and_conservative(): void {
		$analyzer = new WordPressImageMarkupAnalyzer();

		self::assertTrue(
			$analyzer->analyze( '<img src="hero.jpg" loading="lazy" fetchpriority="high" decoding="async">' )->has_loading_priority_conflict()
		);
		self::assertFalse(
			$analyzer->analyze( '<img src="hero.jpg" loading="eager" fetchpriority="high" decoding="async">' )->has_loading_priority_conflict()
		);
		self::assertFalse(
			$analyzer->analyze( '<img src="hero.jpg" loading="lazy" decoding="async">' )->has_loading_priority_conflict()
		);
	}
}
