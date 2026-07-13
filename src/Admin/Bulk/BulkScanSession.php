<?php
/**
 * Bulk scan session.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries one persisted dry-run scan session.
 */
final class BulkScanSession {

	/**
	 * Scan token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Owning user ID.
	 *
	 * @var int
	 */
	private $owner_user_id;

	/**
	 * Created timestamp.
	 *
	 * @var string
	 */
	private $created_at_gmt;

	/**
	 * Updated timestamp.
	 *
	 * @var string
	 */
	private $updated_at_gmt;

	/**
	 * Normalized filters.
	 *
	 * @var BulkScanFilters
	 */
	private $filters;

	/**
	 * Scan progress.
	 *
	 * @var BulkScanProgress
	 */
	private $progress;

	/**
	 * Summary counts.
	 *
	 * @var BulkScanSummary
	 */
	private $summary;

	/**
	 * Queue continuation progress.
	 *
	 * @var BulkQueueProgress
	 */
	private $queue_progress;

	/**
	 * Queue continuation summary.
	 *
	 * @var BulkQueueSummary
	 */
	private $queue_summary;

	/**
	 * Create a session.
	 *
	 * @param string           $token Scan token.
	 * @param int              $owner_user_id Owning user ID.
	 * @param string           $created_at_gmt Created timestamp.
	 * @param string           $updated_at_gmt Updated timestamp.
	 * @param BulkScanFilters  $filters Normalized filters.
	 * @param BulkScanProgress $progress Resumable progress.
	 * @param BulkScanSummary  $summary Cumulative summary.
	 * @param BulkQueueProgress|null $queue_progress Queue progress.
	 * @param BulkQueueSummary|null  $queue_summary Queue summary.
	 */
	public function __construct(
		string $token,
		int $owner_user_id,
		string $created_at_gmt,
		string $updated_at_gmt,
		BulkScanFilters $filters,
		BulkScanProgress $progress,
		BulkScanSummary $summary,
		?BulkQueueProgress $queue_progress = null,
		?BulkQueueSummary $queue_summary = null
	) {
		$this->token          = self::normalize_token( $token );
		$this->owner_user_id  = max( 0, $owner_user_id );
		$this->created_at_gmt = self::normalize_gmt( $created_at_gmt );
		$this->updated_at_gmt = self::normalize_gmt( $updated_at_gmt );
		$this->filters        = $filters;
		$this->progress       = $progress;
		$this->summary        = $summary;
		$this->queue_progress = $queue_progress ?? new BulkQueueProgress();
		$this->queue_summary  = $queue_summary ?? new BulkQueueSummary();
	}

	/**
	 * Build one new running session.
	 *
	 * @param string          $token Scan token.
	 * @param int             $owner_user_id Owner user ID.
	 * @param string          $created_at_gmt Created timestamp.
	 * @param BulkScanFilters $filters Normalized filters.
	 * @return self
	 */
	public static function start( string $token, int $owner_user_id, string $created_at_gmt, BulkScanFilters $filters ): self {
		return new self(
			$token,
			$owner_user_id,
			$created_at_gmt,
			$created_at_gmt,
			$filters,
			new BulkScanProgress(),
			new BulkScanSummary()
		);
	}

	/**
	 * Build from stored data.
	 *
	 * @param mixed $value Raw stored session data.
	 * @return self|null
	 */
	public static function from_array( $value ): ?self {
		if ( ! is_array( $value ) ) {
			return null;
		}

		if ( ! isset( $value['token'] ) || ! is_scalar( $value['token'] ) ) {
			return null;
		}

		return new self(
			(string) $value['token'],
			isset( $value['owner_user_id'] ) && is_numeric( $value['owner_user_id'] ) ? (int) $value['owner_user_id'] : 0,
			isset( $value['created_at_gmt'] ) && is_scalar( $value['created_at_gmt'] ) ? (string) $value['created_at_gmt'] : '',
			isset( $value['updated_at_gmt'] ) && is_scalar( $value['updated_at_gmt'] ) ? (string) $value['updated_at_gmt'] : '',
			BulkScanFilters::from_array( isset( $value['filters'] ) && is_array( $value['filters'] ) ? $value['filters'] : array() ),
			BulkScanProgress::from_array( $value['progress'] ?? array() ),
			BulkScanSummary::from_array( $value['summary'] ?? array() ),
			BulkQueueProgress::from_array( $value['queue_progress'] ?? array() ),
			BulkQueueSummary::from_array( $value['queue_summary'] ?? array() )
		);
	}

	/**
	 * Normalize one scan token.
	 *
	 * @param string $token Raw token.
	 * @return string
	 */
	public static function normalize_token( string $token ): string {
		$token = strtolower( trim( $token ) );
		$token = (string) preg_replace( '/[^a-f0-9]/', '', $token );

		return substr( $token, 0, 64 );
	}

