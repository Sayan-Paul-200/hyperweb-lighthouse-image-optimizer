<?php
/**
 * Bulk scan progress.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries resumable scan progress metadata.
 */
final class BulkScanProgress {

	public const STATUS_RUNNING  = 'running';
	public const STATUS_COMPLETE = 'complete';
	public const STATUS_FAILED   = 'failed';

	/**
	 * Session status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Last processed attachment ID cursor.
	 *
	 * @var int
	 */
	private $last_processed_id;

	/**
	 * Persisted candidate chunk count.
	 *
	 * @var int
	 */
	private $candidate_chunk_count;

	/**
	 * Persisted candidate total.
	 *
	 * @var int
	 */
	private $candidate_total;

	/**
	 * Create the progress object.
	 *
	 * @param string $status Progress status.
	 * @param int    $last_processed_id Monotonic attachment cursor.
	 * @param int    $candidate_chunk_count Stored chunk count.
	 * @param int    $candidate_total Candidate total.
	 */
	public function __construct(
		string $status = self::STATUS_RUNNING,
		int $last_processed_id = 0,
		int $candidate_chunk_count = 0,
		int $candidate_total = 0
	) {
		$this->status                = self::normalize_status( $status );
		$this->last_processed_id     = max( 0, $last_processed_id );
		$this->candidate_chunk_count = max( 0, $candidate_chunk_count );
		$this->candidate_total       = max( 0, $candidate_total );
	}

	/**
	 * Build from stored data.
	 *
	 * @param mixed $value Raw progress data.
	 * @return self
	 */
	public static function from_array( $value ): self {
		if ( ! is_array( $value ) ) {
			return new self();
		}

		return new self(
			isset( $value['status'] ) && is_scalar( $value['status'] ) ? (string) $value['status'] : self::STATUS_RUNNING,
			isset( $value['last_processed_id'] ) && is_numeric( $value['last_processed_id'] ) ? (int) $value['last_processed_id'] : 0,
			isset( $value['candidate_chunk_count'] ) && is_numeric( $value['candidate_chunk_count'] ) ? (int) $value['candidate_chunk_count'] : 0,
			isset( $value['candidate_total'] ) && is_numeric( $value['candidate_total'] ) ? (int) $value['candidate_total'] : 0
		);
	}

	/**
	 * Normalize one status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	public static function normalize_status( string $status ): string {
		$status = strtolower( trim( $status ) );

		return in_array( $status, array( self::STATUS_RUNNING, self::STATUS_COMPLETE, self::STATUS_FAILED ), true )
			? $status
			: self::STATUS_RUNNING;
	}

	/**
	 * Update the cursor.
	 *
	 * @param int $last_processed_id New cursor.
	 * @return self
	 */
	public function with_cursor( int $last_processed_id ): self {
		return new self(
			$this->status(),
			max( $this->last_processed_id(), $last_processed_id ),
			$this->candidate_chunk_count(),
			$this->candidate_total()
		);
	}

	/**
	 * Update the status.
	 *
	 * @param string $status New status.
	 * @return self
	 */
	public function with_status( string $status ): self {
		return new self(
			$status,
			$this->last_processed_id(),
			$this->candidate_chunk_count(),
			$this->candidate_total()
		);
	}

	/**
	 * Update stored candidate counters.
	 *
	 * @param int $candidate_chunk_count New chunk count.
	 * @param int $candidate_total New candidate total.
	 * @return self
	 */
	public function with_candidates( int $candidate_chunk_count, int $candidate_total ): self {
		return new self(
			$this->status(),
			$this->last_processed_id(),
			$candidate_chunk_count,
			$candidate_total
		);
	}

	/**
	 * Get the status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get the last processed cursor.
	 *
	 * @return int
	 */
	public function last_processed_id(): int {
		return $this->last_processed_id;
	}

	/**
	 * Get candidate chunk count.
	 *
	 * @return int
	 */
	public function candidate_chunk_count(): int {
		return $this->candidate_chunk_count;
	}

	/**
	 * Get candidate total.
	 *
	 * @return int
	 */
	public function candidate_total(): int {
		return $this->candidate_total;
	}

	/**
	 * Whether the scan is complete.
	 *
	 * @return bool
	 */
	public function complete(): bool {
		return self::STATUS_COMPLETE === $this->status();
	}

	/**
	 * Serialize the progress.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'status'                => $this->status(),
			'last_processed_id'     => $this->last_processed_id(),
			'candidate_chunk_count' => $this->candidate_chunk_count(),
			'candidate_total'       => $this->candidate_total(),
			'complete'              => $this->complete(),
		);
	}
}
