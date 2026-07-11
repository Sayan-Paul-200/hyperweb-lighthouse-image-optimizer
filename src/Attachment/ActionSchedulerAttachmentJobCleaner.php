<?php
/**
 * Action Scheduler attachment job cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Queries and cancels pending attachment jobs through Action Scheduler.
 */
final class ActionSchedulerAttachmentJobCleaner implements AttachmentJobCleanerInterface {

	private const QUERY_BATCH_SIZE = 25;

	/**
	 * Action Scheduler group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Hook names to inspect.
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
	 * Build the WordPress-backed job cleaner.
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
			array( self::class, 'query_wordpress_actions' ),
			array( self::class, 'cancel_wordpress_action' )
		);
	}

	/**
	 * Create cleaner.
	 *
	 * @param string   $group Action Scheduler group.
	 * @param string[] $hooks Attachment job hooks.
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
		$this->hooks         = $this->normalize_hooks( $hooks );
		$this->is_ready      = $is_ready;
		$this->query_actions = $query_actions;
		$this->cancel_action = $cancel_action;
	}

	/**
	 * Cancel pending plugin-owned jobs for one attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	public function cancel_pending_actions( int $attachment_id ): AttachmentCleanupResult {
		$attachment_id = max( 0, $attachment_id );

		if ( 0 === $attachment_id ) {
			return AttachmentCleanupResult::failure(
				array( AttachmentCleanupResult::CODE_INVALID_ATTACHMENT ),
				array( 'Attachment job cleanup requires a valid attachment ID.' )
			);
		}

		if ( ! (bool) call_user_func( $this->is_ready ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_UNAVAILABLE ),
				array( 'Pending attachment jobs could not be queried because Action Scheduler is unavailable or not initialized.' )
			);
		}

		$cancelled = 0;
		$warnings  = array();

		foreach ( $this->hooks as $hook ) {
			foreach ( $this->matching_action_args( $hook, $attachment_id ) as $args ) {
				$cancelled_id = call_user_func( $this->cancel_action, $hook, $args, $this->group );

				if ( empty( $cancelled_id ) ) {
					$warnings[] = sprintf( 'A pending attachment job for hook %s could not be cancelled.', $hook );
					continue;
				}

				++$cancelled;
			}
		}

		$result = AttachmentCleanupResult::success(
			array( AttachmentCleanupResult::CODE_ATTACHMENT_JOBS_CANCELLED ),
			array( sprintf( 'Cancelled %d pending attachment job(s).', $cancelled ) ),
			0,
			$cancelled
		);

		if ( array() === $warnings ) {
			return $result;
		}

		return AttachmentCleanupResult::combine(
			$result,
			AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_ATTACHMENT_JOB_CANCEL_FAILED ),
				$warnings
			)
		);
	}

	/**
	 * Collect action arguments for one attachment and hook.
	 *
	 * @param string $hook Hook.
	 * @param int    $attachment_id Attachment ID.
	 * @return array<int,array<mixed>>
	 */
	private function matching_action_args( string $hook, int $attachment_id ): array {
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

				if ( $attachment_id === $this->attachment_id_from_args( $args ) ) {
					$match[] = $args;
				}
			}

			$action_count = count( $actions );
			$offset      += self::QUERY_BATCH_SIZE;
		} while ( self::QUERY_BATCH_SIZE === $action_count );

		return $match;
	}

	/**
	 * Read action arguments from a queried action object.
	 *
	 * @param mixed $action Action object.
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

	/**
	 * Extract attachment ID from action args.
	 *
	 * @param array<mixed> $args Action args.
	 * @return int
	 */
	private function attachment_id_from_args( array $args ): int {
		return isset( $args['attachment_id'] ) && is_numeric( $args['attachment_id'] )
			? max( 0, (int) $args['attachment_id'] )
			: 0;
	}

	/**
	 * Normalize hook names.
	 *
	 * @param string[] $hooks Hooks.
	 * @return string[]
	 */
	private function normalize_hooks( array $hooks ): array {
		$normalized = array();

		foreach ( $hooks as $hook ) {
			if ( ! is_scalar( $hook ) ) {
				continue;
			}

			$hook = trim( (string) $hook );

			if ( '' !== $hook ) {
				$normalized[] = $hook;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Query scheduled actions through the global Action Scheduler function.
	 *
	 * @param array<string,mixed> $query Query args.
	 * @return array<int,mixed>
	 */
	private static function query_wordpress_actions( array $query ): array {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return array();
		}

		$actions = as_get_scheduled_actions( $query, 'OBJECT' );

		return is_array( $actions ) ? $actions : array();
	}

	/**
	 * Cancel one scheduled action through the global Action Scheduler function.
	 *
	 * @param string       $hook Hook name.
	 * @param array<mixed> $args Action args.
	 * @param string       $group Action group.
	 * @return mixed
	 */
	private static function cancel_wordpress_action( string $hook, array $args, string $group ) {
		if ( ! function_exists( 'as_unschedule_action' ) ) {
			return false;
		}

		return as_unschedule_action( $hook, $args, $group );
	}
}
