<?php
/**
 * Queue contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Queues optimization jobs for asynchronous processing.
 */
interface QueueInterface {

	/**
	 * Determine whether the queue backend is available.
	 *
	 * @return bool
	 */
	public function available(): bool;

	/**
	 * Enqueue one optimization job.
	 *
	 * @param OptimizationJob $job Optimization job.
	 * @param int             $delay_seconds Relative delay before execution.
	 * @return QueueStatus
	 */
	public function enqueue_optimization( OptimizationJob $job, int $delay_seconds = 0 ): QueueStatus;

	/**
	 * Enqueue one reconciliation job.
	 *
	 * @param ReconciliationJob $job Reconciliation job.
	 * @param int               $delay_seconds Relative delay before execution.
	 * @return QueueStatus
	 */
	public function enqueue_reconciliation( ReconciliationJob $job, int $delay_seconds = 0 ): QueueStatus;
}
