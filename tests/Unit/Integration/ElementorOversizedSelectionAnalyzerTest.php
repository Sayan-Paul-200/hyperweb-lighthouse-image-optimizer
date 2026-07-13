<?php
/**
 * Tests for Elementor oversized full-image selection diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorOversizedSelectionAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorOversizedSelectionResult;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conservative oversized full-selection advisory detection.
 */
final class ElementorOversizedSelectionAnalyzerTest extends TestCase {

	/**
	 * Test a supported widget selecting full for a much smaller slot produces a finding.
	 *
	 * @return void
	 */
	public function test_supported_widget_selecting_full_for_small_slot_produces_finding(): void {
		$result = $this->analyzer()->analyze(
			$this->fixture_html( 'image-widget-full-small-slot.html' ),
			321,
			$this->image_meta()
		);

		self::assertTrue( $result->is_reportable() );
		self::assertTrue( $result->has_finding() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_FULL_SELECTION_DETECTED, $result->code() );
		self::assertSame( 'full', $result->details()['selected_size_name'] );
		self::assertSame( 1200, $result->details()['selected_source_width'] );
		self::assertSame( 320, $result->details()['slot']['width'] );
		self::assertSame( 3.75, $result->details()['width_ratio'] );
	}

	/**
	 * Test a supported widget selecting full near full width does not produce a finding.
	 *
	 * @return void
	 */
	public function test_supported_widget_selecting_full_near_full_width_does_not_produce_finding(): void {
		$result = $this->analyzer()->analyze(
			$this->fixture_html( 'image-widget-full-near-full.html' ),
			321,
			$this->image_meta()
		);

		self::assertTrue( $result->is_reportable() );
		self::assertFalse( $result->has_finding() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_SELECTION_NOT_DETECTED, $result->code() );
		self::assertSame( 1.33, $result->details()['width_ratio'] );
	}

	/**
	 * Test supported medium and large selections do not produce oversized-full findings.
	 *
	 * @return void
	 */
	public function test_supported_medium_and_large_selections_do_not_produce_full_selection_findings(): void {
		$medium = $this->analyzer()->analyze(
			$this->fixture_html( 'image-box-widget-attachment.html' ),
			322,
			$this->image_box_meta()
		);
		$large  = $this->analyzer()->analyze(
			$this->fixture_html( 'cta-widget-attachment.html' ),
			323,
			$this->cta_meta()
		);

		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_SELECTION_NOT_DETECTED, $medium->code() );
		self::assertSame( 'medium', $medium->details()['selected_size_name'] );
		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_SELECTION_NOT_DETECTED, $large->code() );
		self::assertSame( 'large', $large->details()['selected_size_name'] );
	}

	/**
	 * Test missing slot dimensions remain uncertain instead of guessing.
	 *
	 * @return void
	 */
	public function test_missing_slot_dimensions_remain_uncertain(): void {
		$result = $this->analyzer()->analyze(
			$this->fixture_html( 'image-widget-full-uncertain.html' ),
			321,
			$this->image_meta()
		);

		self::assertTrue( $result->is_reportable() );
		self::assertFalse( $result->has_finding() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_SELECTION_UNCERTAIN, $result->code() );
		self::assertSame( 'missing_reliable_slot_width', $result->details()['reason'] );
	}

	/**
	 * Test unresolved selected metadata candidates remain uncertain.
	 *
	 * @return void
	 */
	public function test_unresolved_selected_metadata_candidates_remain_uncertain(): void {
		$meta         = $this->image_meta();
		$meta['file'] = '2026/07/different-file.jpg';

		$result = $this->analyzer()->analyze(
			$this->fixture_html( 'image-widget-full-small-slot.html' ),
			321,
			$meta
		);

		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_SELECTION_UNCERTAIN, $result->code() );
		self::assertSame( 'unresolved_selected_candidate', $result->details()['reason'] );
	}

	/**
	 * Test gallery, carousel, and editor/preview contexts are unsupported.
	 *
	 * @return void
	 */
	public function test_gallery_carousel_and_editor_preview_contexts_are_unsupported(): void {
		$gallery = $this->analyzer()->analyze(
			$this->fixture_html( 'gallery-widget-attachment.html' ),
			324,
			$this->gallery_meta()
		);

		$carousel = $this->analyzer()->analyze(
			$this->fixture_html( 'carousel-widget-attachment.html' ),
			325,
			$this->carousel_meta()
		);

		$runtime              = new FakeElementorRuntime();
		$runtime->editor_mode = true;
		$editor               = $this->analyzer( $runtime )->analyze(
			$this->fixture_html( 'image-widget-full-small-slot.html' ),
			321,
			$this->image_meta()
		);

		self::assertSame( ElementorOversizedSelectionResult::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT, $gallery->code() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT, $carousel->code() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT, $editor->code() );
	}

	/**
	 * Build analyzer.
	 *
	 * @param FakeElementorRuntime|null $runtime Runtime seam.
	 * @return ElementorOversizedSelectionAnalyzer
	 */
	private function analyzer( ?FakeElementorRuntime $runtime = null ): ElementorOversizedSelectionAnalyzer {
		$runtime  = $runtime ?? new FakeElementorRuntime();
		$analyzer = new WordPressImageMarkupAnalyzer();

		return new ElementorOversizedSelectionAnalyzer(
			new ElementorWidgetMatcher( $runtime, $analyzer ),
			$analyzer,
			new AttachmentSizeResolver( new DerivativeManifestSanitizer() )
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

	/**
	 * Build full-size image metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function image_meta(): array {
		return array(
			'file'   => '2026/07/elementor-image.jpg',
			'width'  => 1200,
			'height' => 800,
			'sizes'  => array(
				'medium' => array(
					'file'   => 'elementor-image-300x200.jpg',
					'width'  => 300,
					'height' => 200,
				),
			),
		);
	}

	/**
	 * Build image-box metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function image_box_meta(): array {
		return array(
			'file'   => '2026/07/elementor-image-box.jpg',
			'width'  => 1200,
			'height' => 800,
			'sizes'  => array(
				'medium' => array(
					'file'   => 'elementor-image-box-300x200.jpg',
					'width'  => 300,
					'height' => 200,
				),
			),
		);
	}

	/**
	 * Build CTA metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function cta_meta(): array {
		return array(
			'file'   => '2026/07/elementor-cta.jpg',
			'width'  => 1920,
			'height' => 1080,
			'sizes'  => array(
				'large' => array(
					'file'   => 'elementor-cta-640x360.jpg',
					'width'  => 640,
					'height' => 360,
				),
			),
		);
	}

	/**
	 * Build gallery metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function gallery_meta(): array {
		return array(
			'file'   => '2026/07/elementor-gallery.jpg',
			'width'  => 1200,
			'height' => 1200,
			'sizes'  => array(
				'thumbnail' => array(
					'file'   => 'elementor-gallery-300x300.jpg',
					'width'  => 300,
					'height' => 300,
				),
			),
		);
	}

	/**
	 * Build carousel metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function carousel_meta(): array {
		return array(
			'file'   => '2026/07/elementor-carousel.jpg',
			'width'  => 1920,
			'height' => 1080,
			'sizes'  => array(
				'large' => array(
					'file'   => 'elementor-carousel-640x360.jpg',
					'width'  => 640,
					'height' => 360,
				),
			),
		);
	}
}
