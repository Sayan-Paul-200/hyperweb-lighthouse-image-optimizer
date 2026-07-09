<?php
/**
 * Format support provider contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Provides canonical format support reports.
 */
interface FormatSupportProviderInterface {

	/**
	 * Get support details for a format.
	 *
	 * @param string $format Target format.
	 * @return FormatSupportResult
	 */
	public function support_for( string $format ): FormatSupportResult;
}
