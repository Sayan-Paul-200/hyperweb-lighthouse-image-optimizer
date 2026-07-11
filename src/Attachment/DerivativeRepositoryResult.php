<?php
/**
 * Derivative repository result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries derivative repository operation outcomes.
 */
final class DerivativeRepositoryResult {

	public const CODE_EMPTY                    = 'manifest_empty';
	public const CODE_LOADED                   = 'manifest_loaded';
	public const CODE_SAVED                    = 'manifest_saved';
	public const CODE_STATUS_SAVED             = 'status_saved';
	public const CODE_DELETED                  = 'metadata_deleted';
	public const CODE_RECONCILIATION_STARTED   = 'reconciliation_started';
	public const CODE_INVALID_METADATA_IGNORED = 'invalid_metadata_ignored';
	public const CODE_INVALID_STATUS_REPAIRED  = 'invalid_status_repaired';
	public const CODE_FINGERPRINT_MISMATCH     = 'fingerprint_mismatch';
	public const CODE_WRITE_FAILED             = 'metadata_write_failed';
	public const CODE_DELETE_FAILED            = 'metadata_delete_failed';
	public const CODE_NO_READY_RESULTS         = 'no_ready_results';

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
	 * Manifest.
	 *
	 * @var DerivativeManifest
	 */
	private $manifest;

	/**
	 * Status.
	 *
	 * @var AttachmentStatus
	 */
	private $status;

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
	 * @param bool               $successful Whether successful.
	 * @param bool               $warnings Whether warnings exist.
	 * @param DerivativeManifest $manifest Manifest.
	 * @param AttachmentStatus   $status Status.
	 * @param string[]           $codes Codes.
	 * @param string[]           $messages Messages.
	 */
	public function __construct(
		bool $successful,
		bool $warnings,
		DerivativeManifest $manifest,
		AttachmentStatus $status,
		array $codes = array(),
		array $messages = array()
	) {
		$this->successful = $successful;
		$this->warnings   = $warnings;
		$this->manifest   = $manifest;
		$this->status     = $status;
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
	 * Whether result has warnings.
	 *
	 * @return bool
	 */
	public function has_warnings(): bool {
		return $this->warnings || $this->has_code( self::CODE_INVALID_METADATA_IGNORED ) || $this->has_code( self::CODE_INVALID_STATUS_REPAIRED );
	}

	/**
	 * Get manifest.
	 *
	 * @return DerivativeManifest
	 */
	public function manifest(): DerivativeManifest {
		return $this->manifest;
	}

	/**
	 * Get status.
	 *
	 * @return AttachmentStatus
	 */
	public function status(): AttachmentStatus {
		return $this->status;
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
	 * Serialize result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful' => $this->successful,
			'warnings'   => $this->has_warnings(),
			'codes'      => $this->codes,
			'messages'   => $this->messages,
			'manifest'   => $this->manifest->to_array(),
			'status'     => $this->status->to_array(),
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
				$sanitized[] = $this->redact_absolute_paths( $message );
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Redact absolute paths from messages.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function redact_absolute_paths( string $value ): string {
		$value = (string) preg_replace(
			'/(^|[\s({\[=:])(?:[A-Za-z]:[\\\\\/][^\s<>"\')\]}]+)/',
			'$1[redacted_path]',
			$value
		);

		return (string) preg_replace(
			'/(^|[\s({\[=:])(?:\/[^\s<>"\')\]}]+(?:\/[^\s<>"\')\]}]+)+)/',
			'$1[redacted_path]',
			$value
		);
	}
}
