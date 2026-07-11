<?php
/**
 * Fake queue implementation.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationJob;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;

/**
 * Records queued optimization jobs in tests.
 */
final class FakeQueue implements QueueInterface {

	/**
	 * Whether the queue is available.
	 *
	 * @var bool
	 */
	public $available = true;

	/**
	 * Enqueued jobs.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $jobs = array();

	/**
	 * Result to return.
	 *
	 * @var QueueStatus
	 */
	private $result;

	/**
	 * Optional per-call results.
	 *
	 * @var QueueStatus[]
	 */
	public $results = array();

	/**
	 * Create fake queue.
	 *
	 * @param QueueStatus|null $result Optional result.
	 */
	public function __construct( ?QueueStatus $result = null ) {
		$this->result = $result ?? QueueStatus::queued( 123, true, null );
	}

	/**
	 * Determine whether the queue is available.
	 *
	 * @return bool
	 */
	public function available(): bool {
		return $this->available;
	}

	/**
	 * Record one optimization job enqueue.
	 *
	 * @param OptimizationJob $job Optimization job.
	 * @param int             $delay_seconds Relative delay before execution.
	 * @return QueueStatus
	 */
	public function enqueue_optimization( OptimizationJob $job, int $delay_seconds = 0 ): QueueStatus {
		$this->jobs[] = array(
			'job'           => $job,
			'delay_seconds' => $delay_seconds,
		);

		if ( array() !== $this->results ) {
			$result = array_shift( $this->results );

			if ( $result instanceof QueueStatus ) {
				return $result;
			}
		}

		return $this->result;
	}
}
