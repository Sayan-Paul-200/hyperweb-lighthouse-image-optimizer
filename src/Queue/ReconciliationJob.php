<?php
/**
 * Reconciliation queue job.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Immutable reconciliation job payload.
 */
final class ReconciliationJob {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Short fingerprint signature.
	 *
	 * @var string
	 */
	private $fingerprint;

	/**
	 * Queueing reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Create job.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $fingerprint Short fingerprint signature.
	 * @param string $reason Queueing reason.
	 */
	public function __construct( int $attachment_id, string $fingerprint, string $reason = '' ) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->fingerprint   = $this->normalize_fingerprint( $fingerprint );
		$this->reason        = $this->normalize_reason( $reason );
	}

	/**
	 * Build job from a serialized payload.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return self|null
	 */
	public static function from_array( array $payload ): ?self {
		if ( ! isset( $payload['attachment_id'], $payload['fingerprint'] ) ) {
			return null;
		}

		if ( ! is_numeric( $payload['attachment_id'] ) || ! is_scalar( $payload['fingerprint'] ) ) {
			return null;
		}

		$job = new self(
			(int) $payload['attachment_id'],
			(string) $payload['fingerprint'],
			isset( $payload['reason'] ) && is_scalar( $payload['reason'] ) ? (string) $payload['reason'] : ''
		);

		return $job->is_valid() ? $job : null;
	}

	/**
	 * Build job from Action Scheduler callback arguments.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @param mixed $fingerprint Short fingerprint signature.
	 * @param mixed $reason Queueing reason.
	 * @return self|null
	 */
	public static function from_callback_args( $attachment_id, $fingerprint, $reason ): ?self {
		if ( ! is_numeric( $attachment_id ) || ! is_scalar( $fingerprint ) ) {
			return null;
		}

		$job = new self(
			(int) $attachment_id,
			(string) $fingerprint,
			is_scalar( $reason ) ? (string) $reason : ''
		);

		return $job->is_valid() ? $job : null;
	}

	/**
	 * Determine whether the job payload is valid.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return 0 < $this->attachment_id && '' !== $this->fingerprint;
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get fingerprint.
	 *
	 * @return string
	 */
	public function fingerprint(): string {
		return $this->fingerprint;
	}

	/**
	 * Get reason.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Serialize the full payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'fingerprint'   => $this->fingerprint,
			'reason'        => $this->reason,
		);
	}

	/**
	 * Serialize the identity fields used for deduplication.
	 *
	 * @return array<string,mixed>
	 */
	public function identity_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'fingerprint'   => $this->fingerprint,
		);
	}

	/**
	 * Determine whether two jobs are equivalent work items.
	 *
	 * @param self $other Other job.
	 * @return bool
	 */
	public function equivalent_to( self $other ): bool {
		return $this->identity_array() === $other->identity_array();
	}

	/**
	 * Normalize queueing reason into a compact machine-readable string.
	 *
	 * @param string $reason Queueing reason.
	 * @return string
	 */
	private function normalize_reason( string $reason ): string {
		$reason = strtolower( trim( $reason ) );
		$reason = (string) preg_replace( '/[^a-z0-9_]/', '_', $reason );
		$reason = trim( $reason, '_' );

		return substr( $reason, 0, 64 );
	}

	/**
	 * Normalize fingerprint signature.
	 *
	 * @param string $fingerprint Fingerprint.
	 * @return string
	 */
	private function normalize_fingerprint( string $fingerprint ): string {
		$fingerprint = strtolower( trim( $fingerprint ) );

		return 1 === preg_match( '/^[a-f0-9]{20}$/', $fingerprint ) ? $fingerprint : '';
	}
}
