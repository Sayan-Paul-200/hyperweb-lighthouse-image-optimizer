<?php
/**
 * Tests for Elementor widget matching.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative Elementor fragment classification.
 */
final class ElementorWidgetMatcherTest extends TestCase {

	/**
	 * Test supported widget fixtures classify as supported attachment widgets.
	 *
	 * @param string $fixture Fixture file.
	 * @return void
	 *
	 * @dataProvider supported_widget_fixture_provider
	 */
	public function test_supported_widget_fixtures_classify_as_supported_attachment_widgets( string $fixture ): void {
		$matcher = $this->matcher();

		self::assertSame(
			ElementorWidgetMatcher::MATCH_SUPPORTED_ATTACHMENT_WIDGET,
			$matcher->match( $this->fixture_html( $fixture ) )
		);
	}

	/**
	 * Test gallery and carousel fixtures classify as excluded.
	 *
	 * @param string $fixture Fixture file.
	 * @return void
	 *
	 * @dataProvider excluded_widget_fixture_provider
	 */
	public function test_gallery_and_carousel_fixtures_classify_as_excluded( string $fixture ): void {
		$matcher = $this->matcher();

		self::assertSame(
			ElementorWidgetMatcher::MATCH_EXCLUDED_GALLERY_OR_CAROUSEL,
			$matcher->match( $this->fixture_html( $fixture ) )
		);
	}

	/**
	 * Test supported widget fixtures fail open in editor and preview mode.
	 *
	 * @return void
	 */
	public function test_supported_widget_fixtures_fail_open_in_editor_and_preview_modes(): void {
		$editor_runtime                = new FakeElementorRuntime();
		$editor_runtime->editor_mode   = true;
		$preview_runtime               = new FakeElementorRuntime();
		$preview_runtime->preview_mode = true;
		$html                          = $this->fixture_html( 'image-widget-attachment.html' );

		self::assertSame(
			ElementorWidgetMatcher::MATCH_EDITOR_OR_PREVIEW,
			$this->matcher( $editor_runtime )->match( $html )
		);
		self::assertSame(
			ElementorWidgetMatcher::MATCH_EDITOR_OR_PREVIEW,
			$this->matcher( $preview_runtime )->match( $html )
		);
	}

	/**
	 * Test malformed or uncertain fragments classify as unrecognized.
	 *
	 * @return void
	 */
	public function test_malformed_or_uncertain_fragments_classify_as_unrecognized(): void {
		$matcher = $this->matcher();

		self::assertSame( ElementorWidgetMatcher::MATCH_UNRECOGNIZED, $matcher->match( '<span>Not an image</span>' ) );
		self::assertSame(
			ElementorWidgetMatcher::MATCH_UNRECOGNIZED,
			$matcher->match( '<img class="wp-image-777 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/plain-content.jpg" alt="Plain content image">' )
		);
	}

	/**
	 * Provide supported widget fixtures.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function supported_widget_fixture_provider(): array {
		return array(
			'image widget'     => array( 'image-widget-attachment.html' ),
			'image box widget' => array( 'image-box-widget-attachment.html' ),
			'cta widget'       => array( 'cta-widget-attachment.html' ),
		);
	}

	/**
	 * Provide excluded widget fixtures.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function excluded_widget_fixture_provider(): array {
		return array(
			'gallery widget'  => array( 'gallery-widget-attachment.html' ),
			'carousel widget' => array( 'carousel-widget-attachment.html' ),
		);
	}

	/**
	 * Build matcher.
	 *
	 * @param FakeElementorRuntime|null $runtime Runtime seam.
	 * @return ElementorWidgetMatcher
	 */
	private function matcher( ?FakeElementorRuntime $runtime = null ): ElementorWidgetMatcher {
		$runtime = $runtime ?? new FakeElementorRuntime();

		return new ElementorWidgetMatcher( $runtime, new WordPressImageMarkupAnalyzer() );
	}

	/**
	 * Load one fixture HTML fragment.
	 *
	 * @param string $file Fixture file.
	 * @return string
	 */
	private function fixture_html( string $file ): string {
		$path = dirname( __DIR__, 2 ) . '/Fixtures/Elementor/' . $file;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading committed fixture files during tests.
		$html = file_get_contents( $path );

		self::assertIsString( $html );

		return $html;
	}
}
