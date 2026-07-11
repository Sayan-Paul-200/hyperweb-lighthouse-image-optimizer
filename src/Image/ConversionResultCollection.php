<?php
/**
 * Conversion result collection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries a set of conversion results.
 */
final class ConversionResultCollection {

	/**
	 * Results.
	 *
	 * @var ConversionResult[]
	 */
	private $results;

	/**
	 * Create collection.
	 *
	 * @param ConversionResult[] $results Results.
	 */
	public function __construct( array $results = array() ) {
		$this->results = array_values(
			array_filter(
				$results,
				static function ( $result ): bool {
					return $result instanceof ConversionResult;
				}
			)
		);
	}

	/**
	 * Get all results.
	 *
	 * @return ConversionResult[]
	 */
	public function results(): array {
		return $this->results;
	}

	/**
	 * Return a copy with one result appended.
	 *
	 * @param ConversionResult $result Result.
	 * @return self
	 */
	public function with_added( ConversionResult $result ): self {
		$results   = $this->results;
		$results[] = $result;

		return new self( $results );
	}

	/**
	 * Get successful results.
	 *
	 * @return ConversionResult[]
	 */
	public function successful(): array {
		return $this->with_status( ConversionResult::STATUS_SUCCESS );
	}

	/**
	 * Get skipped results.
	 *
	 * @return ConversionResult[]
	 */
	public function skipped(): array {
		return $this->with_status( ConversionResult::STATUS_SKIPPED );
	}

	/**
	 * Get failed results.
	 *
	 * @return ConversionResult[]
	 */
	public function failed(): array {
		return $this->with_status( ConversionResult::STATUS_FAILED );
	}

	/**
	 * Whether any result failed.
	 *
	 * @return bool
	 */
	public function has_failures(): bool {
		return array() !== $this->failed();
	}

	/**
	 * Summarize result counts.
	 *
	 * @return array<string,int>
	 */
	public function summary(): array {
		return array(
			'total'   => count( $this->results ),
			'success' => count( $this->successful() ),
			'skipped' => count( $this->skipped() ),
			'failed'  => count( $this->failed() ),
		);
	}

	/**
	 * Serialize collection.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'results' => array_map(
				static function ( ConversionResult $result ): array {
					return $result->to_array();
				},
				$this->results
			),
			'summary' => $this->summary(),
		);
	}

	/**
	 * Filter by status.
	 *
	 * @param string $status Status.
	 * @return ConversionResult[]
	 */
	private function with_status( string $status ): array {
		return array_values(
			array_filter(
				$this->results,
				static function ( ConversionResult $result ) use ( $status ): bool {
					return $status === $result->status();
				}
			)
		);
	}
}
