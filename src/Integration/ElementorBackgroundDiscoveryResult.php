<?php
/**
 * Elementor background discovery result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries supported background sources plus unsupported observations.
 */
final class ElementorBackgroundDiscoveryResult {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $document_id;

	/**
	 * Supported sources.
	 *
	 * @var ElementorBackgroundSource[]
	 */
	private $supported_sources;

	/**
	 * Unsupported cases.
	 *
	 * @var ElementorUnsupportedBackgroundCase[]
	 */
	private $unsupported_cases;

	/**
	 * Create the result.
	 *
	 * @param int                                  $document_id Document ID.
	 * @param ElementorBackgroundSource[]          $supported_sources Supported sources.
	 * @param ElementorUnsupportedBackgroundCase[] $unsupported_cases Unsupported cases.
	 */
	public function __construct( int $document_id, array $supported_sources = array(), array $unsupported_cases = array() ) {
		$this->document_id       = max( 0, $document_id );
		$this->supported_sources = $this->filter_supported_sources( $supported_sources );
		$this->unsupported_cases = $this->filter_unsupported_cases( $unsupported_cases );
	}

	/**
	 * Get supported sources.
	 *
	 * @return ElementorBackgroundSource[]
	 */
	public function supported_sources(): array {
		return $this->supported_sources;
	}

	/**
	 * Get unsupported cases.
	 *
	 * @return ElementorUnsupportedBackgroundCase[]
	 */
	public function unsupported_cases(): array {
		return $this->unsupported_cases;
	}

	/**
	 * Build a scalar summary.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$device_counts = array(
			'desktop' => 0,
			'tablet'  => 0,
			'mobile'  => 0,
		);
		$code_counts   = array();

		foreach ( $this->supported_sources as $source ) {
			$data = $source->to_array();

			if ( isset( $device_counts[ $data['device'] ] ) ) {
				++$device_counts[ $data['device'] ];
			}
		}

		foreach ( $this->unsupported_cases as $case ) {
			$code = $case->code();

			if ( ! isset( $code_counts[ $code ] ) ) {
				$code_counts[ $code ] = 0;
			}

			++$code_counts[ $code ];
		}

		return array(
			'document_id'            => $this->document_id,
			'supported_source_count' => count( $this->supported_sources ),
			'unsupported_case_count' => count( $this->unsupported_cases ),
			'device_counts'          => $device_counts,
			'unsupported_codes'      => $code_counts,
		);
	}

	/**
	 * Serialize the full result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'document_id'       => $this->document_id,
			'supported_sources' => array_map(
				static function ( ElementorBackgroundSource $source ): array {
					return $source->to_array();
				},
				$this->supported_sources
			),
			'unsupported_cases' => array_map(
				static function ( ElementorUnsupportedBackgroundCase $unsupported_case ): array {
					return $unsupported_case->to_array();
				},
				$this->unsupported_cases
			),
			'summary'           => $this->summary(),
		);
	}

	/**
	 * Filter supported sources.
	 *
	 * @param array<int,mixed> $sources Raw sources.
	 * @return ElementorBackgroundSource[]
	 */
	private function filter_supported_sources( array $sources ): array {
		return array_values(
			array_filter(
				$sources,
				static function ( $source ): bool {
					return $source instanceof ElementorBackgroundSource;
				}
			)
		);
	}

	/**
	 * Filter unsupported cases.
	 *
	 * @param array<int,mixed> $cases Raw cases.
	 * @return ElementorUnsupportedBackgroundCase[]
	 */
	private function filter_unsupported_cases( array $cases ): array {
		return array_values(
			array_filter(
				$cases,
				static function ( $unsupported_case ): bool {
					return $unsupported_case instanceof ElementorUnsupportedBackgroundCase;
				}
			)
		);
	}
}
