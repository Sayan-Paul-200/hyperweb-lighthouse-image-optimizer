<?php
/**
 * Log deletion result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries one bounded clear-all batch result.
 */
final class LogDeletionResult {

	/**
	 * Rows removed in this batch.
	 *
	 * @var int
	 */
	private $deleted_count;

	/**
	 * Whether the delete flow is complete.
	 *
	 * @var bool
	 */
	private $complete;

	/**
	 * Batch size used.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * Create the result.
	 *
	 * @param int  $deleted_count Rows removed in this batch.
	 * @param bool $complete Whether the delete flow is complete.
	 * @param int  $batch_size Batch size used.
	 */
	public function __construct( int $deleted_count, bool $complete, int $batch_size ) {
		$this->deleted_count = max( 0, $deleted_count );
		$this->complete      = $complete;
		$this->batch_size    = max( 1, $batch_size );
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'deletedCount' => $this->deleted_count,
			'complete'     => $this->complete,
			'batchSize'    => $this->batch_size,
		);
	}
}
