<?php
/**
 * Action Scheduler attachment job control.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Counts and cancels plugin-owned attachment actions through Action Scheduler.
 */
final class ActionSchedulerAttachmentJobControl implements AttachmentJobControlInterface {

	private const QUERY_BATCH_SIZE = 25;

	/**
	 * Action group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Hook names.
	 *
	 * @var string[]
	 */
	private $hooks;

	/**
	 * Readiness callback.
	 *
	 * @var callable
	 */
	private $is_ready;

	/**
	 * Query callback.
	 *
	 * @var callable
	 */
	private $query_actions;

	/**
	 * Cancel callback.
	 *
	 * @var callable
	 */
	private $cancel_action;

	/**
	 * Build the WordPress-backed controller.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			LifecyclePolicy::ACTION_GROUP,
			LifecyclePolicy::attachment_job_hooks(),
			static function (): bool {
				return function_exists( 'as_get_scheduled_actions' )
					&& function_exists( 'as_unschedule_action' )
					&& class_exists( 'ActionScheduler', false )
					&& is_callable( array( 'ActionScheduler', 'is_initialized' ) )
					&& (bool) call_user_func( array( 'ActionScheduler', 'is_initialized' ) );
			},
			static function ( array $query ): array {
				$actions = function_exists( 'as_get_scheduled_actions' ) ? as_get_scheduled_actions( $query, 'OBJECT' ) : array();
				return is_array( $actions ) ? $actions : array();
			},
			static function ( string $hook, array $args, string $group ) {
				return function_exists( 'as_unschedule_action' ) ? as_unschedule_action( $hook, $args, $group ) : false;
			}
		);
	}

	/**
	 * Create the controller.
	 *
	 * @param string   $group Action group.
	 * @param string[] $hooks Hook names.
	 * @param callable $is_ready Readiness callback.
	 * @param callable $query_actions Query callback.
	 * @param callable $cancel_action Cancel callback.
	 */
	public function __construct(
		string $group,
		array $hooks,
		callable $is_ready,
		callable $query_actions,
		callable $cancel_action
	) {
		$this->group         = $group;
		$this->hooks         = array_values( array_filter( array_map( 'strval', $hooks ) ) );
		$this->is_ready      = $is_ready;
		$this->query_actions = $query_actions;
		$this->cancel_action = $cancel_action;
	}

	/**
	 * Count pending plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function pending_count(): int {
		return $this->count_actions( 'pending' );
	}

	/**
	 * Count in-progress plugin-owned attachment jobs.
	 *
	 * @return int
	 */
	public function in_progress_count(): int {
		return $this->count_actions( 'in-progress' );
	}

	/**
	 * Cancel pending plugin-owned attachment jobs.
	 *
	 * @return AttachmentJobControlResult
	 */
	public function cancel_pending(): AttachmentJobControlResult {
		if ( ! (bool) call_user_func( $this->is_ready ) ) {
			return AttachmentJobControlResult::failure(
				array( AttachmentJobControlResult::CODE_UNAVAILABLE ),
				array( 'Pending attachment jobs could not be queried because Action Scheduler is unavailable or not initialized.' )
			);
		}

		$cancelled = 0;
		$warnings  = array();

		foreach ( $this->hooks as $hook ) {
			foreach ( $this->matching_action_args( $hook ) as $args ) {
				$cancelled_id = call_user_func( $this->cancel_action, $hook, $args, $this->group );

				if ( empty( $cancelled_id ) ) {
					$warnings[] = sprintf( 'A pending attachment job for hook %s could not be cancelled.', $hook );
					continue;
				}

				++$cancelled;
			}
		}

		if ( array() !== $warnings ) {
			return AttachmentJobControlResult::failure(
				array( AttachmentJobControlResult::CODE_CANCEL_FAILED ),
				$warnings,
				$cancelled
			);
		}

		return AttachmentJobControlResult::success(
			$cancelled,
			array( sprintf( 'Cancelled %d pending attachment job(s).', $cancelled ) )
		);
	}

	/**
	 * Count one action status across all plugin attachment hooks.
	 *
	 * @param string $status Action status.
	 * @return int
	 */
	private function count_actions( string $status ): int {
		if ( ! (bool) call_user_func( $this->is_ready ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $this->hooks as $hook ) {
			$offset = 0;

			do {
				$actions = call_user_func(
					$this->query_actions,
					array(
						'hook'     => $hook,
						'group'    => $this->group,
						'status'   => $status,
						'per_page' => self::QUERY_BATCH_SIZE,
						'offset'   => $offset,
						'orderby'  => 'date',
						'order'    => 'ASC',
					)
				);

				if ( ! is_array( $actions ) ) {
					$actions = array();
				}

				$count       += count( $actions );
				$action_count = count( $actions );
				$offset      += self::QUERY_BATCH_SIZE;
			} while ( self::QUERY_BATCH_SIZE === $action_count );
		}

		return $count;
	}

	/**
	 * Collect matching pending action args for one hook.
	 *
	 * @param string $hook Hook name.
	 * @return array<int,array<mixed>>
	 */
	private function matching_action_args( string $hook ): array {
		$offset = 0;
		$match  = array();

		do {
			$actions = call_user_func(
				$this->query_actions,
				array(
					'hook'     => $hook,
					'group'    => $this->group,
					'status'   => 'pending',
					'per_page' => self::QUERY_BATCH_SIZE,
					'offset'   => $offset,
					'orderby'  => 'date',
					'order'    => 'ASC',
				)
			);

			if ( ! is_array( $actions ) ) {
				$actions = array();
			}

			foreach ( $actions as $action ) {
				$args = $this->action_args( $action );

				if ( array() !== $args ) {
					$match[] = $args;
				}
			}

			$action_count = count( $actions );
			$offset      += self::QUERY_BATCH_SIZE;
		} while ( self::QUERY_BATCH_SIZE === $action_count );

		return $match;
	}

	/**
	 * Read action arguments from a scheduled action object or array.
	 *
	 * @param mixed $action Action object/array.
	 * @return array<mixed>
	 */
	private function action_args( $action ): array {
		if ( is_object( $action ) && method_exists( $action, 'get_args' ) ) {
			$args = $action->get_args();
			return is_array( $args ) ? $args : array();
		}

		if ( is_array( $action ) && isset( $action['args'] ) && is_array( $action['args'] ) ) {
			return $action['args'];
		}

		return array();
	}
}
