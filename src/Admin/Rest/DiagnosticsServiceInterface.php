<?php
/**
 * Diagnostics service contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Builds the diagnostics payload used by the REST controller.
 */
interface DiagnosticsServiceInterface {

	/**
	 * Build the diagnostics report payload.
	 *
	 * @return array<string,mixed>
	 */
	public function report(): array;
}
