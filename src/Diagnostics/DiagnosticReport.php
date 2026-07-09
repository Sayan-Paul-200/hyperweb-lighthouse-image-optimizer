<?php
/**
 * Diagnostic report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Collection of diagnostic results.
 */
final class DiagnosticReport {

	/**
	 * Diagnostic results.
	 *
	 * @var DiagnosticResult[]
	 */
	private $results;

	/**
	 * Create a report.
	 *
	 * @param DiagnosticResult[] $results Results.
	 */
	public function __construct( array $results ) {
		$this->results = array_values(
			array_filter(
				$results,
				static function ( $result ): bool {
					return $result instanceof DiagnosticResult;
				}
			)
		);
	}

	/**
	 * Get results.
	 *
	 * @return DiagnosticResult[]
	 */
	public function results(): array {
		return $this->results;
	}

	/**
	 * Get summary counts.
	 *
	 * @return array<string,int>
	 */
	public function summary(): array {
		$summary = array(
			'total'   => count( $this->results ),
			'pass'    => 0,
			'warning' => 0,
			'fail'    => 0,
			'info'    => 0,
		);

		foreach ( $this->results as $result ) {
			++$summary[ $result->status() ];
		}

		return $summary;
	}

	/**
	 * Serialize for REST/admin consumers.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'summary' => $this->summary(),
			'results' => array_map(
				static function ( DiagnosticResult $result ): array {
					return $result->to_array();
				},
				$this->results
			),
		);
	}
}
