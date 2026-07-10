<?php
/**
 * Attachment lock operation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries the result of acquiring or releasing an attachment lock.
 */
final class AttachmentLockResult {

	public const CODE_ACQUIRED                 = 'lock_acquired';
	public const CODE_RELEASED                 = 'lock_released';
	public const CODE_RELEASE_MISSING          = 'lock_release_missing';
	public const CODE_UNAVAILABLE              = 'lock_unavailable';
	public const CODE_STALE_RECOVERED          = 'stale_lock_recovered';
	public const CODE_INVALID_RECOVERED        = 'invalid_lock_recovered';
	public const CODE_RECOVERY_FAILED          = 'lock_recovery_failed';
	public const CODE_WRITE_FAILED             = 'lock_write_failed';
	public const CODE_RELEASE_FAILED           = 'lock_release_failed';
	public const CODE_TOKEN_MISMATCH           = 'lock_token_mismatch';
	public const CODE_LOCKED_CALLBACK_COMPLETE = 'locked_callback_completed';

	/**
	 * Whether operation succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Whether warnings exist.
	 *
	 * @var bool
	 */
	private $warnings;

	/**
	 * Lock, when available.
	 *
	 * @var AttachmentLock|null
	 */
	private $lock;

	/**
	 * Codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Create result.
	 *
	 * @param bool                $successful Whether successful.
	 * @param bool                $warnings Whether warnings exist.
	 * @param AttachmentLock|null $lock Lock.
	 * @param string[]            $codes Codes.
	 * @param string[]            $messages Messages.
	 */
	public function __construct(
		bool $successful,
		bool $warnings,
		?AttachmentLock $lock = null,
		array $codes = array(),
		array $messages = array()
	) {
		$this->successful = $successful;
		$this->warnings   = $warnings;
		$this->lock       = $lock;
		$this->codes      = $this->normalize_codes( $codes );
		$this->messages   = $this->sanitize_messages( $messages );
	}

	/**
	 * Whether operation succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Whether warnings exist.
	 *
	 * @return bool
	 */
	public function has_warnings(): bool {
		return $this->warnings;
	}

	/**
	 * Get lock.
	 *
	 * @return AttachmentLock|null
	 */
	public function lock(): ?AttachmentLock {
		return $this->lock;
	}

	/**
	 * Get codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Whether result has a code.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $code, $this->codes, true );
	}

	/**
	 * Serialize safely without exposing tokens.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful' => $this->successful,
			'warnings'   => $this->warnings,
			'codes'      => $this->codes,
			'messages'   => $this->messages,
			'lock'       => $this->lock instanceof AttachmentLock ? $this->lock->to_array() : null,
		);
	}

	/**
	 * Normalize codes.
	 *
	 * @param string[] $codes Codes.
	 * @return string[]
	 */
	private function normalize_codes( array $codes ): array {
		$normalized = array();

		foreach ( $codes as $code ) {
			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = strtolower( trim( (string) $code ) );
			$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
			$code = trim( $code, '_' );

			if ( '' !== $code ) {
				$normalized[] = substr( $code, 0, 64 );
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Sanitize messages.
	 *
	 * @param string[] $messages Messages.
	 * @return string[]
	 */
	private function sanitize_messages( array $messages ): array {
		$sanitized = array();

		foreach ( $messages as $message ) {
			if ( ! is_scalar( $message ) ) {
				continue;
			}

			$message = trim( (string) $message );

			if ( '' !== $message ) {
				$sanitized[] = substr( $message, 0, 500 );
			}
		}

		return array_values( array_unique( $sanitized ) );
	}
}
