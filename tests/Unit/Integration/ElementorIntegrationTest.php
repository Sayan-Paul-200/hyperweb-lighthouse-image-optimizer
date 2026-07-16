<?php
/**
 * Tests for Elementor attachment-widget integration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Elementor eligibility filtering behavior.
 */
final class ElementorIntegrationTest extends TestCase {

	/**
	 * Test hook registration adds only the internal Elementor eligibility filter.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_internal_elementor_filter(): void {
		$hooks = new HookRegistrar();

		$this->provider()->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 1, $hooks->filters() );
		self::assertSame( 'hwlio_markup_is_eligible', $hooks->filters()[0]['hook'] );
	}

	/**
	 * Test supported Elementor widgets preserve current eligibility.
	 *
	 * @param string $fixture Fixture file.
	 * @return void
	 *
	 * @dataProvider supported_widget_fixture_provider
	 */
	public function test_supported_elementor_widgets_preserve_current_eligibility( string $fixture ): void {
		$provider = $this->provider();

		self::assertTrue(
			$provider->filter_markup_eligibility( true, 321, $this->fixture_html( $fixture ), array( 'hook' => 'wp_get_attachment_image' ) )
		);
	}

	/**
	 * Test gallery and carousel widgets are made ineligible.
	 *
	 * @param string $fixture Fixture file.
	 * @return void
	 *
	 * @dataProvider excluded_widget_fixture_provider
	 */
	public function test_gallery_and_carousel_widgets_are_made_ineligible( string $fixture ): void {
		$provider = $this->provider();

		self::assertFalse(
			$provider->filter_markup_eligibility( true, 324, $this->fixture_html( $fixture ), array( 'hook' => 'wp_get_attachment_image' ) )
		);
	}

	/**
	 * Test editor and preview requests are made ineligible for supported Elementor fragments.
	 *
	 * @return void
	 */
	public function test_editor_and_preview_requests_are_made_ineligible(): void {
		$editor_runtime                = new FakeElementorRuntime();
		$editor_runtime->editor_mode   = true;
		$preview_runtime               = new FakeElementorRuntime();
		$preview_runtime->preview_mode = true;
		$html                          = $this->fixture_html( 'image-widget-attachment.html' );

		self::assertFalse( $this->provider( $editor_runtime )->filter_markup_eligibility( true, 321, $html, array() ) );
		self::assertFalse( $this->provider( $preview_runtime )->filter_markup_eligibility( true, 321, $html, array() ) );
	}

	/**
	 * Test non-Elementor fragments remain unaffected.
	 *
	 * @return void
	 */
	public function test_non_elementor_fragments_remain_unaffected(): void {
		$html = '<img class="wp-image-777 alignnone size-full" src="https://example.test/wp-content/uploads/2026/07/plain-content.jpg" width="1200" height="800" alt="Plain content image">';

		self::assertTrue( $this->provider()->filter_markup_eligibility( true, 777, $html, array() ) );
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
	 * Build provider.
	 *
	 * @param FakeElementorRuntime|null $runtime Runtime seam.
	 * @return ElementorIntegration
	 */
	private function provider( ?FakeElementorRuntime $runtime = null ): ElementorIntegration {
		$runtime = $runtime ?? new FakeElementorRuntime();

		return new ElementorIntegration(
			new ElementorWidgetMatcher( $runtime, new WordPressImageMarkupAnalyzer() )
		);
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
