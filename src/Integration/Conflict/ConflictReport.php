<?php
/**
 * Conflict report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Conflict;

/**
 * Aggregates current-site compatibility warnings.
 */
final class ConflictReport {

	/**
	 * Conflict results.
	 *
	 * @var ConflictResult[]
	 */
	private $results;

	/**
	 * Create report.
	 *
	 * @param ConflictResult[] $results Results.
	 */
	public function __construct( array $results = array() ) {
		$this->results = array_values(
			array_filter(
				$results,
				static function ( $result ): bool {
					return $result instanceof ConflictResult;
				}
			)
		);
	}

	/**
	 * Get results.
	 *
	 * @return ConflictResult[]
	 */
	public function results(): array {
		return $this->results;
	}

	/**
	 * Whether the report is empty.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return array() === $this->results;
	}

	/**
	 * Serialize for admin/REST use.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function to_array(): array {
		return array_map(
			static function ( ConflictResult $result ): array {
				return $result->to_array();
			},
			$this->results
		);
	}
}
