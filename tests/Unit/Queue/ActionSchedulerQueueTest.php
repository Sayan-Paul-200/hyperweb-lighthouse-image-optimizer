<?php
/**
 * Tests for the Action Scheduler queue adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\ActionSchedulerQueue;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationJob;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Action Scheduler queue behavior through injected seams.
 */
final class ActionSchedulerQueueTest extends TestCase {

	/**
	 * Test available returns false when Action Scheduler is unavailable.
	 *
	 * @return void
	 */
	public function test_available_returns_false_when_action_scheduler_is_unavailable(): void {
		$queue = $this->queue_with( false );

		self::assertFalse( $queue->available() );
	}

	/**
	 * Test async enqueue path.
	 *
	 * @return void
	 */
	public function test_enqueue_optimization_uses_async_path_without_delay(): void {
		$async_calls = array();
		$queue       = $this->queue_with(
			true,
			static function ( array $query ): array {
				unset( $query );
				return array();
			},
			static function ( string $hook, array $args, string $group, bool $unique, int $priority ) use ( &$async_calls ) {
				$async_calls[] = array(
					'hook'     => $hook,
					'args'     => $args,
					'group'    => $group,
					'unique'   => $unique,
					'priority' => $priority,
				);
				return 101;
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 5, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) )
		);

