<?php
/**
 * Bulk queue summary.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries cumulative bulk queue summary counts.
 */
final class BulkQueueSummary {

	/**
	 * Count map.
	 *
	 * @var array<string,int>
	 */
	private $counts;

	/**
	 * Create the summary.
	 *
	 * @param int $queued Queued count.
	 * @param int $already_queued Already-queued count.
	 * @param int $already_optimized Already-optimized count.
	 * @param int $skipped Skipped count.
	 * @param int $failed_to_queue Failed-to-queue count.
	 */
	public function __construct(
		int $queued = 0,
		int $already_queued = 0,
		int $already_optimized = 0,
		int $skipped = 0,
		int $failed_to_queue = 0
	) {
		$this->counts = array(
			'queued'            => max( 0, $queued ),
			'already_queued'    => max( 0, $already_queued ),
			'already_optimized' => max( 0, $already_optimized ),
			'skipped'           => max( 0, $skipped ),
			'failed_to_queue'   => max( 0, $failed_to_queue ),
		);
	}

	/**
	 * Build from stored data.
	 *
	 * @param mixed $value Raw value.
	 * @return self
	 */
	public static function from_array( $value ): self {
		if ( ! is_array( $value ) ) {
			return new self();
		}

		return new self(
			self::int_value( $value, 'queued' ),
			self::int_value( $value, 'already_queued' ),
			self::int_value( $value, 'already_optimized' ),
			self::int_value( $value, 'skipped' ),
			self::int_value( $value, 'failed_to_queue' )
		);
	}

	/**
	 * Accumulate one delta set.
	 *
	 * @param array<string,int> $delta Delta map.
	 * @return self
	 */
	public function accumulate( array $delta ): self {
		return new self(
			$this->counts['queued'] + self::int_value( $delta, 'queued' ),
			$this->counts['already_queued'] + self::int_value( $delta, 'already_queued' ),
			$this->counts['already_optimized'] + self::int_value( $delta, 'already_optimized' ),
			$this->counts['skipped'] + self::int_value( $delta, 'skipped' ),
			$this->counts['failed_to_queue'] + self::int_value( $delta, 'failed_to_queue' )
		);
	}

	/**
	 * Serialize to an array.
	 *
	 * @return array<string,int>
	 */
	public function to_array(): array {
		return $this->counts;
	}

	/**
	 * Read one safe integer value.
	 *
	 * @param array<string,mixed> $values Values.
	 * @param string              $key Key.
	 * @return int
	 */
	private static function int_value( array $values, string $key ): int {
		return isset( $values[ $key ] ) && is_numeric( $values[ $key ] ) ? max( 0, (int) $values[ $key ] ) : 0;
	}
}
