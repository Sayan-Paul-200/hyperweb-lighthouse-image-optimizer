<?php
/**
 * Action Scheduler maintenance cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Unschedules plugin-owned recurring maintenance actions through Action Scheduler.
 */
final class ActionSchedulerScheduledActionCleaner implements ScheduledActionCleanerInterface {

	/**
	 * Action group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Maintenance hooks.
	 *
	 * @var string[]
	 */
	private $hooks;

	/**
	 * Create the cleaner.
	 *
	 * @param string   $group Action Scheduler group.
	 * @param string[] $hooks Plugin-owned maintenance hooks.
	 */
	public function __construct( string $group, array $hooks ) {
		$this->group = $group;
		$this->hooks = array_values( $hooks );
	}

	/**
	 * Create the default plugin-owned cleaner.
	 *
	 * @return self
	 */
	public static function for_plugin(): self {
		return new self(
			LifecyclePolicy::ACTION_GROUP,
			LifecyclePolicy::maintenance_action_hooks()
		);
	}

	/**
	 * Unschedule plugin-owned recurring maintenance actions.
	 *
	 * @return LifecycleResult
	 */
	public function unschedule_recurring_maintenance(): LifecycleResult {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_MAINTENANCE_UNAVAILABLE ),
				array( 'Action Scheduler is unavailable; maintenance cleanup was skipped.' )
			);
		}

		try {
			foreach ( $this->hooks as $hook ) {
				\as_unschedule_all_actions( $hook, array(), $this->group );
			}
		} catch ( \Throwable $throwable ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_MAINTENANCE_UNAVAILABLE ),
				array( $throwable->getMessage() )
			);
		}

		return LifecycleResult::success( array( LifecycleResult::CODE_MAINTENANCE_UNSCHEDULED ) );
	}
}
