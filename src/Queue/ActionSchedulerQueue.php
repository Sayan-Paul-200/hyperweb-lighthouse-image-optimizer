<?php
/**
 * Action Scheduler-backed queue adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Wraps Action Scheduler for optimization jobs.
 */
final class ActionSchedulerQueue implements QueueInterface {

	private const QUERY_BATCH_SIZE = 25;
	private const PRIORITY         = 10;

	/**
	 * Action group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Optimization hook.
	 *
	 * @var string
	 */
	private $hook;

	/**
	 * Reconciliation hook.
	 *
	 * @var string
	 */
	private $reconciliation_hook;

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
	 * Async enqueue callback.
	 *
	 * @var callable
	 */
	private $enqueue_async;

	/**
	 * Delayed enqueue callback.
	 *
	 * @var callable
	 */
	private $schedule_single;

	/**
	 * Clock callback.
	 *
	 * @var callable
	 */
	private $now;

	/**
	 * Build the WordPress-backed queue adapter.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			LifecyclePolicy::ACTION_GROUP,
			LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT,
			LifecyclePolicy::ACTION_RECONCILE_ATTACHMENT,
			static function (): bool {
				return function_exists( 'as_get_scheduled_actions' )
					&& function_exists( 'as_enqueue_async_action' )
					&& function_exists( 'as_schedule_single_action' )
					&& class_exists( 'ActionScheduler', false )
					&& is_callable( array( 'ActionScheduler', 'is_initialized' ) )
					&& (bool) call_user_func( array( 'ActionScheduler', 'is_initialized' ) );
			},
			array( self::class, 'query_wordpress_actions' ),
			array( self::class, 'enqueue_async_wordpress' ),
			array( self::class, 'schedule_single_wordpress' ),
			'time'
		);
	}

	/**
	 * Create adapter.
	 *
	 * @param string   $group Action group.
	 * @param string   $hook Optimization hook.
	 * @param string   $reconciliation_hook Reconciliation hook.
	 * @param callable $is_ready Readiness callback.
	 * @param callable $query_actions Query callback.
	 * @param callable $enqueue_async Async enqueue callback.
	 * @param callable $schedule_single Delayed enqueue callback.
	 * @param callable $now Clock callback.
	 */
	public function __construct(
		string $group,
		string $hook,
		string $reconciliation_hook,
		callable $is_ready,
		callable $query_actions,
		callable $enqueue_async,
		callable $schedule_single,
		callable $now
	) {
		$this->group               = $group;
		$this->hook                = trim( $hook );
		$this->reconciliation_hook = trim( $reconciliation_hook );
		$this->is_ready            = $is_ready;
		$this->query_actions       = $query_actions;
		$this->enqueue_async       = $enqueue_async;
		$this->schedule_single     = $schedule_single;
		$this->now                 = $now;
	}

