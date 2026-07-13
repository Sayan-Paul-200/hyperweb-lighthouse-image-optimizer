<?php
/**
 * Composite diagnostics service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\ConflictDiagnostics;
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
	 * Conflict diagnostics.
	 *
	 * @var ConflictDiagnostics
	 */
	private $conflicts;

	/**
	 * Create service.
	 *
	 * @param EnvironmentDiagnostics      $environment Environment diagnostics.
	 * @param DerivativeHealthDiagnostics $derivatives Derivative health diagnostics.
	 * @param ConflictDiagnostics         $conflicts Conflict diagnostics.
	 */
	public function __construct(
		EnvironmentDiagnostics $environment,
		DerivativeHealthDiagnostics $derivatives,
		ConflictDiagnostics $conflicts
	) {
		$this->environment = $environment;
		$this->derivatives = $derivatives;
		$this->conflicts   = $conflicts;
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
				$this->conflicts->run(),
				array( $this->derivatives->run() )
			)
		) )->to_array();
	}
}