	/**
	 * Update the progress and touch the session.
	 *
	 * @param BulkScanProgress $progress Updated progress.
	 * @param string           $updated_at_gmt Updated timestamp.
	 * @return self
	 */
	public function with_progress( BulkScanProgress $progress, string $updated_at_gmt ): self {
		return new self(
			$this->token(),
			$this->owner_user_id(),
			$this->created_at_gmt(),
			$updated_at_gmt,
			$this->filters(),
			$progress,
			$this->summary(),
			$this->queue_progress(),
			$this->queue_summary()
		);
	}

	/**
	 * Update the summary and touch the session.
	 *
	 * @param BulkScanSummary $summary Updated summary.
	 * @param string          $updated_at_gmt Updated timestamp.
	 * @return self
	 */
	public function with_summary( BulkScanSummary $summary, string $updated_at_gmt ): self {
		return new self(
			$this->token(),
			$this->owner_user_id(),
			$this->created_at_gmt(),
			$updated_at_gmt,
			$this->filters(),
			$this->progress(),
			$summary,
			$this->queue_progress(),
			$this->queue_summary()
		);
	}

	/**
	 * Update progress and summary together.
	 *
	 * @param BulkScanProgress $progress Updated progress.
	 * @param BulkScanSummary  $summary Updated summary.
	 * @param string           $updated_at_gmt Updated timestamp.
	 * @return self
	 */
	public function with_state( BulkScanProgress $progress, BulkScanSummary $summary, string $updated_at_gmt ): self {
		return new self(
			$this->token(),
			$this->owner_user_id(),
			$this->created_at_gmt(),
			$updated_at_gmt,
			$this->filters(),
			$progress,
			$summary,
			$this->queue_progress(),
			$this->queue_summary()
		);
	}

	/**
	 * Update queue progress and summary together.
	 *
	 * @param BulkQueueProgress $queue_progress Queue progress.
	 * @param BulkQueueSummary  $queue_summary Queue summary.
	 * @param string            $updated_at_gmt Updated timestamp.
	 * @return self
	 */
	public function with_queue_state( BulkQueueProgress $queue_progress, BulkQueueSummary $queue_summary, string $updated_at_gmt ): self {
		return new self(
			$this->token(),
			$this->owner_user_id(),
			$this->created_at_gmt(),
			$updated_at_gmt,
			$this->filters(),
			$this->progress(),
			$this->summary(),
			$queue_progress,
			$queue_summary
		);
	}

	/**
	 * Get the token.
	 *
	 * @return string
	 */
	public function token(): string {
		return $this->token;
	}

	/**
	 * Get owner user ID.
	 *
	 * @return int
	 */
	public function owner_user_id(): int {
		return $this->owner_user_id;
	}

	/**
	 * Get created timestamp.
	 *
	 * @return string
	 */
	public function created_at_gmt(): string {
		return $this->created_at_gmt;
	}

	/**
	 * Get updated timestamp.
	 *
	 * @return string
	 */
	public function updated_at_gmt(): string {
		return $this->updated_at_gmt;
	}

	/**
	 * Get filters.
	 *
	 * @return BulkScanFilters
	 */
	public function filters(): BulkScanFilters {
		return $this->filters;
	}

	/**
	 * Get progress.
	 *
	 * @return BulkScanProgress
	 */
	public function progress(): BulkScanProgress {
		return $this->progress;
	}

	/**
	 * Get summary.
	 *
	 * @return BulkScanSummary
	 */
	public function summary(): BulkScanSummary {
		return $this->summary;
	}

	/**
	 * Get queue progress.
	 *
	 * @return BulkQueueProgress
	 */
	public function queue_progress(): BulkQueueProgress {
		return $this->queue_progress;
	}

	/**
	 * Get queue summary.
	 *
	 * @return BulkQueueSummary
	 */
	public function queue_summary(): BulkQueueSummary {
		return $this->queue_summary;
	}

	/**
	 * Serialize the session.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'token'          => $this->token(),
			'owner_user_id'  => $this->owner_user_id(),
			'created_at_gmt' => $this->created_at_gmt(),
			'updated_at_gmt' => $this->updated_at_gmt(),
			'filters'        => $this->filters()->to_array(),
			'progress'       => $this->progress()->to_array(),
			'summary'        => $this->summary()->to_array(),
			'queue_progress' => $this->queue_progress()->to_array(),
			'queue_summary'  => $this->queue_summary()->to_array(),
		);
	}

	/**
	 * Normalize one GMT timestamp string.
	 *
	 * @param string $value Raw timestamp.
	 * @return string
	 */
	private static function normalize_gmt( string $value ): string {
		$value = trim( $value );

		return '' === $value ? gmdate( 'Y-m-d H:i:s' ) : $value;
	}
}
