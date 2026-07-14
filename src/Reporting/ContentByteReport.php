<?php
/**
 * Content byte report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries summary and per-occurrence byte reporting for one content record.
 */
final class ContentByteReport {

	/**
	 * Summary.
	 *
	 * @var ContentByteSummary
	 */
	private $summary;

	/**
	 * Occurrence rows.
	 *
	 * @var ByteOccurrenceReport[]
	 */
	private $occurrences;

	/**
	 * Create report.
	 *
	 * @param ContentByteSummary    $summary Summary.
	 * @param ByteOccurrenceReport[] $occurrences Occurrence rows.
	 */
	public function __construct( ContentByteSummary $summary, array $occurrences = array() ) {
		$this->summary     = $summary;
		$this->occurrences = array_values(
			array_filter(
				$occurrences,
				static function ( $occurrence ): bool {
					return $occurrence instanceof ByteOccurrenceReport;
				}
			)
		);
	}

	/**
	 * Get summary.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		return $this->summary->to_array();
	}

	/**
	 * Serialize rows.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function occurrences(): array {
		return array_map(
			static function ( ByteOccurrenceReport $occurrence ): array {
				return $occurrence->to_array();
			},
			$this->occurrences
		);
	}
}
