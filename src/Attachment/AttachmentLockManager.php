<?php
/**
 * Attachment lock manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Acquires, releases, and recovers plugin-owned attachment locks.
 */
final class AttachmentLockManager {

	public const DEFAULT_TTL_SECONDS = 600;
	public const RECOVERY_LIMIT      = 100;

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Token generator.
	 *
	 * @var AttachmentLockTokenGeneratorInterface
	 */
	private $tokens;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Lock scanner.
	 *
	 * @var AttachmentLockScannerInterface|null
	 */
	private $scanner;

	/**
	 * Build WordPress-backed lock manager.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentMetaStore(),
			new RandomAttachmentLockTokenGenerator(),
			new SystemAttachmentClock(),
			new WordPressAttachmentLockScanner()
		);
	}

	/**
	 * Create manager.
	 *
	 * @param AttachmentMetaStoreInterface          $meta Meta store.
	 * @param AttachmentLockTokenGeneratorInterface $tokens Token generator.
	 * @param AttachmentClockInterface              $clock Clock.
	 * @param AttachmentLockScannerInterface|null   $scanner Lock scanner.
	 */
	public function __construct(
		AttachmentMetaStoreInterface $meta,
		AttachmentLockTokenGeneratorInterface $tokens,
		AttachmentClockInterface $clock,
		?AttachmentLockScannerInterface $scanner = null
	) {
		$this->meta    = $meta;
		$this->tokens  = $tokens;
		$this->clock   = $clock;
		$this->scanner = $scanner;
	}

	/**
	 * Acquire a unique attachment lock.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $ttl_seconds Time to live in seconds.
	 * @return AttachmentLockResult
	 */
	public function acquire( int $attachment_id, int $ttl_seconds = self::DEFAULT_TTL_SECONDS ): AttachmentLockResult {
		$attachment_id = max( 0, $attachment_id );
		$lock          = $this->new_lock( $ttl_seconds );

		if ( $this->meta->add_unique( $attachment_id, LifecyclePolicy::META_LOCK, $lock->to_storage_array() ) ) {
			return new AttachmentLockResult( true, false, $lock, array( AttachmentLockResult::CODE_ACQUIRED ) );
		}

		$raw_existing  = $this->meta->get( $attachment_id, LifecyclePolicy::META_LOCK, null );
		$existing_lock = AttachmentLock::from_stored( $raw_existing );

		if ( null === $raw_existing ) {
			return new AttachmentLockResult(
				false,
				true,
				null,
				array( AttachmentLockResult::CODE_WRITE_FAILED ),
				array( 'Attachment lock could not be written.' )
			);
		}

		if ( $existing_lock instanceof AttachmentLock && ! $existing_lock->is_expired( $this->now() ) ) {
			return new AttachmentLockResult(
				false,
				false,
				$existing_lock,
				array( AttachmentLockResult::CODE_UNAVAILABLE ),
				array( 'Attachment is already locked.' )
			);
		}

		$recovery_code = $existing_lock instanceof AttachmentLock
			? AttachmentLockResult::CODE_STALE_RECOVERED
			: AttachmentLockResult::CODE_INVALID_RECOVERED;

		if ( ! $this->meta->delete_value( $attachment_id, LifecyclePolicy::META_LOCK, $raw_existing ) ) {
			return new AttachmentLockResult(
				false,
				true,
				$existing_lock,
				array( AttachmentLockResult::CODE_RECOVERY_FAILED ),
				array( 'Existing stale or invalid lock could not be recovered.' )
			);
		}

		$retry_lock = $this->new_lock( $ttl_seconds );

		if ( $this->meta->add_unique( $attachment_id, LifecyclePolicy::META_LOCK, $retry_lock->to_storage_array() ) ) {
			return new AttachmentLockResult(
				true,
				true,
				$retry_lock,
				array( $recovery_code, AttachmentLockResult::CODE_ACQUIRED )
			);
		}

		$current = AttachmentLock::from_stored( $this->meta->get( $attachment_id, LifecyclePolicy::META_LOCK, null ) );

		return new AttachmentLockResult(
			false,
			true,
			$current,
			array( $recovery_code, AttachmentLockResult::CODE_WRITE_FAILED ),
			array( 'Attachment lock could not be written after recovery.' )
		);
	}

