<?php
/**
 * Attachment fingerprint comparison result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Represents the result of comparing a queued or stored fingerprint to current state.
 */
final class AttachmentFingerprintComparison {

	public const STATUS_MATCH   = 'match';
	public const STATUS_STALE   = 'stale';
	public const STATUS_INVALID = 'invalid';

	/**
	 * Comparison status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Comparison code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-safe message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Current fingerprint.
	 *
	 * @var AttachmentFingerprint|null
	 */
	private $current;

	/**
	 * Reference fingerprint.
	 *
	 * @var AttachmentFingerprint|null
	 */
	private $reference;

	/**
	 * Public-safe details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create comparison.
	 *
	 * @param string                     $status Status.
	 * @param string                     $code Code.
	 * @param string                     $message Message.
	 * @param AttachmentFingerprint|null $current Current fingerprint.
	 * @param AttachmentFingerprint|null $reference Reference fingerprint.
	 * @param array<mixed>               $details Details.
	 */
	private function __construct(
		string $status,
		string $code,
		string $message,
		?AttachmentFingerprint $current = null,
		?AttachmentFingerprint $reference = null,
		array $details = array()
	) {
		$this->status    = $this->normalize_status( $status );
		$this->code      = AttachmentFingerprintCode::normalize( $code );
		$this->message   = '' === trim( $message ) ? 'Attachment fingerprint comparison.' : $this->redact_absolute_paths( trim( $message ) );
		$this->current   = $current;
		$this->reference = $reference;
		$this->details   = $this->sanitize_details( $details );
	}

	/**
	 * Build a matching comparison.
	 *
	 * @param AttachmentFingerprint      $current Current fingerprint.
	 * @param AttachmentFingerprint|null $reference Reference fingerprint.
	 * @return self
	 */
	public static function matched( AttachmentFingerprint $current, ?AttachmentFingerprint $reference = null ): self {
		return new self(
			self::STATUS_MATCH,
			AttachmentFingerprintCode::FINGERPRINT_MATCH,
			'The attachment fingerprint matches the current source state.',
			$current,
			$reference
		);
	}

	/**
	 * Build a stale comparison.
	 *
	 * @param string                     $code Code.
	 * @param string                     $message Message.
	 * @param AttachmentFingerprint      $current Current fingerprint.
	 * @param AttachmentFingerprint|null $reference Reference fingerprint.
	 * @param array<mixed>               $details Details.
	 * @return self
	 */
	public static function stale(
		string $code,
		string $message,
		AttachmentFingerprint $current,
		?AttachmentFingerprint $reference = null,
		array $details = array()
	): self {
		return new self(
			self::STATUS_STALE,
			$code,
			$message,
			$current,
			$reference,
			$details
		);
	}

	/**
	 * Build an invalid comparison.
	 *
	 * @param string                     $code Code.
	 * @param string                     $message Message.
	 * @param AttachmentFingerprint|null $current Current fingerprint.
	 * @param array<mixed>               $details Details.
	 * @return self
	 */
	public static function invalid(
		string $code,
		string $message,
		?AttachmentFingerprint $current = null,
		array $details = array()
	): self {
		return new self(
			self::STATUS_INVALID,
			$code,
			$message,
			$current,
			null,
			$details
		);
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get current fingerprint.
	 *
	 * @return AttachmentFingerprint|null
	 */
	public function current_fingerprint(): ?AttachmentFingerprint {
		return $this->current;
	}

	/**
	 * Get reference fingerprint.
	 *
	 * @return AttachmentFingerprint|null
	 */
	public function reference_fingerprint(): ?AttachmentFingerprint {
		return $this->reference;
	}

	/**
	 * Get details.
	 *
	 * @return array<mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Whether fingerprints match.
	 *
	 * @return bool
	 */
	public function is_match(): bool {
		return self::STATUS_MATCH === $this->status;
	}

	/**
	 * Whether current state is stale relative to reference.
	 *
	 * @return bool
	 */
	public function is_stale(): bool {
		return self::STATUS_STALE === $this->status;
	}

	/**
	 * Whether the comparison could not be trusted.
	 *
	 * @return bool
	 */
	public function is_invalid(): bool {
		return self::STATUS_INVALID === $this->status;
	}

	/**
	 * Serialize comparison.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'status'    => $this->status,
			'code'      => $this->code,
			'message'   => $this->message,
			'current'   => null === $this->current ? null : $this->current->to_array(),
			'reference' => null === $this->reference ? null : $this->reference->to_array(),
			'details'   => $this->details,
		);
	}

	/**
	 * Normalize status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private function normalize_status( string $status ): string {
		$status = strtolower( trim( $status ) );

		return in_array( $status, array( self::STATUS_MATCH, self::STATUS_STALE, self::STATUS_INVALID ), true )
			? $status
			: self::STATUS_INVALID;
	}

	/**
	 * Sanitize details.
	 *
	 * @param array<mixed> $details Details.
	 * @return array<mixed>
	 */
	private function sanitize_details( array $details ): array {
		$sanitized = array();

		foreach ( $details as $key => $value ) {
			$detail_key = is_int( $key ) ? $key : $this->sanitize_key( (string) $key );

			if ( is_string( $value ) ) {
				$sanitized[ $detail_key ] = $this->redact_absolute_paths( $value );
			} elseif ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[ $detail_key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize detail key.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function sanitize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );

		return '' === $key ? 'detail' : substr( $key, 0, 64 );
	}

	/**
	 * Redact absolute paths from a string.
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
