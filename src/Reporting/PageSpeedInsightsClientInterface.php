<?php
/**
 * PageSpeed Insights client contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Fetches and normalizes PSI responses.
 */
interface PageSpeedInsightsClientInterface {

	/**
	 * Fetch one PSI report for the given public URL and strategy.
	 *
	 * @param string $public_url Public URL.
	 * @param string $strategy Strategy.
	 * @param string $api_key Optional API key.
	 * @return PageSpeedClientResult
	 */
	public function fetch( string $public_url, string $strategy, string $api_key = '' ): PageSpeedClientResult;
}
