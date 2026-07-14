<?php
/**
 * Image issue report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries the page-level issue findings derived from one inventory snapshot.
 */
final class ImageIssueReport {

	/**
	 * Findings.
	 *
	 * @var ImageIssueFinding[]
	 */
	private $findings;

	/**
	 * Create report.
	 *
	 * @param ImageIssueFinding[] $findings Findings.
	 */
	public function __construct( array $findings = array() ) {
		$this->findings = array_values(
			array_filter(
				$findings,
				static function ( $finding ): bool {
					return $finding instanceof ImageIssueFinding;
				}
			)
		);
	}

	/**
	 * Get findings.
	 *
	 * @return ImageIssueFinding[]
	 */
	public function findings(): array {
		return $this->findings;
	}

	/**
	 * Serialize findings.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function to_array(): array {
		return array_map(
			static function ( ImageIssueFinding $finding ): array {
				return $finding->to_array();
			},
			$this->findings
		);
	}

	/**
	 * Build issue summary counts.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$by_severity = array(
			ImageIssueFinding::SEVERITY_HIGH   => 0,
			ImageIssueFinding::SEVERITY_MEDIUM => 0,
			ImageIssueFinding::SEVERITY_LOW    => 0,
		);
		$by_code     = array();

		foreach ( $this->findings as $finding ) {
			++$by_severity[ $finding->severity() ];
			$code              = $finding->code();
			$by_code[ $code ] = isset( $by_code[ $code ] ) ? (int) $by_code[ $code ] + 1 : 1;
		}

		ksort( $by_code );

		return array(
			'total'       => count( $this->findings ),
			'by_severity' => $by_severity,
			'by_code'     => $by_code,
		);
	}
}
