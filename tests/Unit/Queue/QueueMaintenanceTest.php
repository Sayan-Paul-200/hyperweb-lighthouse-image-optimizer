<?php
/**
 * Queue maintenance tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueMaintenance;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsReconciliationResult;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentLockScanner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentLockTokenGenerator;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeRecurringActionScheduler;
use PHPUnit\Framework\TestCase;

/**
 * Verifies recurring maintenance scheduling and execution.
 */
final class QueueMaintenanceTest extends TestCase {

	private const NOW = 1783555200;

	/**
	 * Test hook registration.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_scheduler_and_maintenance_actions(): void {
		$hooks       = new HookRegistrar();
		$maintenance = $this->build_provider();

		$maintenance->register_hooks( $hooks );

		self::assertCount( 4, $hooks->actions() );
		self::assertSame( 'action_scheduler_init', $hooks->actions()[0]['hook'] );
		self::assertSame( LifecyclePolicy::ACTION_RECOVER_STALE_LOCKS, $hooks->actions()[1]['hook'] );
		self::assertSame( LifecyclePolicy::ACTION_RECONCILE_STATISTICS, $hooks->actions()[2]['hook'] );
		self::assertSame( LifecyclePolicy::ACTION_RECALCULATE_STATISTICS, $hooks->actions()[3]['hook'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
		self::assertSame( 0, $hooks->actions()[1]['accepted_args'] );
		self::assertSame( 0, $hooks->actions()[2]['accepted_args'] );
		self::assertSame( 0, $hooks->actions()[3]['accepted_args'] );
	}

	/**
	 * Test recurring schedules are added uniquely.
	 *
	 * @return void
	 */
	public function test_ensure_scheduled_adds_hourly_and_daily_actions(): void {
		$scheduler   = new FakeRecurringActionScheduler();
		$maintenance = $this->build_provider( $scheduler );

		$maintenance->ensure_scheduled();

		self::assertSame( 2, $scheduler->schedule_calls );
		self::assertCount( 2, $scheduler->scheduled_actions );
		self::assertSame( LifecyclePolicy::ACTION_RECOVER_STALE_LOCKS, $scheduler->scheduled_actions[0]['hook'] );
		self::assertSame( self::NOW + QueueMaintenance::STALE_LOCK_INTERVAL, $scheduler->scheduled_actions[0]['timestamp'] );
		self::assertSame( QueueMaintenance::STALE_LOCK_INTERVAL, $scheduler->scheduled_actions[0]['interval'] );
		self::assertSame( LifecyclePolicy::ACTION_GROUP, $scheduler->scheduled_actions[0]['group'] );
		self::assertTrue( $scheduler->scheduled_actions[0]['unique'] );
		self::assertSame( QueueMaintenance::PRIORITY, $scheduler->scheduled_actions[0]['priority'] );
		self::assertSame( LifecyclePolicy::ACTION_RECONCILE_STATISTICS, $scheduler->scheduled_actions[1]['hook'] );
		self::assertSame( self::NOW + QueueMaintenance::STATISTICS_INTERVAL, $scheduler->scheduled_actions[1]['timestamp'] );
		self::assertSame( QueueMaintenance::STATISTICS_INTERVAL, $scheduler->scheduled_actions[1]['interval'] );
		self::assertSame( LifecyclePolicy::ACTION_GROUP, $scheduler->scheduled_actions[1]['group'] );
		self::assertTrue( $scheduler->scheduled_actions[1]['unique'] );
		self::assertSame( QueueMaintenance::PRIORITY, $scheduler->scheduled_actions[1]['priority'] );
	}

	/**
	 * Test existing recurring schedules are not duplicated.
	 *
	 * @return void
	 */
	public function test_ensure_scheduled_does_not_duplicate_existing_actions(): void {
		$scheduler             = new FakeRecurringActionScheduler();
		$scheduler->has_action = true;
		$maintenance           = $this->build_provider( $scheduler );

		$maintenance->ensure_scheduled();

		self::assertSame( 0, $scheduler->schedule_calls );
	}

