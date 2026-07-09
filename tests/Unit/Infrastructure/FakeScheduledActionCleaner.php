<?php
/**
 * Fake scheduled action cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\ScheduledActionCleanerInterface;

/**
 * Records scheduled action cleanup calls.
 */
final class FakeScheduledActionCleaner implements ScheduledActionCleanerInterface {

	/**
	 * Number of cleanup calls.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Result to return.
	 *
	 * @var LifecycleResult
	 */
	private $result;

	/**
	 * Create the fake cleaner.
	 *
	 * @param LifecycleResult|null $result Optional result.
	 */
	public function __construct( ?LifecycleResult $result = null ) {
		$this->result = null !== $result
			? $result
			: LifecycleResult::success( array( LifecycleResult::CODE_MAINTENANCE_UNSCHEDULED ) );
	}

	/**
	 * Unschedule plugin-owned recurring maintenance actions.
	 *
	 * @return LifecycleResult
	 */
	public function unschedule_recurring_maintenance(): LifecycleResult {
		++$this->calls;

		return $this->result;
	}
}
