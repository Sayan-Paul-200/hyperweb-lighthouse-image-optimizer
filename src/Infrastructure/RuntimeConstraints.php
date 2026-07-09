<?php
/**
 * Runtime constraint values.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Captures PHP runtime limits without modifying them.
 */
final class RuntimeConstraints {

	/**
	 * Parsed memory limit.
	 *
	 * @var MemoryLimit
	 */
	private $memory_limit;

	/**
	 * Raw max execution time value.
	 *
	 * @var string
	 */
	private $max_execution_time_raw;

	/**
	 * Parsed max execution time.
	 *
	 * @var int|null
	 */
	private $max_execution_time;

	/**
	 * Create runtime constraints.
	 *
	 * @param MemoryLimit $memory_limit Parsed memory limit.
	 * @param string      $max_execution_time_raw Raw max execution time.
	 * @param int|null    $max_execution_time Parsed max execution time.
	 */
	public function __construct( MemoryLimit $memory_limit, string $max_execution_time_raw, ?int $max_execution_time ) {
		$this->memory_limit           = $memory_limit;
		$this->max_execution_time_raw = $max_execution_time_raw;
		$this->max_execution_time     = $max_execution_time;
	}

	/**
	 * Build constraints from raw PHP values.
	 *
	 * @param string $memory_limit Raw memory limit.
	 * @param string $max_execution_time Raw max execution time.
	 * @return self
	 */
	public static function from_raw( string $memory_limit, string $max_execution_time ): self {
		$execution         = trim( $max_execution_time );
		$execution_seconds = null;

		if ( 1 === preg_match( '/^\d+$/', $execution ) ) {
			$execution_seconds = (int) $execution;
		}

		return new self(
			MemoryLimit::from_raw( $memory_limit ),
			$max_execution_time,
			$execution_seconds
		);
	}

	/**
	 * Get the memory limit.
	 *
	 * @return MemoryLimit
	 */
	public function memory_limit(): MemoryLimit {
		return $this->memory_limit;
	}

	/**
	 * Get raw max execution time.
	 *
	 * @return string
	 */
	public function max_execution_time_raw(): string {
		return $this->max_execution_time_raw;
	}

	/**
	 * Get parsed max execution time.
	 *
	 * @return int|null
	 */
	public function max_execution_time(): ?int {
		return $this->max_execution_time;
	}
}
