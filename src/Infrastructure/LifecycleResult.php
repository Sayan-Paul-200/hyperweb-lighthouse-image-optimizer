<?php
/**
 * Lifecycle result value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Captures lifecycle operation outcomes with stable codes.
 */
final class LifecycleResult {

	public const SEVERITY_SUCCESS = 'success';
	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_FAILURE = 'failure';

	public const CODE_DEACTIVATION_COMPLETE         = 'deactivation_complete';
	public const CODE_MAINTENANCE_UNSCHEDULED       = 'maintenance_unscheduled';
	public const CODE_MAINTENANCE_UNAVAILABLE       = 'maintenance_unavailable';
	public const CODE_UNINSTALL_COMPLETE            = 'uninstall_complete';
	public const CODE_UNINSTALL_DATA_PRESERVED      = 'uninstall_data_preserved';
	public const CODE_UNINSTALL_DATA_DELETED        = 'uninstall_data_deleted';
	public const CODE_DERIVATIVES_PRESERVED         = 'derivatives_preserved';
	public const CODE_DERIVATIVES_DELETED           = 'derivatives_deleted';
	public const CODE_DERIVATIVE_REJECTED           = 'derivative_rejected';
	public const CODE_DERIVATIVE_MISSING            = 'derivative_missing';
	public const CODE_ORIGINAL_PRESERVED            = 'original_preserved';
	public const CODE_INVALID_SETTINGS_PRESERVED    = 'invalid_settings_preserved';
	public const CODE_NETWORK_BATCH_PROCESSED       = 'network_batch_processed';
	public const CODE_NETWORK_UNINSTALL_COMPLETED   = 'network_uninstall_completed';
	public const CODE_NETWORK_SITE_FAILED           = 'network_site_failed';
	public const CODE_UPLOADS_DIRECTORY_UNAVAILABLE = 'uploads_directory_unavailable';

	/**
	 * Result severity.
	 *
	 * @var string
	 */
	private $severity;

	/**
	 * Stable result codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Human-readable messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Create a result.
	 *
	 * @param string   $severity Result severity.
	 * @param string[] $codes Stable result codes.
	 * @param string[] $messages Human-readable messages.
	 */
	private function __construct( string $severity, array $codes, array $messages = array() ) {
		$this->severity = $severity;
		$this->codes    = array_values( array_unique( $codes ) );
		$this->messages = array_values( $messages );
	}

	/**
	 * Build a successful result.
	 *
	 * @param string[] $codes Stable result codes.
	 * @param string[] $messages Human-readable messages.
	 * @return self
	 */
	public static function success( array $codes, array $messages = array() ): self {
		return new self( self::SEVERITY_SUCCESS, $codes, $messages );
	}

	/**
	 * Build a warning result.
	 *
	 * @param string[] $codes Stable result codes.
	 * @param string[] $messages Human-readable messages.
	 * @return self
	 */
	public static function warning( array $codes, array $messages = array() ): self {
		return new self( self::SEVERITY_WARNING, $codes, $messages );
	}

	/**
	 * Build a failure result.
	 *
	 * @param string[] $codes Stable result codes.
	 * @param string[] $messages Human-readable messages.
	 * @return self
	 */
	public static function failure( array $codes, array $messages = array() ): self {
		return new self( self::SEVERITY_FAILURE, $codes, $messages );
	}

	/**
	 * Combine multiple result objects.
	 *
	 * @param self ...$results Results to combine.
	 * @return self
	 */
	public static function combine( self ...$results ): self {
		$severity = self::SEVERITY_SUCCESS;
		$codes    = array();
		$messages = array();

		foreach ( $results as $result ) {
			if ( self::SEVERITY_FAILURE === $result->severity() ) {
				$severity = self::SEVERITY_FAILURE;
			} elseif ( self::SEVERITY_WARNING === $result->severity() && self::SEVERITY_FAILURE !== $severity ) {
				$severity = self::SEVERITY_WARNING;
			}

			$codes    = array_merge( $codes, $result->codes() );
			$messages = array_merge( $messages, $result->messages() );
		}

		return new self( $severity, $codes, $messages );
	}

	/**
	 * Determine whether the lifecycle operation can continue.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return self::SEVERITY_FAILURE !== $this->severity;
	}

	/**
	 * Determine whether warnings were reported.
	 *
	 * @return bool
	 */
	public function has_warnings(): bool {
		return self::SEVERITY_WARNING === $this->severity;
	}

	/**
	 * Get the severity.
	 *
	 * @return string
	 */
	public function severity(): string {
		return $this->severity;
	}

	/**
	 * Get stable result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get human-readable messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Check whether a code is present.
	 *
	 * @param string $code Stable result code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $code, $this->codes, true );
	}
}
