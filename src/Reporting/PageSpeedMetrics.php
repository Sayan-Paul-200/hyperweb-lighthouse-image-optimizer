<?php
/**
 * PageSpeed Insights metrics value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one normalized lab-data metrics payload.
 */
final class PageSpeedMetrics {

	/**
	 * Scalar metric payload.
	 *
	 * @var array<string,int|float|null>
	 */
	private $metrics;

	/**
	 * Create the metrics payload.
	 *
	 * @param array<string,mixed> $metrics Raw metrics.
	 */
	public function __construct( array $metrics = array() ) {
		$this->metrics = array(
			'performance_score'         => $this->score( $metrics['performance_score'] ?? null ),
			'largest_contentful_paint_ms' => $this->integer( $metrics['largest_contentful_paint_ms'] ?? null ),
			'cumulative_layout_shift'   => $this->decimal( $metrics['cumulative_layout_shift'] ?? null ),
			'speed_index_ms'            => $this->integer( $metrics['speed_index_ms'] ?? null ),
			'total_blocking_time_ms'    => $this->integer( $metrics['total_blocking_time_ms'] ?? null ),
		);
	}

	/**
	 * Build an empty metrics object.
	 *
	 * @return self
	 */
	public static function empty(): self {
		return new self();
	}

	/**
	 * Serialize metrics.
	 *
	 * @return array<string,int|float|null>
	 */
	public function to_array(): array {
		return $this->metrics;
	}

	/**
	 * Whether any metric is available.
	 *
	 * @return bool
	 */
	public function has_values(): bool {
		foreach ( $this->metrics as $value ) {
			if ( null !== $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize one score-like value to 0-100.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	private function score( $value ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$numeric = (float) $value;
		$numeric = $numeric <= 1 ? $numeric * 100 : $numeric;

		return max( 0, min( 100, (int) round( $numeric ) ) );
	}

	/**
	 * Normalize one integer metric.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	private function integer( $value ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return max( 0, (int) round( (float) $value ) );
	}

	/**
	 * Normalize one decimal metric.
	 *
	 * @param mixed $value Raw value.
	 * @return float|null
	 */
	private function decimal( $value ): ?float {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return max( 0.0, round( (float) $value, 3 ) );
	}
}
