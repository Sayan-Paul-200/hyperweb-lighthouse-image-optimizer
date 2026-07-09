<?php
/**
 * Scheduled action cleanup contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Cleans up plugin-owned scheduled maintenance actions.
 */
interface ScheduledActionCleanerInterface {

	/**
	 * Unschedule plugin-owned recurring maintenance actions.
	 *
	 * @return LifecycleResult
	 */
	public function unschedule_recurring_maintenance(): LifecycleResult;
}
