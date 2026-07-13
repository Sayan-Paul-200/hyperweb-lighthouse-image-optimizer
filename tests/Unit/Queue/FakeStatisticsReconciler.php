<?php
/**
 * Fake statistics reconciler.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsReconciliationResult;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsReconcilerInterface;

/**
 * Returns a configured statistics reconciliation result.
 */
final class FakeStatisticsReconciler implements StatisticsReconcilerInterface {

	/**
	 * Invocation count.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Result to return.
	 *
	 * @var StatisticsReconciliationResult
	 */
	private $result;

	/**
	 * Create fake reconciler.
	 *
	 * @param StatisticsReconciliationResult|null $result Optional result.
	 */
	public function __construct( ?StatisticsReconciliationResult $result = null ) {
		$this->result = $result ?? StatisticsReconciliationResult::success( StatisticsCache::empty( '2026-07-11 00:00:00' ) );
	}

	/**
	 * Reconcile statistics.
	 *
	 * @return StatisticsReconciliationResult
	 */
	public function reconcile(): StatisticsReconciliationResult {
		++$this->calls;

		return $this->result;
	}
}
