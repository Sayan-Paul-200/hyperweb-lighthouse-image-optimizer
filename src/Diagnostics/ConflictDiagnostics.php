<?php
/**
 * Conflict diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictResult;

/**
 * Adapts compatibility warnings into structured diagnostics.
 */
final class ConflictDiagnostics {

	/**
	 * Conflict detector.
	 *
	 * @var ConflictDetector
	 */
	private $detector;

	/**
	 * Create diagnostics wrapper.
	 *
	 * @param ConflictDetector $detector Conflict detector.
	 */
	public function __construct( ConflictDetector $detector ) {
		$this->detector = $detector;
	}

	/**
	 * Build WordPress-backed diagnostics.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( ConflictDetector::for_wordpress() );
	}

	/**
	 * Run conflict diagnostics.
	 *
	 * @return DiagnosticResult[]
	 */
	public function run(): array {
		$results = array();

		foreach ( $this->detector->detect()->results() as $conflict ) {
			$results[] = new DiagnosticResult(
				'conflict_' . $conflict->capability(),
				ConflictResult::SEVERITY_ERROR === $conflict->severity() ? DiagnosticStatus::FAIL : DiagnosticStatus::WARNING,
				$conflict->code(),
				$conflict->label(),
				$conflict->message(),
				array(
					'capability'       => $conflict->capability(),
					'evidence_plugins' => $conflict->evidence_plugins(),
					'setting_keys'     => $conflict->setting_keys(),
				)
			);
		}

		return $results;
	}
}
