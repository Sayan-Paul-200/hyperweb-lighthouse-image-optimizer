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
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportDiagnostics;

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
	 * Offload support diagnostics.
	 *
	 * @var OffloadSupportDiagnostics|null
	 */
	private $offload;

	/**
	 * Create service.
	 *
	 * @param EnvironmentDiagnostics         $environment Environment diagnostics.
	 * @param DerivativeHealthDiagnostics    $derivatives Derivative health diagnostics.
	 * @param ConflictDiagnostics            $conflicts Conflict diagnostics.
	 * @param OffloadSupportDiagnostics|null $offload Offload support diagnostics.
	 */
	public function __construct(
		EnvironmentDiagnostics $environment,
		DerivativeHealthDiagnostics $derivatives,
		ConflictDiagnostics $conflicts,
		?OffloadSupportDiagnostics $offload = null
	) {
		$this->environment = $environment;
		$this->derivatives = $derivatives;
		$this->conflicts   = $conflicts;
		$this->offload     = $offload;
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
				null !== $this->offload ? array( $this->offload->run() ) : array(),
				array( $this->derivatives->run() )
			)
		) )->to_array();
	}
}
