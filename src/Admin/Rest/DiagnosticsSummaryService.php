<?php
/**
 * Diagnostics summary service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\EnvironmentDiagnostics;

/**
 * Adapts EnvironmentDiagnostics for REST responses.
 */
final class DiagnosticsSummaryService implements DiagnosticsServiceInterface {

	/**
	 * Diagnostics runner.
	 *
	 * @var EnvironmentDiagnostics
	 */
	private $diagnostics;

	/**
	 * Create the service.
	 *
	 * @param EnvironmentDiagnostics $diagnostics Diagnostics runner.
	 */
	public function __construct( EnvironmentDiagnostics $diagnostics ) {
		$this->diagnostics = $diagnostics;
	}

	/**
	 * Build the diagnostics report payload.
	 *
	 * @return array<string,mixed>
	 */
	public function report(): array {
		return $this->diagnostics->run()->to_array();
	}
}
