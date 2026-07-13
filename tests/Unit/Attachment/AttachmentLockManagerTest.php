<?php
/**
 * Tests for attachment locking.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLock;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockRecoveryResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticSanitizer;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies token-protected attachment lock behavior.
 */
final class AttachmentLockManagerTest extends TestCase {

	private const NOW = 1783526500;

	/**
	 * Test lock parsing, expiration, and safe serialization.
	 *
	 * @return void
	 */
	public function test_lock_parsing_expiration_and_safe_serialization(): void {
		$lock = new AttachmentLock( 'secret-token', 100, 700 );

		self::assertSame( 'secret-token', $lock->token() );
		self::assertFalse( $lock->is_expired( 699 ) );
		self::assertTrue( $lock->is_expired( 700 ) );
		self::assertSame( 100, $lock->created_at() );
		self::assertSame( 700, $lock->expires_at() );
		self::assertSame( 50, $lock->seconds_remaining( 650 ) );
		self::assertTrue( $lock->token_matches( 'secret-token' ) );
		self::assertFalse( $lock->token_matches( 'other-token' ) );
		$parsed = AttachmentLock::from_stored( $lock->to_storage_array() );
		self::assertInstanceOf( AttachmentLock::class, $parsed );
		self::assertSame( $lock->to_storage_array(), $parsed->to_storage_array() );
		self::assertArrayNotHasKey( 'token', $lock->to_array() );
		self::assertNull(
			AttachmentLock::from_stored(
				array(
					'created_at' => 100,
					'expires_at' => 700,
				)
			)
		);
		self::assertNull(
			AttachmentLock::from_stored(
				array(
					'token'      => 'x',
					'created_at' => 100,
					'expires_at' => 100,
				)
			)
		);
	}

	/**
	 * Test first acquisition succeeds and second active worker cannot acquire.
	 *
	 * @return void
	 */
	public function test_unique_acquisition_prevents_concurrent_workers(): void {
		$store   = new FakeAttachmentMetaStore();
		$manager = $this->manager( $store, array( 'worker-one', 'worker-two' ) );

		$first  = $manager->acquire( 123 );
		$second = $manager->acquire( 123 );

		self::assertTrue( $first->is_successful() );
		self::assertSame( 'worker-one', $store->meta[123][ LifecyclePolicy::META_LOCK ]['token'] );
		self::assertSame( self::NOW, $store->meta[123][ LifecyclePolicy::META_LOCK ]['created_at'] );
		self::assertSame( self::NOW + AttachmentLockManager::DEFAULT_TTL_SECONDS, $store->meta[123][ LifecyclePolicy::META_LOCK ]['expires_at'] );
		self::assertFalse( $second->is_successful() );
		self::assertTrue( $second->has_code( AttachmentLockResult::CODE_UNAVAILABLE ) );
		self::assertSame( 'worker-one', $store->meta[123][ LifecyclePolicy::META_LOCK ]['token'] );
		$serialized_first = $first->to_array();
		self::assertIsArray( $serialized_first['lock'] );
		self::assertArrayNotHasKey( 'token', $serialized_first['lock'] );
	}

	/**
	 * Test stale lock is recovered before acquisition.
	 *
	 * @return void
	 */
	public function test_stale_lock_recovers_before_acquisition(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_LOCK ] = $this->lock_payload( 'old-token', self::NOW - 700, self::NOW - 100 );

