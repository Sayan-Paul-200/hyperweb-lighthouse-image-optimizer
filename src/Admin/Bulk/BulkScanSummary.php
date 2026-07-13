<?php
/**
 * Bulk scan summary.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries cumulative dry-run summary counts.
 */
final class BulkScanSummary {

	/**
	 * Count map.
	 *
	 * @var array<string,int>
	 */
	private $counts;

	/**
	 * Create the summary.
	 *
	 * @param int $scanned Scanned count.
	 * @param int $eligible Eligible count.
	 * @param int $excluded Excluded count.
	 * @param int $active Active count.
	 * @param int $already_optimized Already optimized count.
	 * @param int $skipped Skipped count.
	 */
	public function __construct(
		int $scanned = 0,
		int $eligible = 0,
		int $excluded = 0,
		int $active = 0,
		int $already_optimized = 0,
		int $skipped = 0
	) {
		$this->counts = array(
			'scanned'           => max( 0, $scanned ),
			'eligible'          => max( 0, $eligible ),
			'excluded'          => max( 0, $excluded ),
			'active'            => max( 0, $active ),
			'already_optimized' => max( 0, $already_optimized ),
			'skipped'           => max( 0, $skipped ),
		);
	}

	/**
	 * Build from stored data.
	 *
	 * @param mixed $value Raw summary data.
	 * @return self
	 */
	public static function from_array( $value ): self {
		if ( ! is_array( $value ) ) {
			return new self();
		}

		return new self(
			self::int_value( $value, 'scanned' ),
			self::int_value( $value, 'eligible' ),
			self::int_value( $value, 'excluded' ),
			self::int_value( $value, 'active' ),
			self::int_value( $value, 'already_optimized' ),
			self::int_value( $value, 'skipped' )
		);
	}

	/**
	 * Add one count delta set.
	 *
	 * @param array<string,int> $delta Count deltas.
	 * @return self
	 */
	public function accumulate( array $delta ): self {
		return new self(
			$this->scanned() + self::safe_delta( $delta, 'scanned' ),
			$this->eligible() + self::safe_delta( $delta, 'eligible' ),
			$this->excluded() + self::safe_delta( $delta, 'excluded' ),
			$this->active() + self::safe_delta( $delta, 'active' ),
			$this->already_optimized() + self::safe_delta( $delta, 'already_optimized' ),
			$this->skipped() + self::safe_delta( $delta, 'skipped' )
		);
	}

	/**
	 * Get scanned count.
	 *
	 * @return int
	 */
	public function scanned(): int {
		return $this->counts['scanned'];
	}

	/**
	 * Get eligible count.
	 *
	 * @return int
	 */
	public function eligible(): int {
		return $this->counts['eligible'];
	}

	/**
	 * Get excluded count.
	 *
	 * @return int
	 */
	public function excluded(): int {
		return $this->counts['excluded'];
	}

	/**
	 * Get active count.
	 *
	 * @return int
	 */
	public function active(): int {
		return $this->counts['active'];
	}

	/**
	 * Get already-optimized count.
	 *
	 * @return int
	 */
	public function already_optimized(): int {
		return $this->counts['already_optimized'];
	}

	/**
	 * Get skipped count.
	 *
	 * @return int
	 */
	public function skipped(): int {
		return $this->counts['skipped'];
	}

	/**
	 * Serialize the summary.
	 *
	 * @return array<string,int>
	 */
	public function to_array(): array {
		return $this->counts;
	}

	/**
	 * Get a stored integer value.
	 *
	 * @param array<string,mixed> $values Values.
	 * @param string              $key Array key.
	 * @return int
	 */
	private static function int_value( array $values, string $key ): int {
		return isset( $values[ $key ] ) && is_numeric( $values[ $key ] ) ? max( 0, (int) $values[ $key ] ) : 0;
	}

	/**
	 * Get one safe delta.
	 *
	 * @param array<string,int> $delta Count delta map.
	 * @param string            $key Count key.
	 * @return int
	 */
	private static function safe_delta( array $delta, string $key ): int {
		return isset( $delta[ $key ] ) && is_numeric( $delta[ $key ] ) ? max( 0, (int) $delta[ $key ] ) : 0;
	}
}
