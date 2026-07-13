<?php
/**
 * Tests for Elementor oversized selection result serialization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorOversizedSelectionResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies advisory result normalization and public-safe serialization.
 */
final class ElementorOversizedSelectionResultTest extends TestCase {

	/**
	 * Test finding results serialize safe scalar evidence only.
	 *
	 * @return void
	 */
	public function test_finding_results_serialize_safe_scalar_evidence_only(): void {
		$result = ElementorOversizedSelectionResult::finding(
			array(
				'attachment_id'  => 321,
				'widget_match'   => 'supported_attachment_widget',
				'selected_width' => 1200,
				'slot'           => array(
					'width'  => 320,
					'height' => 213,
				),
				'unsupported'    => (object) array( 'ignored' => true ),
			)
		);

		self::assertTrue( $result->is_reportable() );
		self::assertTrue( $result->has_finding() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_OVERSIZED_FULL_SELECTION_DETECTED, $result->code() );
		self::assertSame( null, $result->details()['unsupported'] );
		self::assertArrayNotHasKey( 'html', $result->to_array() );
	}

	/**
	 * Test unsupported results are not reportable and normalize unknown codes safely.
	 *
	 * @return void
	 */
	public function test_unsupported_results_are_not_reportable(): void {
		$result = ElementorOversizedSelectionResult::unsupported(
			array(
				'widget_match' => 'excluded_gallery_or_carousel',
			)
		);

		self::assertFalse( $result->is_reportable() );
		self::assertFalse( $result->has_finding() );
		self::assertSame( ElementorOversizedSelectionResult::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT, $result->code() );
	}
}
