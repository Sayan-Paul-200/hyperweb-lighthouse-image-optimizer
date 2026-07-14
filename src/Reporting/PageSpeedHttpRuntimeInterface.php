<?php
/**
 * PageSpeed HTTP runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Wraps WordPress HTTP requests for testable PSI client behavior.
 */
interface PageSpeedHttpRuntimeInterface {

	/**
	 * Execute one GET request with query args.
	 *
	 * @param string              $url Base endpoint.
	 * @param array<string,mixed> $query_args Query args.
	 * @return PageSpeedHttpResponse
	 */
	public function get( string $url, array $query_args ): PageSpeedHttpResponse;
}