		self::assertTrue( $status->is_successful() );
		self::assertTrue( $status->is_async() );
		self::assertSame( 101, $status->action_id() );
		self::assertCount( 1, $async_calls );
		self::assertSame( LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT, $async_calls[0]['hook'] );
		self::assertSame( LifecyclePolicy::ACTION_GROUP, $async_calls[0]['group'] );
		self::assertTrue( $async_calls[0]['unique'] );
	}

	/**
	 * Test delayed enqueue path.
	 *
	 * @return void
	 */
	public function test_enqueue_optimization_uses_delayed_path_with_delay(): void {
		$single_calls = array();
		$queue        = $this->queue_with(
			true,
			static function ( array $query ): array {
				unset( $query );
				return array();
			},
			static function ( string $hook, array $args, string $group, bool $unique, int $priority ): int {
				unset( $hook, $args, $group, $unique, $priority );
				return 0;
			},
			static function ( int $timestamp, string $hook, array $args, string $group, bool $unique, int $priority ) use ( &$single_calls ) {
				$single_calls[] = array(
					'timestamp' => $timestamp,
					'hook'      => $hook,
					'args'      => $args,
					'group'     => $group,
					'unique'    => $unique,
					'priority'  => $priority,
				);
				return 202;
			},
			static function (): int {
				return 1000;
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 7, 'avif', 2, true, 'retry', str_repeat( 'b', 20 ) ),
			15
		);

		self::assertTrue( $status->is_successful() );
		self::assertFalse( $status->is_async() );
		self::assertSame( 1015, $status->scheduled_timestamp() );
		self::assertCount( 1, $single_calls );
		self::assertSame( 1015, $single_calls[0]['timestamp'] );
	}

	/**
	 * Test pending duplicates are treated as already queued.
	 *
	 * @return void
	 */
	public function test_pending_duplicate_is_treated_as_already_queued(): void {
		$query_count = 0;
		$queue       = $this->queue_with(
			true,
			static function ( array $query ) use ( &$query_count ): array {
				++$query_count;

				if ( 'pending' !== $query['status'] ) {
					return array();
				}

				return array(
					array(
						'args' => array(
							'attachment_id' => 9,
							'format'        => 'webp',
							'cursor'        => 1,
							'force'         => false,
							'reason'        => 'retry',
							'fingerprint'   => str_repeat( 'c', 20 ),
						),
					),
				);
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 9, 'webp', 1, false, 'new_upload', str_repeat( 'c', 20 ) )
		);

		self::assertTrue( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) );
		self::assertSame( 1, $query_count );
	}

	/**
	 * Test in-progress duplicates are treated as already queued.
	 *
	 * @return void
	 */
	public function test_in_progress_duplicate_is_treated_as_already_queued(): void {
		$queries = array();
		$queue   = $this->queue_with(
			true,
			static function ( array $query ) use ( &$queries ): array {
				$queries[] = $query;

				if ( 'in-progress' !== $query['status'] ) {
					return array();
				}

				return array(
					array(
						'args' => array(
							'attachment_id' => 11,
							'format'        => 'avif',
							'cursor'        => 0,
							'force'         => true,
							'reason'        => 'manual',
							'fingerprint'   => str_repeat( 'd', 20 ),
						),
					),
				);
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 11, 'avif', 0, true, 'retry', str_repeat( 'd', 20 ) )
		);

		self::assertTrue( $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) );
		self::assertSame( 'pending', $queries[0]['status'] );
		self::assertSame( 'in-progress', $queries[1]['status'] );
	}

	/**
	 * Test different fingerprints are not treated as duplicates.
	 *
	 * @return void
	 */
	public function test_different_fingerprint_is_not_treated_as_duplicate(): void {
		$async_calls = 0;
		$queue       = $this->queue_with(
			true,
			static function ( array $query ): array {
				if ( 'pending' !== $query['status'] ) {
					return array();
				}

				return array(
					array(
						'args' => array(
							'attachment_id' => 12,
							'format'        => 'webp',
							'cursor'        => 0,
							'force'         => false,
							'reason'        => 'manual',
							'fingerprint'   => str_repeat( 'a', 20 ),
						),
					),
				);
			},
			static function ( string $hook, array $args, string $group, bool $unique, int $priority ) use ( &$async_calls ): int {
				unset( $hook, $args, $group, $unique, $priority );
				++$async_calls;
				return 303;
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 12, 'webp', 0, false, 'manual', str_repeat( 'b', 20 ) )
		);

		self::assertTrue( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_QUEUED ) );
		self::assertSame( 1, $async_calls );
	}

	/**
	 * Test malformed existing action args are ignored.
	 *
	 * @return void
	 */
	public function test_malformed_existing_action_args_are_ignored(): void {
		$async_calls = 0;
		$queue       = $this->queue_with(
			true,
			static function ( array $query ): array {
				if ( 'pending' !== $query['status'] ) {
					return array();
				}

				return array(
					array( 'args' => array( 'attachment_id' => 'bad' ) ),
					array(
						'args' => array(
							'attachment_id' => 9,
							'format'        => 'jpeg',
						),
					),
				);
			},
			static function ( string $hook, array $args, string $group, bool $unique, int $priority ) use ( &$async_calls ): int {
				unset( $hook, $args, $group, $unique, $priority );
				++$async_calls;
				return 404;
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 9, 'webp', 0, false, 'manual', str_repeat( 'e', 20 ) )
		);

		self::assertTrue( $status->is_successful() );
		self::assertSame( 1, $async_calls );
	}

	/**
	 * Test invalid jobs return invalid payload status.
	 *
	 * @return void
	 */
	public function test_invalid_job_returns_invalid_payload_status(): void {
		$queue = $this->queue_with( true );

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 0, 'gif', 0, false, 'manual', 'bad' )
		);

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_INVALID_JOB_PAYLOAD ) );
	}

	/**
	 * Test query callback exceptions degrade to queue unavailable.
	 *
	 * @return void
	 */
	public function test_query_callback_exception_degrades_to_queue_unavailable(): void {
		$queue = $this->queue_with(
			true,
			static function ( array $query ): array {
				unset( $query );
				throw new \RuntimeException( 'Query failed.' );
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 13, 'webp', 0, false, 'manual', str_repeat( 'f', 20 ) )
		);

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_QUEUE_UNAVAILABLE ) );
	}

	/**
	 * Test enqueue callback exceptions degrade to enqueue failed.
	 *
	 * @return void
	 */
	public function test_enqueue_callback_exception_degrades_to_enqueue_failed(): void {
		$queue = $this->queue_with(
			true,
			static function ( array $query ): array {
				unset( $query );
				return array();
			},
			static function ( string $hook, array $args, string $group, bool $unique, int $priority ): int {
				unset( $hook, $args, $group, $unique, $priority );
				throw new \RuntimeException( 'Enqueue failed.' );
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 15, 'avif', 0, false, 'manual', str_repeat( 'a', 20 ) )
		);

		self::assertFalse( $status->is_successful() );
		self::assertTrue( $status->has_code( QueueStatus::CODE_ENQUEUE_FAILED ) );
	}

	/**
	 * Test duplicate detection queries in bounded pages.
	 *
	 * @return void
	 */
	public function test_duplicate_detection_queries_in_bounded_pages(): void {
		$offsets = array();
		$queue   = $this->queue_with(
			true,
			static function ( array $query ) use ( &$offsets ): array {
				if ( 'pending' !== $query['status'] ) {
					return array();
				}

				$offsets[] = $query['offset'];

				if ( 0 === $query['offset'] ) {
					return array_fill( 0, 25, array( 'args' => array( 'attachment_id' => 'bad' ) ) );
				}

				if ( 25 === $query['offset'] ) {
					return array(
						array(
							'args' => array(
								'attachment_id' => 21,
								'format'        => 'webp',
								'cursor'        => 4,
								'force'         => false,
								'reason'        => 'retry',
								'fingerprint'   => str_repeat( 'c', 20 ),
							),
						),
					);
				}

				return array();
			}
		);

		$status = $queue->enqueue_optimization(
			new OptimizationJob( 21, 'webp', 4, false, 'manual', str_repeat( 'c', 20 ) )
		);

		self::assertTrue( $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) );
		self::assertSame( array( 0, 25 ), $offsets );
	}

	/**
	 * Create a queue adapter with overrideable seams.
	 *
	 * @param bool          $available Whether the queue is available.
	 * @param callable|null $query_actions Query callback.
	 * @param callable|null $enqueue_async Async enqueue callback.
	 * @param callable|null $schedule_single Delayed enqueue callback.
	 * @param callable|null $now Clock callback.
	 * @return ActionSchedulerQueue
	 */
	private function queue_with(
		bool $available,
		?callable $query_actions = null,
		?callable $enqueue_async = null,
		?callable $schedule_single = null,
		?callable $now = null
	): ActionSchedulerQueue {
		return new ActionSchedulerQueue(
			LifecyclePolicy::ACTION_GROUP,
			LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT,
			static function () use ( $available ): bool {
				return $available;
			},
			$query_actions ?? static function ( array $query ): array {
				unset( $query );
				return array();
			},
			$enqueue_async ?? static function ( string $hook, array $args, string $group, bool $unique, int $priority ): int {
				unset( $hook, $args, $group, $unique, $priority );
				return 1;
			},
			$schedule_single ?? static function ( int $timestamp, string $hook, array $args, string $group, bool $unique, int $priority ): int {
				unset( $timestamp, $hook, $args, $group, $unique, $priority );
				return 2;
			},
			$now ?? static function (): int {
				return 100;
			}
		);
	}
}
