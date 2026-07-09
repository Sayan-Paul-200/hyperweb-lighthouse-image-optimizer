<?php
/**
 * Deactivation orchestration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Runs non-destructive deactivation cleanup.
 */
final class Deactivator {

	/**
	 * Scheduled action cleaner.
	 *
	 * @var ScheduledActionCleanerInterface
	 */
	private $scheduled_actions;

	/**
	 * Build a WordPress-backed deactivator.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( ActionSchedulerScheduledActionCleaner::for_plugin() );
	}

	/**
	 * Create the deactivator.
	 *
	 * @param ScheduledActionCleanerInterface $scheduled_actions Scheduled action cleaner.
	 */
	public function __construct( ScheduledActionCleanerInterface $scheduled_actions ) {
		$this->scheduled_actions = $scheduled_actions;
	}

	/**
	 * Run deactivation cleanup.
	 *
	 * @return LifecycleResult
	 */
	public function deactivate(): LifecycleResult {
		return LifecycleResult::combine(
			$this->scheduled_actions->unschedule_recurring_maintenance(),
			LifecycleResult::success( array( LifecycleResult::CODE_DEACTIVATION_COMPLETE ) )
		);
	}
}
