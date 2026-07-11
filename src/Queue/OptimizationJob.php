<?php
/**
 * Optimization queue job.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Immutable optimization job payload.
 */
final class OptimizationJob {

	public const FORMAT_WEBP = 'webp';
	public const FORMAT_AVIF = 'avif';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Target format.
	 *
	 * @var string
	 */
	private $format;

	/**
	 * Source cursor.
	 *
	 * @var int
	 */
	private $cursor;

	/**
	 * Force flag.
	 *
	 * @var bool
	 */
	private $force;

	/**
	 * Queueing reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Short fingerprint signature.
	 *
	 * @var string
	 */
	private $fingerprint;

	/**
	 * Create job.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $format Target format.
	 * @param int    $cursor Source cursor.
	 * @param bool   $force Force flag.
	 * @param string $reason Queueing reason.
	 * @param string $fingerprint Short fingerprint signature.
	 */
	public function __construct(
		int $attachment_id,
		string $format,
		int $cursor = 0,
		bool $force = false,
		string $reason = '',
		string $fingerprint = ''
	) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->format        = $this->normalize_format( $format );
		$this->cursor        = max( 0, $cursor );
		$this->force         = $force;
		$this->reason        = $this->normalize_reason( $reason );
		$this->fingerprint   = $this->normalize_fingerprint( $fingerprint );
	}

	/**
	 * Build job from serialized payload.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return self|null
	 */
	public static function from_array( array $payload ): ?self {
		if ( ! isset( $payload['attachment_id'], $payload['format'], $payload['fingerprint'] ) ) {
			return null;
		}

		if ( ! is_numeric( $payload['attachment_id'] ) || ! is_scalar( $payload['format'] ) || ! is_scalar( $payload['fingerprint'] ) ) {
			return null;
		}

		$job = new self(
			(int) $payload['attachment_id'],
			(string) $payload['format'],
			isset( $payload['cursor'] ) && is_numeric( $payload['cursor'] ) ? (int) $payload['cursor'] : 0,
			isset( $payload['force'] ) ? (bool) $payload['force'] : false,
			isset( $payload['reason'] ) && is_scalar( $payload['reason'] ) ? (string) $payload['reason'] : '',
			(string) $payload['fingerprint']
		);

		return $job->is_valid() ? $job : null;
	}

	/**
	 * Build job from Action Scheduler callback arguments.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @param mixed $format Target format.
	 * @param mixed $cursor Source cursor.
	 * @param mixed $force Force flag.
	 * @param mixed $reason Queueing reason.
	 * @param mixed $fingerprint Short fingerprint signature.
	 * @return self|null
	 */
	public static function from_callback_args( $attachment_id, $format, $cursor, $force, $reason, $fingerprint ): ?self {
		if ( ! is_numeric( $attachment_id ) || ! is_scalar( $format ) || ! is_scalar( $fingerprint ) ) {
			return null;
		}

		$job = new self(
			(int) $attachment_id,
			(string) $format,
			is_numeric( $cursor ) ? (int) $cursor : 0,
			(bool) $force,
			is_scalar( $reason ) ? (string) $reason : '',
			(string) $fingerprint
		);

		return $job->is_valid() ? $job : null;
	}

	/**
	 * Determine whether the job payload is valid.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return 0 < $this->attachment_id
			&& '' !== $this->format
			&& '' !== $this->fingerprint;
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
	 * Get target format.
	 *
	 * @return string
	 */
	public function format(): string {
		return $this->format;
	}

	/**
	 * Get source cursor.
	 *
	 * @return int
	 */
	public function cursor(): int {
		return $this->cursor;
	}

	/**
	 * Get force flag.
	 *
	 * @return bool
	 */
	public function force(): bool {
		return $this->force;
	}

	/**
	 * Get queueing reason.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Get short fingerprint signature.
	 *
	 * @return string
	 */
	public function fingerprint(): string {
		return $this->fingerprint;
	}

	/**
	 * Serialize the full payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'format'        => $this->format,
			'cursor'        => $this->cursor,
			'force'         => $this->force,
			'reason'        => $this->reason,
			'fingerprint'   => $this->fingerprint,
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
			'format'        => $this->format,
			'cursor'        => $this->cursor,
			'force'         => $this->force,
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
	 * Normalize format.
	 *
	 * @param string $format Format.
	 * @return string
	 */
	private function normalize_format( string $format ): string {
		$format = strtolower( trim( $format ) );

		return in_array( $format, array( self::FORMAT_WEBP, self::FORMAT_AVIF ), true ) ? $format : '';
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
