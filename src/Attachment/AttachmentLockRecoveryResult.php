<?php
/**
 * Attachment lock recovery result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries bounded stale-lock recovery counts.
 */
final class AttachmentLockRecoveryResult {

	public const CODE_NO_LOCKS          = 'no_locks_found';
	public const CODE_ACTIVE_LOCKS      = 'active_locks_found';
	public const CODE_STALE_RECOVERED   = 'stale_locks_recovered';
	public const CODE_INVALID_RECOVERED = 'invalid_locks_recovered';
	public const CODE_RECOVERY_FAILED   = 'lock_recovery_failed';

	/**
	 * Scanned count.
	 *
	 * @var int
	 */
	private $scanned;

	/**
	 * Active count.
	 *
	 * @var int
	 */
	private $active;

	/**
	 * Stale recovered count.
	 *
	 * @var int
	 */
	private $stale_recovered;

	/**
	 * Invalid recovered count.
	 *
	 * @var int
	 */
	private $invalid_recovered;

	/**
	 * Failure count.
	 *
	 * @var int
	 */
	private $failed;

	/**
	 * Sampled attachment IDs.
	 *
	 * @var int[]
	 */
	private $sample_attachment_ids;

	/**
	 * Recovered attachment IDs.
	 *
	 * @var int[]
	 */
	private $recovered_attachment_ids;

	/**
	 * Create result.
	 *
	 * @param int   $scanned Scanned count.
	 * @param int   $active Active count.
	 * @param int   $stale_recovered Stale recovered count.
	 * @param int   $invalid_recovered Invalid recovered count.
	 * @param int   $failed Failure count.
	 * @param int[] $sample_attachment_ids Sampled attachment IDs.
	 * @param int[] $recovered_attachment_ids Recovered attachment IDs.
	 */
	public function __construct(
		int $scanned,
		int $active,
		int $stale_recovered,
		int $invalid_recovered,
		int $failed,
		array $sample_attachment_ids = array(),
		array $recovered_attachment_ids = array()
	) {
		$this->scanned               = max( 0, $scanned );
		$this->active                = max( 0, $active );
		$this->stale_recovered       = max( 0, $stale_recovered );
		$this->invalid_recovered     = max( 0, $invalid_recovered );
		$this->failed                = max( 0, $failed );
		$this->sample_attachment_ids = $this->normalize_ids( $sample_attachment_ids, 10 );
		$this->recovered_attachment_ids = $this->normalize_ids( $recovered_attachment_ids, 100 );
	}

	/**
	 * Whether recovery completed without failed deletes.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return 0 === $this->failed;
	}

	/**
	 * Whether warnings exist.
	 *
	 * @return bool
	 */
	public function has_warnings(): bool {
		return 0 < $this->failed;
	}

	/**
	 * Get scanned count.
	 *
	 * @return int
	 */
	public function scanned(): int {
		return $this->scanned;
	}

	/**
	 * Get active count.
	 *
	 * @return int
	 */
	public function active(): int {
		return $this->active;
	}

	/**
	 * Get stale recovered count.
	 *
	 * @return int
	 */
	public function stale_recovered(): int {
		return $this->stale_recovered;
	}

	/**
	 * Get invalid recovered count.
	 *
	 * @return int
	 */
	public function invalid_recovered(): int {
		return $this->invalid_recovered;
	}

	/**
	 * Get failure count.
	 *
	 * @return int
	 */
	public function failed(): int {
		return $this->failed;
	}

	/**
	 * Get recovered attachment IDs.
	 *
	 * @return int[]
	 */
	public function recovered_attachment_ids(): array {
		return $this->recovered_attachment_ids;
	}

	/**
	 * Get sampled attachment IDs.
	 *
	 * @return int[]
	 */
	public function sample_attachment_ids(): array {
		return $this->sample_attachment_ids;
	}

	/**
	 * Get result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		$codes = array();

		if ( 0 === $this->scanned ) {
			$codes[] = self::CODE_NO_LOCKS;
		}

		if ( 0 < $this->active ) {
			$codes[] = self::CODE_ACTIVE_LOCKS;
		}

		if ( 0 < $this->stale_recovered ) {
			$codes[] = self::CODE_STALE_RECOVERED;
		}

		if ( 0 < $this->invalid_recovered ) {
			$codes[] = self::CODE_INVALID_RECOVERED;
		}

		if ( 0 < $this->failed ) {
			$codes[] = self::CODE_RECOVERY_FAILED;
		}

		return $codes;
	}

	/**
	 * Serialize safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful'            => $this->is_successful(),
			'warnings'              => $this->has_warnings(),
			'codes'                 => $this->codes(),
			'scanned'               => $this->scanned,
			'active'                => $this->active,
			'stale_recovered'       => $this->stale_recovered,
			'invalid_recovered'     => $this->invalid_recovered,
			'failed'                => $this->failed,
			'sample_attachment_ids' => $this->sample_attachment_ids,
			'recovered_attachment_ids' => $this->recovered_attachment_ids,
		);
	}

	/**
	 * Normalize attachment IDs.
	 *
	 * @param int[] $ids IDs.
	 * @param int   $limit Maximum IDs to keep.
	 * @return int[]
	 */
	private function normalize_ids( array $ids, int $limit ): array {
		$normalized = array();

		foreach ( $ids as $id ) {
			if ( ! is_numeric( $id ) ) {
				continue;
			}

			$id = (int) $id;

			if ( 0 < $id ) {
				$normalized[] = $id;
			}
		}

		return array_slice( array_values( array_unique( $normalized ) ), 0, max( 1, $limit ) );
	}
}