	/**
	 * Test stale lock recovery marks processing attachments stale and preserves others.
	 *
	 * @return void
	 */
	public function test_stale_lock_recovery_repairs_processing_status_and_logs_info(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_LOCK ]   = $this->lock_payload( 'stale-one', self::NOW - 700, self::NOW - 100 );
		$store->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_PROCESSING,
			'formats'    => array( 'webp' ),
			'updated_at' => self::NOW - 50,
			'error_code' => null,
			'excluded'   => true,
		);
		$store->meta[124][ LifecyclePolicy::META_LOCK ]   = $this->lock_payload( 'stale-two', self::NOW - 700, self::NOW - 100 );
		$store->meta[124][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_OPTIMIZED,
			'formats'    => array( 'webp', 'avif' ),
			'updated_at' => self::NOW - 50,
			'error_code' => null,
			'excluded'   => false,
		);

		$logger      = new FakeLogger();
		$maintenance = $this->build_provider(
			new FakeRecurringActionScheduler(),
			$store,
			new FakeAttachmentLockScanner( array( 123, 124 ) ),
			new FakeStatisticsReconciler(),
			$logger
		);

		$maintenance->run_stale_lock_recovery();

		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );
		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[124] );
		self::assertSame( AttachmentStatus::STATE_STALE, $store->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( array( 'webp' ), $store->meta[123][ LifecyclePolicy::META_STATUS ]['formats'] );
		self::assertTrue( $store->meta[123][ LifecyclePolicy::META_STATUS ]['excluded'] );
		self::assertSame( AttachmentStatus::STATE_OPTIMIZED, $store->meta[124][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertCount( 1, $logger->entries );
		self::assertSame( 'info', $logger->entries[0]['level'] );
		self::assertSame( LogCode::MAINTENANCE_STALE_LOCKS_RECOVERED, $logger->entries[0]['code'] );
		self::assertSame( 1, $logger->entries[0]['context']['status_repairs'] );
	}

	/**
	 * Test stale lock recovery emits no log when nothing happened.
	 *
	 * @return void
	 */
	public function test_stale_lock_recovery_skips_logging_for_noop_runs(): void {
		$logger      = new FakeLogger();
		$maintenance = $this->build_provider(
			new FakeRecurringActionScheduler(),
			new FakeAttachmentMetaStore(),
			new FakeAttachmentLockScanner( array() ),
			new FakeStatisticsReconciler(),
			$logger
		);

		$maintenance->run_stale_lock_recovery();

		self::assertSame( array(), $logger->entries );
	}

	/**
	 * Test stale lock recovery warnings are logged on recovery failures.
	 *
	 * @return void
	 */
	public function test_stale_lock_recovery_logs_warning_on_recovery_failure(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_LOCK ] = $this->lock_payload( 'old-token', self::NOW - 700, self::NOW - 100 );
		$store->before_delete_value                     = static function ( int $attachment_id, string $key ) use ( $store ): void {
			$store->meta[ $attachment_id ][ $key ] = array(
				'token'      => 'new-worker-token',
				'created_at' => self::NOW,
				'expires_at' => self::NOW + 600,
			);
		};

		$logger      = new FakeLogger();
		$maintenance = $this->build_provider(
			new FakeRecurringActionScheduler(),
			$store,
			new FakeAttachmentLockScanner( array( 123 ) ),
			new FakeStatisticsReconciler(),
			$logger
		);

		$maintenance->run_stale_lock_recovery();

		self::assertCount( 1, $logger->entries );
		self::assertSame( 'warning', $logger->entries[0]['level'] );
		self::assertSame( LogCode::MAINTENANCE_STALE_LOCK_RECOVERY_FAILED, $logger->entries[0]['code'] );
		self::assertSame( 1, $logger->entries[0]['context']['failed'] );
	}

	/**
	 * Test statistics reconciliation success logs an informational entry.
	 *
	 * @return void
	 */
	public function test_statistics_reconciliation_logs_success(): void {
		$logger      = new FakeLogger();
		$maintenance = $this->build_provider(
			new FakeRecurringActionScheduler(),
			new FakeAttachmentMetaStore(),
			new FakeAttachmentLockScanner( array() ),
			new FakeStatisticsReconciler(
				StatisticsReconciliationResult::success(
					new StatisticsCache(
						'2026-07-11 12:00:00',
						array( 'optimized' => 2 ),
						array( 'attachments_considered' => 2 ),
						array()
					)
				)
			),
			$logger
		);

		$maintenance->run_statistics_reconciliation();

		self::assertCount( 1, $logger->entries );
		self::assertSame( 'info', $logger->entries[0]['level'] );
		self::assertSame( LogCode::MAINTENANCE_STATISTICS_RECONCILED, $logger->entries[0]['code'] );
		self::assertSame( 2, $logger->entries[0]['context']['totals']['attachments_considered'] );
	}

	/**
	 * Test statistics reconciliation failure logs a warning.
	 *
	 * @return void
	 */
	public function test_statistics_reconciliation_logs_warning_when_reconcile_fails(): void {
		$logger      = new FakeLogger();
		$maintenance = $this->build_provider(
			new FakeRecurringActionScheduler(),
			new FakeAttachmentMetaStore(),
			new FakeAttachmentLockScanner( array() ),
			new FakeStatisticsReconciler(
				StatisticsReconciliationResult::failure(
					array( StatisticsReconciliationResult::CODE_WRITE_FAILED ),
					array( 'Statistics cache could not be saved.' ),
					StatisticsCache::empty( '2026-07-11 12:00:00' )
				)
			),
			$logger
		);

		$maintenance->run_statistics_reconciliation();

		self::assertCount( 1, $logger->entries );
		self::assertSame( 'warning', $logger->entries[0]['level'] );
		self::assertSame( LogCode::MAINTENANCE_STATISTICS_RECONCILE_FAILED, $logger->entries[0]['code'] );
		self::assertSame( array( StatisticsReconciliationResult::CODE_WRITE_FAILED ), $logger->entries[0]['context']['codes'] );
	}

	/**
	 * Build provider with injected fakes.
	 *
	 * @param FakeRecurringActionScheduler|null $scheduler Scheduler.
	 * @param FakeAttachmentMetaStore|null      $store Attachment meta store.
	 * @param FakeAttachmentLockScanner|null    $scanner Lock scanner.
	 * @param FakeStatisticsReconciler|null     $statistics Statistics reconciler.
	 * @param FakeLogger|null                   $logger Logger.
	 * @return QueueMaintenance
	 */
	private function build_provider(
		?FakeRecurringActionScheduler $scheduler = null,
		?FakeAttachmentMetaStore $store = null,
		?FakeAttachmentLockScanner $scanner = null,
		?FakeStatisticsReconciler $statistics = null,
		?FakeLogger $logger = null
	): QueueMaintenance {
		$store = $store ?? new FakeAttachmentMetaStore();

		return new QueueMaintenance(
			$scheduler ?? new FakeRecurringActionScheduler(),
			new AttachmentLockManager(
				$store,
				new FixedAttachmentLockTokenGenerator( array( 'unused-token' ) ),
				new FixedAttachmentClock( self::NOW ),
				$scanner ?? new FakeAttachmentLockScanner( array() )
			),
			new DerivativeRepository(
				$store,
				new DerivativeManifestSanitizer(),
				new FixedAttachmentClock( self::NOW )
			),
			$statistics ?? new FakeStatisticsReconciler(),
			$logger ?? new FakeLogger(),
			LifecyclePolicy::ACTION_GROUP,
			static function (): int {
				return self::NOW;
			}
		);
	}

	/**
	 * Build stored lock payload.
	 *
	 * @param string $token Token.
	 * @param int    $created_at Created timestamp.
	 * @param int    $expires_at Expiration timestamp.
	 * @return array<string,mixed>
	 */
	private function lock_payload( string $token, int $created_at, int $expires_at ): array {
		return array(
			'token'      => $token,
			'created_at' => $created_at,
			'expires_at' => $expires_at,
		);
	}
}
