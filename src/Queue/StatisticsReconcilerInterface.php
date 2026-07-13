<?php
/**
 * Statistics reconciler contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Reconciles the internal statistics cache.
 */
interface StatisticsReconcilerInterface {

	/**
	 * Recalculate and persist the internal statistics cache.
	 *
	 * @return StatisticsReconciliationResult
	 */
	public function reconcile(): StatisticsReconciliationResult;
}