	/**
	 * Release an attachment lock only when the token matches.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $token Lock token.
	 * @return AttachmentLockResult
	 */
	public function release( int $attachment_id, string $token ): AttachmentLockResult {
		$attachment_id = max( 0, $attachment_id );
		$raw_existing  = $this->meta->get( $attachment_id, LifecyclePolicy::META_LOCK, null );

		if ( null === $raw_existing ) {
			return new AttachmentLockResult(
				true,
				true,
				null,
				array( AttachmentLockResult::CODE_RELEASE_MISSING ),
				array( 'Attachment lock was already missing.' )
			);
		}

		$existing_lock = AttachmentLock::from_stored( $raw_existing );

		if ( ! $existing_lock instanceof AttachmentLock || ! $existing_lock->token_matches( $token ) ) {
			return new AttachmentLockResult(
				false,
				true,
				$existing_lock,
				array( AttachmentLockResult::CODE_TOKEN_MISMATCH ),
				array( 'Attachment lock token did not match.' )
			);
		}

		if ( ! $this->meta->delete_value( $attachment_id, LifecyclePolicy::META_LOCK, $raw_existing ) ) {
			return new AttachmentLockResult(
				false,
				true,
				$existing_lock,
				array( AttachmentLockResult::CODE_RELEASE_FAILED ),
				array( 'Attachment lock could not be released.' )
			);
		}

		return new AttachmentLockResult( true, false, null, array( AttachmentLockResult::CODE_RELEASED ) );
	}

	/**
	 * Run a callback while holding a lock.
	 *
	 * @param int      $attachment_id Attachment ID.
	 * @param callable $callback Callback receiving AttachmentLock.
	 * @param int      $ttl_seconds Time to live in seconds.
	 * @return AttachmentLockResult
	 */
	public function run_locked(
		int $attachment_id,
		callable $callback,
		int $ttl_seconds = self::DEFAULT_TTL_SECONDS
	): AttachmentLockResult {
		$acquired = $this->acquire( $attachment_id, $ttl_seconds );
		$lock     = $acquired->lock();

		if ( ! $acquired->is_successful() || ! $lock instanceof AttachmentLock ) {
			return $acquired;
		}

		try {
			call_user_func( $callback, $lock );
		} finally {
			$released = $this->release( $attachment_id, $lock->token() );
		}

		return new AttachmentLockResult(
			$released->is_successful(),
			$acquired->has_warnings() || $released->has_warnings(),
			null,
			array_merge(
				$acquired->codes(),
				array( AttachmentLockResult::CODE_LOCKED_CALLBACK_COMPLETE ),
				$released->codes()
			),
			array_merge( $acquired->messages(), $released->messages() )
		);
	}

	/**
	 * Recover stale and malformed locks in a bounded batch.
	 *
	 * @param int $limit Maximum locks to scan.
	 * @return AttachmentLockRecoveryResult
	 */
	public function recover_stale( int $limit = self::RECOVERY_LIMIT ): AttachmentLockRecoveryResult {
		if ( ! $this->scanner instanceof AttachmentLockScannerInterface ) {
			return new AttachmentLockRecoveryResult( 0, 0, 0, 0, 0 );
		}

		$limit             = max( 1, min( self::RECOVERY_LIMIT, $limit ) );
		$attachment_ids    = $this->scanner->locked_attachment_ids( $limit );
		$active            = 0;
		$stale_recovered   = 0;
		$invalid_recovered = 0;
		$failed            = 0;
		$samples           = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			$raw_existing  = $this->meta->get( $attachment_id, LifecyclePolicy::META_LOCK, null );

			if ( null === $raw_existing ) {
				continue;
			}

			$samples[]      = $attachment_id;
			$existing_lock  = AttachmentLock::from_stored( $raw_existing );
			$should_recover = null === $existing_lock || $existing_lock->is_expired( $this->now() );

			if ( ! $should_recover ) {
				++$active;
				continue;
			}

			if ( $this->meta->delete_value( $attachment_id, LifecyclePolicy::META_LOCK, $raw_existing ) ) {
				if ( $existing_lock instanceof AttachmentLock ) {
					++$stale_recovered;
				} else {
					++$invalid_recovered;
				}
				continue;
			}

			++$failed;
		}

		return new AttachmentLockRecoveryResult(
			count( $attachment_ids ),
			$active,
			$stale_recovered,
			$invalid_recovered,
			$failed,
			$samples
		);
	}

	/**
	 * Build a new lock.
	 *
	 * @param int $ttl_seconds Time to live in seconds.
	 * @return AttachmentLock
	 */
	private function new_lock( int $ttl_seconds ): AttachmentLock {
		$ttl_seconds = 0 < $ttl_seconds ? $ttl_seconds : self::DEFAULT_TTL_SECONDS;
		$now         = $this->now();

		return new AttachmentLock( $this->tokens->generate(), $now, $now + $ttl_seconds );
	}

	/**
	 * Current timestamp.
	 *
	 * @return int
	 */
	private function now(): int {
		return $this->clock->now();
	}
}
