<?php
/**
 * Action Scheduler readiness status.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Captures Action Scheduler loaded and initialized state.
 */
final class ActionSchedulerStatus {

	public const STATUS_READY           = 'ready';
	public const STATUS_NOT_INITIALIZED = 'not_initialized';
	public const STATUS_MISSING         = 'missing';
	public const STATUS_UNKNOWN         = 'unknown';

	/**
	 * Whether Action Scheduler is loaded.
	 *
	 * @var bool
	 */
	private $loaded;

	/**
	 * Whether Action Scheduler is initialized.
	 *
	 * @var bool|null
	 */
	private $initialized;

	/**
	 * Status value.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Create status.
	 *
	 * @param bool      $loaded Whether loaded.
	 * @param bool|null $initialized Whether initialized.
	 * @param string    $status Status value.
	 */
	public function __construct( bool $loaded, ?bool $initialized, string $status ) {
		$this->loaded      = $loaded;
		$this->initialized = $initialized;
		$this->status      = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_UNKNOWN;
	}

	/**
	 * Build status from loaded and initialized state.
	 *
	 * @param bool      $loaded Whether loaded.
	 * @param bool|null $initialized Whether initialized.
	 * @return self
	 */
	public static function from_state( bool $loaded, ?bool $initialized ): self {
		if ( ! $loaded ) {
			return new self( false, null, self::STATUS_MISSING );
		}

		if ( true === $initialized ) {
			return new self( true, true, self::STATUS_READY );
		}

		if ( false === $initialized ) {
			return new self( true, false, self::STATUS_NOT_INITIALIZED );
		}

		return new self( true, null, self::STATUS_UNKNOWN );
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_READY,
			self::STATUS_NOT_INITIALIZED,
			self::STATUS_MISSING,
			self::STATUS_UNKNOWN,
		);
	}

	/**
	 * Whether Action Scheduler is loaded.
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {
		return $this->loaded;
	}

	/**
	 * Whether Action Scheduler is initialized.
	 *
	 * @return bool|null
	 */
	public function is_initialized(): ?bool {
		return $this->initialized;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}
}
