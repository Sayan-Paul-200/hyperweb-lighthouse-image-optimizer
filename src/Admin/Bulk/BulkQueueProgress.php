<?php
/**
 * Bulk queue progress.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries resumable queue continuation progress for one scan session.
 */
final class BulkQueueProgress {

	public const MODE_IDLE  = 'idle';
	public const MODE_QUEUE = 'queue';
	public const MODE_RETRY = 'retry';

	public const STATUS_IDLE     = 'idle';
	public const STATUS_RUNNING  = 'running';
	public const STATUS_PAUSED   = 'paused';
	public const STATUS_COMPLETE = 'complete';

	/**
	 * Queue mode.
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Queue status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Next chunk page to process.
	 *
	 * @var int
	 */
	private $next_page;

	/**
	 * Processed candidate count.
	 *
	 * @var int
	 */
	private $processed_candidates;

	/**
	 * Create the progress object.
	 *
	 * @param string $mode Queue mode.
	 * @param string $status Queue status.
	 * @param int    $next_page Next chunk page.
	 * @param int    $processed_candidates Processed candidate count.
	 */
	public function __construct(
		string $mode = self::MODE_IDLE,
		string $status = self::STATUS_IDLE,
		int $next_page = 1,
		int $processed_candidates = 0
	) {
		$this->mode                 = self::normalize_mode( $mode );
		$this->status               = self::normalize_status( $status );
		$this->next_page            = max( 1, $next_page );
		$this->processed_candidates = max( 0, $processed_candidates );
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
			isset( $value['mode'] ) && is_scalar( $value['mode'] ) ? (string) $value['mode'] : self::MODE_IDLE,
			isset( $value['status'] ) && is_scalar( $value['status'] ) ? (string) $value['status'] : self::STATUS_IDLE,
			isset( $value['next_page'] ) && is_numeric( $value['next_page'] ) ? (int) $value['next_page'] : 1,
			isset( $value['processed_candidates'] ) && is_numeric( $value['processed_candidates'] ) ? (int) $value['processed_candidates'] : 0
		);
	}

	/**
	 * Normalize a mode.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	public static function normalize_mode( string $mode ): string {
		$mode = strtolower( trim( $mode ) );

		return in_array( $mode, array( self::MODE_IDLE, self::MODE_QUEUE, self::MODE_RETRY ), true )
			? $mode
			: self::MODE_IDLE;
	}

	/**
	 * Normalize a status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	public static function normalize_status( string $status ): string {
		$status = strtolower( trim( $status ) );

		return in_array( $status, array( self::STATUS_IDLE, self::STATUS_RUNNING, self::STATUS_PAUSED, self::STATUS_COMPLETE ), true )
			? $status
			: self::STATUS_IDLE;
	}

	/**
	 * Build a reset progress snapshot for one mode.
	 *
	 * @param string $mode Queue mode.
	 * @return self
	 */
	public static function reset( string $mode ): self {
		return new self( $mode, self::STATUS_RUNNING, 1, 0 );
	}

	/**
	 * Get mode.
	 *
	 * @return string
	 */
	public function mode(): string {
		return $this->mode;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get next page.
	 *
	 * @return int
	 */
	public function next_page(): int {
		return $this->next_page;
	}

	/**
	 * Get processed candidate count.
	 *
	 * @return int
	 */
	public function processed_candidates(): int {
		return $this->processed_candidates;
	}

	/**
	 * Whether complete.
	 *
	 * @return bool
	 */
	public function complete(): bool {
		return self::STATUS_COMPLETE === $this->status();
	}

	/**
	 * Update the status.
	 *
	 * @param string $status Queue status.
	 * @return self
	 */
	public function with_status( string $status ): self {
		return new self( $this->mode(), $status, $this->next_page(), $this->processed_candidates() );
	}

	/**
	 * Advance queue continuation by one processed page.
	 *
	 * @param int $processed_count Processed candidate count.
	 * @return self
	 */
	public function advance( int $processed_count ): self {
		return new self(
			$this->mode(),
			self::STATUS_RUNNING,
			$this->next_page() + 1,
			$this->processed_candidates() + max( 0, $processed_count )
		);
	}

	/**
	 * Serialize to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'mode'                 => $this->mode(),
			'status'               => $this->status(),
			'next_page'            => $this->next_page(),
			'processed_candidates' => $this->processed_candidates(),
			'complete'             => $this->complete(),
		);
	}
}