		$result = $this->manager( $store, array( 'candidate-token', 'new-token' ) )->acquire( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( AttachmentLockResult::CODE_STALE_RECOVERED ) );
		self::assertTrue( $result->has_code( AttachmentLockResult::CODE_ACQUIRED ) );
		self::assertSame( 'new-token', $store->meta[123][ LifecyclePolicy::META_LOCK ]['token'] );
	}

	/**
	 * Test malformed lock is recovered before acquisition.
	 *
	 * @return void
	 */
	public function test_malformed_lock_recovers_before_acquisition(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_LOCK ] = array(
			'token'      => '',
			'created_at' => self::NOW - 10,
			'expires_at' => self::NOW + 10,
		);

		$result = $this->manager( $store, array( 'candidate-token', 'new-token' ) )->acquire( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( AttachmentLockResult::CODE_INVALID_RECOVERED ) );
		self::assertSame( 'new-token', $store->meta[123][ LifecyclePolicy::META_LOCK ]['token'] );
	}

	/**
	 * Test exact-value delete protects a newer lock during recovery race.
	 *
	 * @return void
	 */
	public function test_exact_value_delete_prevents_recovery_from_deleting_newer_lock(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_LOCK ] = $this->lock_payload( 'old-token', self::NOW - 700, self::NOW - 100 );
		$store->before_delete_value                     = static function ( int $attachment_id, string $key ) use ( $store ): void {
			$store->meta[ $attachment_id ][ $key ] = array(
				'token'      => 'new-worker-token',
				'created_at' => self::NOW,
				'expires_at' => self::NOW + 600,
			);
		};

		$result = $this->manager( $store, array( 'unused' ), array( 123 ) )->recover_stale();

		self::assertFalse( $result->is_successful() );
		self::assertSame( 1, $result->failed() );
		self::assertSame( 'new-worker-token', $store->meta[123][ LifecyclePolicy::META_LOCK ]['token'] );
		self::assertContains( AttachmentLockRecoveryResult::CODE_RECOVERY_FAILED, $result->codes() );
		self::assertSame( array(), $result->recovered_attachment_ids() );
	}

	/**
	 * Test release requires matching token and missing release is idempotent.
	 *
	 * @return void
	 */
	public function test_release_requires_matching_token_and_handles_missing_lock(): void {
		$store   = new FakeAttachmentMetaStore();
		$manager = $this->manager( $store, array( 'worker-token' ) );
		$manager->acquire( 123 );

		$mismatch = $manager->release( 123, 'wrong-token' );

		self::assertFalse( $mismatch->is_successful() );
		self::assertTrue( $mismatch->has_code( AttachmentLockResult::CODE_TOKEN_MISMATCH ) );
		self::assertArrayHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );

		$released = $manager->release( 123, 'worker-token' );
		$missing  = $manager->release( 123, 'worker-token' );

		self::assertTrue( $released->is_successful() );
		self::assertTrue( $released->has_code( AttachmentLockResult::CODE_RELEASED ) );
		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );
		self::assertTrue( $missing->is_successful() );
		self::assertTrue( $missing->has_warnings() );
		self::assertTrue( $missing->has_code( AttachmentLockResult::CODE_RELEASE_MISSING ) );
	}

	/**
	 * Test run locked releases after success.
	 *
	 * @return void
	 */
	public function test_run_locked_releases_after_successful_callback(): void {
		$store    = new FakeAttachmentMetaStore();
		$manager  = $this->manager( $store, array( 'worker-token' ) );
		$callback = 0;

		$result = $manager->run_locked(
			123,
			static function ( AttachmentLock $lock ) use ( &$callback ): void {
				++$callback;
				self::assertSame( 'worker-token', $lock->token() );
			}
		);

		self::assertSame( 1, $callback );
		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( AttachmentLockResult::CODE_LOCKED_CALLBACK_COMPLETE ) );
		self::assertTrue( $result->has_code( AttachmentLockResult::CODE_RELEASED ) );
		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );
	}

	/**
	 * Test run locked releases after thrown callback.
	 *
	 * @return void
	 */
	public function test_run_locked_releases_after_thrown_callback(): void {
		$store   = new FakeAttachmentMetaStore();
		$manager = $this->manager( $store, array( 'worker-token' ) );

		try {
			$manager->run_locked(
				123,
				static function (): void {
					throw new \RuntimeException( 'Callback failed.' );
				}
			);

			self::fail( 'Expected callback exception.' );
		} catch ( \RuntimeException $exception ) {
			self::assertSame( 'Callback failed.', $exception->getMessage() );
		}

		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );
	}

	/**
	 * Test recovery is bounded.
	 *
	 * @return void
	 */
	public function test_recover_stale_is_bounded_to_one_hundred_locks(): void {
		$store = new FakeAttachmentMetaStore();
		$ids   = range( 1, 150 );

		foreach ( $ids as $attachment_id ) {
			$store->meta[ $attachment_id ][ LifecyclePolicy::META_LOCK ] = $this->lock_payload( 'token-' . $attachment_id, self::NOW - 700, self::NOW - 100 );
		}

		$scanner = new FakeAttachmentLockScanner( $ids );
		$result  = $this->manager( $store, array( 'unused' ), $ids, $scanner )->recover_stale( 250 );

		self::assertSame( 100, $scanner->last_limit );
		self::assertSame( 100, $result->scanned() );
		self::assertSame( 100, $result->stale_recovered() );
		self::assertSame( 0, $result->failed() );
		self::assertCount( 100, $result->recovered_attachment_ids() );
		self::assertSame( 1, $result->recovered_attachment_ids()[0] );
		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[100] );
		self::assertArrayHasKey( LifecyclePolicy::META_LOCK, $store->meta[101] );
	}

	/**
	 * Test diagnostics statuses for clear, active, stale, and invalid locks.
	 *
	 * @return void
	 */
	public function test_lock_diagnostics_reports_clear_active_stale_and_invalid_states(): void {
		self::assertSame( DiagnosticStatus::PASS, $this->diagnostic_status( array() ) );

		self::assertSame(
			DiagnosticStatus::INFO,
			$this->diagnostic_status(
				array(
					123 => $this->lock_payload( 'active-token', self::NOW, self::NOW + 600 ),
				)
			)
		);

		self::assertSame(
			DiagnosticStatus::WARNING,
			$this->diagnostic_status(
				array(
					123 => $this->lock_payload( 'stale-token', self::NOW - 700, self::NOW - 100 ),
				)
			)
		);

		$report  = $this->diagnostic_report(
			array(
				123 => array(
					'token'      => '',
					'created_at' => self::NOW,
					'expires_at' => self::NOW + 600,
				),
			)
		);
		$result  = $report->results()[0];
		$details = $result->details();

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 1, $details['invalid'] );
		self::assertArrayNotHasKey( 'token', $details );
	}

	/**
	 * Build manager.
	 *
	 * @param FakeAttachmentMetaStore        $store Meta store.
	 * @param string[]                       $tokens Tokens.
	 * @param int[]                          $scanner_ids Scanner IDs.
	 * @param FakeAttachmentLockScanner|null $scanner Scanner.
	 * @return AttachmentLockManager
	 */
	private function manager(
		FakeAttachmentMetaStore $store,
		array $tokens,
		array $scanner_ids = array(),
		?FakeAttachmentLockScanner $scanner = null
	): AttachmentLockManager {
		return new AttachmentLockManager(
			$store,
			new FixedAttachmentLockTokenGenerator( $tokens ),
			new FixedAttachmentClock( self::NOW ),
			$scanner instanceof FakeAttachmentLockScanner ? $scanner : new FakeAttachmentLockScanner( $scanner_ids )
		);
	}

	/**
	 * Build diagnostic report from raw locks.
	 *
	 * @param array<int,mixed> $locks Raw locks keyed by attachment ID.
	 * @return \HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticReport
	 */
	private function diagnostic_report( array $locks ) {
		$store = new FakeAttachmentMetaStore();

		foreach ( $locks as $attachment_id => $lock ) {
			$store->meta[ (int) $attachment_id ][ LifecyclePolicy::META_LOCK ] = $lock;
		}

		$diagnostics = new AttachmentLockDiagnostics(
			new FakeAttachmentLockScanner( array_keys( $locks ) ),
			$store,
			new FixedAttachmentClock( self::NOW ),
			new DiagnosticSanitizer()
		);

		return $diagnostics->run();
	}

	/**
	 * Get diagnostic status from raw locks.
	 *
	 * @param array<int,mixed> $locks Raw locks keyed by attachment ID.
	 * @return string
	 */
	private function diagnostic_status( array $locks ): string {
		return $this->diagnostic_report( $locks )->results()[0]->status();
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
