<?php
/**
 * Source image validation collection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries validation results for a set of source images.
 */
final class SourceImageValidationCollection {

	/**
	 * Results.
	 *
	 * @var SourceImageValidationResult[]
	 */
	private $results;

	/**
	 * Create collection.
	 *
	 * @param SourceImageValidationResult[] $results Results.
	 */
	public function __construct( array $results = array() ) {
		$this->results = array_values(
			array_filter(
				$results,
				static function ( $result ): bool {
					return $result instanceof SourceImageValidationResult;
				}
			)
		);
	}

	/**
	 * Get all results.
	 *
	 * @return SourceImageValidationResult[]
	 */
	public function results(): array {
		return $this->results;
	}

	/**
	 * Get eligible results.
	 *
	 * @return SourceImageValidationResult[]
	 */
	public function eligible(): array {
		return $this->with_status( SourceImageValidationResult::STATUS_ELIGIBLE );
	}

	/**
	 * Get skipped results.
	 *
	 * @return SourceImageValidationResult[]
	 */
	public function skipped(): array {
		return $this->with_status( SourceImageValidationResult::STATUS_SKIPPED );
	}

	/**
	 * Get invalid results.
	 *
	 * @return SourceImageValidationResult[]
	 */
	public function invalid(): array {
		return $this->with_status( SourceImageValidationResult::STATUS_INVALID );
	}

	/**
	 * Serialize collection.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'results' => array_map(
				static function ( SourceImageValidationResult $result ): array {
					return $result->to_array();
				},
				$this->results
			),
		);
	}

	/**
	 * Filter by status.
	 *
	 * @param string $status Status.
	 * @return SourceImageValidationResult[]
	 */
	private function with_status( string $status ): array {
		return array_values(
			array_filter(
				$this->results,
				static function ( SourceImageValidationResult $result ) use ( $status ): bool {
					return $status === $result->status();
				}
			)
		);
	}
}
