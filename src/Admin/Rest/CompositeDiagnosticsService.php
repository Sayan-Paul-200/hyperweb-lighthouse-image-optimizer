<?php
/**
 * Composite diagnostics service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticReport;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\EnvironmentDiagnostics;

/**
 * Combines environment and derivative-health diagnostics for REST responses.
 */
final class CompositeDiagnosticsService implements DiagnosticsServiceInterface {

	/**
	 * Environment diagnostics.
	 *
	 * @var EnvironmentDiagnostics
	 */
	private $environment;

	/**
	 * Derivative health diagnostics.
	 *
	 * @var DerivativeHealthDiagnostics
	 */
	private $derivatives;

	/**
	 * Create service.
	 *
	 * @param EnvironmentDiagnostics      $environment Environment diagnostics.
	 * @param DerivativeHealthDiagnostics $derivatives Derivative health diagnostics.
	 */
	public function __construct( EnvironmentDiagnostics $environment, DerivativeHealthDiagnostics $derivatives ) {
		$this->environment = $environment;
		$this->derivatives = $derivatives;
	}

	/**
	 * Build the diagnostics report payload.
	 *
	 * @return array<string,mixed>
	 */
	public function report(): array {
		return ( new DiagnosticReport(
			array_merge(
				$this->environment->run()->results(),
				array( $this->derivatives->run() )
			)
		) )->to_array();
	}
}
