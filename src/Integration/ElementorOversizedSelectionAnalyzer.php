<?php
/**
 * Elementor oversized selection analyzer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalyzerInterface;

/**
 * Detects conservative oversized full-image selection in supported Elementor widgets.
 */
final class ElementorOversizedSelectionAnalyzer {

	/**
	 * Widget matcher.
	 *
	 * @var ElementorWidgetMatcher
	 */
	private $matcher;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Attachment size resolver.
	 *
	 * @var AttachmentSizeResolver
	 */
	private $resolver;

	/**
	 * Width ratio threshold.
	 *
	 * @var float
	 */
	private $threshold;

	/**
	 * Create analyzer.
	 *
	 * @param ElementorWidgetMatcher       $matcher Widget matcher.
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 * @param AttachmentSizeResolver       $resolver Attachment size resolver.
	 * @param float                        $threshold Width ratio threshold.
	 */
	public function __construct(
		ElementorWidgetMatcher $matcher,
		ImageMarkupAnalyzerInterface $analyzer,
		AttachmentSizeResolver $resolver,
		float $threshold = 1.5
	) {
		$this->matcher   = $matcher;
		$this->analyzer  = $analyzer;
		$this->resolver  = $resolver;
		$this->threshold = $threshold > 0 ? $threshold : 1.5;
	}

	/**
	 * Analyze one Elementor image fragment for oversized full-image selection.
	 *
	 * @param string              $html Widget image fragment.
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $image_meta Attachment metadata.
	 * @param int|null            $known_width Reliable runtime width hint.
	 * @return ElementorOversizedSelectionResult
	 */
	public function analyze( string $html, int $attachment_id, array $image_meta, ?int $known_width = null ): ElementorOversizedSelectionResult {
		$match = $this->matcher->match( $html );

		if ( ElementorWidgetMatcher::MATCH_SUPPORTED_ATTACHMENT_WIDGET !== $match ) {
			return ElementorOversizedSelectionResult::unsupported(
				array(
					'attachment_id' => max( 0, $attachment_id ),
					'widget_match'  => $match,
				)
			);
		}

		$analysis = $this->analyzer->analyze( $html );

		if ( ! $analysis->is_renderable_img() ) {
			return ElementorOversizedSelectionResult::uncertain(
				array(
					'attachment_id' => max( 0, $attachment_id ),
					'widget_match'  => $match,
					'reason'        => 'invalid_markup',
				)
			);
		}

		$selected = $this->resolver->resolve_from_analysis( $analysis, $image_meta, $known_width );

		if ( ! is_array( $selected ) ) {
			return ElementorOversizedSelectionResult::uncertain(
				array(
					'attachment_id' => max( 0, $attachment_id ),
					'widget_match'  => $match,
					'reason'        => 'unresolved_selected_candidate',
					'slot'          => $this->slot_details( $analysis, $known_width ),
				)
			);
		}

		$details = array(
			'attachment_id'          => max( 0, $attachment_id ),
			'widget_match'           => $match,
			'selected_size_name'     => isset( $selected['size_name'] ) ? (string) $selected['size_name'] : '',
			'selected_source_width'  => isset( $selected['width'] ) ? (int) $selected['width'] : null,
			'selected_source_height' => isset( $selected['height'] ) ? (int) $selected['height'] : null,
			'slot'                   => $this->slot_details( $analysis, $known_width ),
		);

		if ( 'full' !== $details['selected_size_name'] ) {
			return ElementorOversizedSelectionResult::not_detected( $details );
		}

		$slot_width = $details['slot']['width'];

		if ( ! is_int( $slot_width ) || $slot_width < 1 ) {
			$details['reason'] = 'missing_reliable_slot_width';

			return ElementorOversizedSelectionResult::uncertain( $details );
		}

		$selected_width = $details['selected_source_width'];

		if ( ! is_int( $selected_width ) || $selected_width < 1 ) {
			$details['reason'] = 'missing_selected_source_width';

			return ElementorOversizedSelectionResult::uncertain( $details );
		}

		$ratio                  = round( $selected_width / $slot_width, 2 );
		$details['width_ratio'] = $ratio;

		if ( $ratio >= $this->threshold ) {
			$details['recommendation'] = 'Use a registered intermediate image size instead of full for this widget when the smaller slot is intentional.';

			return ElementorOversizedSelectionResult::finding( $details );
		}

		return ElementorOversizedSelectionResult::not_detected( $details );
	}

	/**
	 * Build reliable slot details from markup and runtime width facts.
	 *
	 * @param \HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalysis $analysis Markup analysis.
	 * @param int|null                                                        $known_width Known runtime width.
	 * @return array<string,int|null>
	 */
	private function slot_details( $analysis, ?int $known_width ): array {
		$analysis_width  = $analysis->has_valid_width() ? $analysis->width() : null;
		$analysis_height = $analysis->has_valid_height() ? $analysis->height() : null;
		$known_width     = is_int( $known_width ) && $known_width > 0 ? $known_width : null;

		$slot_width  = null !== $known_width ? $known_width : $analysis_width;
		$slot_height = null;

		if ( null === $known_width ) {
			$slot_height = $analysis_height;
		} elseif ( null !== $analysis_height && null !== $analysis_width && $analysis_width === $known_width ) {
			$slot_height = $analysis_height;
		}

		return array(
			'width'  => $slot_width,
			'height' => $slot_height,
		);
	}
}