	/**
	 * Determine whether the queue backend is available.
	 *
	 * @return bool
	 */
	public function available(): bool {
		try {
			return (bool) call_user_func( $this->is_ready );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Enqueue one optimization job.
	 *
	 * @param OptimizationJob $job Optimization job.
	 * @param int             $delay_seconds Relative delay before execution.
	 * @return QueueStatus
	 */
	public function enqueue_optimization( OptimizationJob $job, int $delay_seconds = 0 ): QueueStatus {
		if ( ! $job->is_valid() ) {
			return QueueStatus::invalid_job_payload(
				array( 'Optimization queue payload is invalid.' )
			);
		}

		if ( ! $this->available() ) {
			return QueueStatus::queue_unavailable(
				array( 'Action Scheduler is unavailable or not initialized.' )
			);
		}

		try {
			if ( $this->has_equivalent_optimization_action( $job ) ) {
				return QueueStatus::already_queued(
					array( 'An equivalent optimization job is already queued or running.' )
				);
			}
		} catch ( \Throwable $throwable ) {
			return QueueStatus::queue_unavailable(
				array( $throwable->getMessage() )
			);
		}

		$delay_seconds = max( 0, $delay_seconds );
		$payload       = $job->to_array();

		return $this->enqueue_payload(
			$this->hook,
			$payload,
			$delay_seconds,
			'Optimization job was queued successfully.',
			'Optimization job could not be queued.'
		);
	}

	/**
	 * Enqueue one reconciliation job.
	 *
	 * @param ReconciliationJob $job Reconciliation job.
	 * @param int               $delay_seconds Relative delay before execution.
	 * @return QueueStatus
	 */
	public function enqueue_reconciliation( ReconciliationJob $job, int $delay_seconds = 0 ): QueueStatus {
		if ( ! $job->is_valid() ) {
			return QueueStatus::invalid_job_payload(
				array( 'Reconciliation queue payload is invalid.' )
			);
		}

		if ( ! $this->available() ) {
			return QueueStatus::queue_unavailable(
				array( 'Action Scheduler is unavailable or not initialized.' )
			);
		}

		try {
			if ( $this->has_equivalent_reconciliation_action( $job ) ) {
				return QueueStatus::already_queued(
					array( 'An equivalent reconciliation job is already queued or running.' )
				);
			}
		} catch ( \Throwable $throwable ) {
			return QueueStatus::queue_unavailable(
				array( $throwable->getMessage() )
			);
		}

		return $this->enqueue_payload(
			$this->reconciliation_hook,
			$job->to_array(),
			max( 0, $delay_seconds ),
			'Reconciliation job was queued successfully.',
			'Reconciliation job could not be queued.'
		);
	}

	/**
	 * Determine whether an equivalent pending or running action already exists.
	 *
	 * @param OptimizationJob $job Optimization job.
	 * @return bool
	 */
	private function has_equivalent_optimization_action( OptimizationJob $job ): bool {
		return $this->has_equivalent_action(
			$this->hook,
			$job,
			static function ( array $args ): ?OptimizationJob {
				return OptimizationJob::from_array( $args );
			}
		);
	}

	/**
	 * Determine whether an equivalent pending or running reconciliation action already exists.
	 *
	 * @param ReconciliationJob $job Reconciliation job.
	 * @return bool
	 */
	private function has_equivalent_reconciliation_action( ReconciliationJob $job ): bool {
		return $this->has_equivalent_action(
			$this->reconciliation_hook,
			$job,
			static function ( array $args ): ?ReconciliationJob {
				return ReconciliationJob::from_array( $args );
			}
		);
	}

	/**
	 * Determine whether an equivalent pending or running action already exists.
	 *
	 * @param string   $hook Hook.
	 * @param mixed    $job Job.
	 * @param callable $from_array Payload parser.
	 * @return bool
	 */
	private function has_equivalent_action( string $hook, $job, callable $from_array ): bool {
		foreach ( array( 'pending', 'in-progress' ) as $status ) {
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

				foreach ( $actions as $action ) {
					$existing = call_user_func( $from_array, $this->action_args( $action ) );

					if ( is_object( $existing ) && method_exists( $job, 'equivalent_to' ) && $job->equivalent_to( $existing ) ) {
						return true;
					}
				}

				$action_count = count( $actions );
				$offset      += self::QUERY_BATCH_SIZE;
			} while ( self::QUERY_BATCH_SIZE === $action_count );
		}

		return false;
	}

	/**
	 * Enqueue a payload through Action Scheduler.
	 *
	 * @param string              $hook Hook.
	 * @param array<string,mixed> $payload Payload.
	 * @param int                 $delay_seconds Delay in seconds.
	 * @param string              $success_message Success message.
	 * @param string              $failure_message Failure message.
	 * @return QueueStatus
	 */
	private function enqueue_payload(
		string $hook,
		array $payload,
		int $delay_seconds,
		string $success_message,
		string $failure_message
	): QueueStatus {
		try {
			if ( 0 === $delay_seconds ) {
				$action_id = call_user_func(
					$this->enqueue_async,
					$hook,
					$payload,
					$this->group,
					true,
					self::PRIORITY
				);

				return $this->queued_result( $action_id, true, null, $success_message, $failure_message );
			}

			$scheduled_timestamp = (int) call_user_func( $this->now ) + $delay_seconds;
			$action_id           = call_user_func(
				$this->schedule_single,
				$scheduled_timestamp,
				$hook,
				$payload,
				$this->group,
				true,
				self::PRIORITY
			);

			return $this->queued_result( $action_id, false, $scheduled_timestamp, $success_message, $failure_message );
		} catch ( \Throwable $throwable ) {
			return QueueStatus::enqueue_failed(
				array( $throwable->getMessage() )
			);
		}
	}

	/**
	 * Read action args from a scheduled action object/array.
	 *
	 * @param mixed $action Action.
	 * @return array<string,mixed>
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
	 * Build a queued result or enqueue-failed result from a returned action ID.
	 *
	 * @param mixed    $action_id Action ID.
	 * @param bool     $async Whether async scheduling was used.
	 * @param int|null $scheduled_timestamp Scheduled timestamp.
	 * @param string   $success_message Success message.
	 * @param string   $failure_message Failure message.
	 * @return QueueStatus
	 */
	private function queued_result( $action_id, bool $async, ?int $scheduled_timestamp, string $success_message, string $failure_message ): QueueStatus {
		if ( is_numeric( $action_id ) && 0 < (int) $action_id ) {
			return QueueStatus::queued(
				(int) $action_id,
				$async,
				$scheduled_timestamp,
				array( $success_message )
			);
		}

		return QueueStatus::enqueue_failed(
			array( $failure_message )
		);
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
	 * Enqueue an async optimization action through Action Scheduler.
	 *
	 * @param string              $hook Hook.
	 * @param array<string,mixed> $args Args.
	 * @param string              $group Group.
	 * @param bool                $unique Unique flag.
	 * @param int                 $priority Priority.
	 * @return mixed
	 */
	private static function enqueue_async_wordpress(
		string $hook,
		array $args,
		string $group,
		bool $unique,
		int $priority
	) {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		return as_enqueue_async_action( $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule a delayed optimization action through Action Scheduler.
	 *
	 * @param int                 $timestamp Timestamp.
	 * @param string              $hook Hook.
	 * @param array<string,mixed> $args Args.
	 * @param string              $group Group.
	 * @param bool                $unique Unique flag.
	 * @param int                 $priority Priority.
	 * @return mixed
	 */
	private static function schedule_single_wordpress(
		int $timestamp,
		string $hook,
		array $args,
		string $group,
		bool $unique,
		int $priority
	) {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return false;
		}

		return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
	}
}
