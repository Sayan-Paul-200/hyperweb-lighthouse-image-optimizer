<?php
/**
 * Image markup analyzer contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Analyzes one image HTML fragment conservatively.
 */
interface ImageMarkupAnalyzerInterface {

	/**
	 * Analyze one image fragment.
	 *
	 * @param string $html Markup fragment.
	 * @return ImageMarkupAnalysis
	 */
	public function analyze( string $html ): ImageMarkupAnalysis;
}
