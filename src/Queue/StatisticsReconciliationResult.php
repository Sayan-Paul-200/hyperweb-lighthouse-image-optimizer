<?php
/**
 * Statistics reconciliation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Carries statistics cache reconciliation outcomes.
 */
final class StatisticsReconciliationResult {

	public const CODE_RECONCILED             = 'statistics_reconciled';
	public const CODE_WRITE_FAILED           = 'statistics_cache_write_failed';
	public const CODE_SCAN_FAILED            = 'statistics_cache_scan_failed';
	public const CODE_METADATA_IGNORED       = 'statistics_metadata_ignored';

	/**
	 * Whether reconciliation succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Whether reconciliation produced warnings.
	 *
	 * @var bool
	 */
	private $warnings;

	/**
	 * Result codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Result messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Reconciled cache payload.
	 *
	 * @var StatisticsCache
	 */
	private $cache;

	/**
	 * Create result.
	 *
	 * @param bool            $successful Whether successful.
	 * @param bool            $warnings Whether warnings exist.
	 * @param string[]        $codes Result codes.
	 * @param string[]        $messages Result messages.
	 * @param StatisticsCache $cache Reconciled cache payload.
	 */
	public function __construct( bool $successful, bool $warnings, array $codes, array $messages, StatisticsCache $cache ) {
		$this->successful = $successful;
		$this->warnings   = $warnings;
		$this->codes      = $this->normalize_codes( $codes );
		$this->messages   = $this->normalize_messages( $messages );
		$this->cache      = $cache;
	}

	/**
	 * Build a successful result.
	 *
	 * @param StatisticsCache $cache Cache.
	 * @param string[]        $codes Optional extra codes.
	 * @param string[]        $messages Optional messages.
	 * @param bool            $warnings Whether warnings exist.
	 * @return self
	 */
	public static function success( StatisticsCache $cache, array $codes = array(), array $messages = array(), bool $warnings = false ): self {
		array_unshift( $codes, self::CODE_RECONCILED );

		return new self( true, $warnings, $codes, $messages, $cache );
	}

	/**
	 * Build a failed result.
	 *
	 * @param string[]        $codes Codes.
	 * @param string[]        $messages Messages.
	 * @param StatisticsCache $cache Cache.
	 * @return self
	 */
	public static function failure( array $codes, array $messages, StatisticsCache $cache ): self {
		return new self( false, true, $codes, $messages, $cache );
	}

	/**
	 * Whether reconciliation succeeded.
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
	 * Get result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get result messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Get reconciled cache payload.
	 *
	 * @return StatisticsCache
	 */
	public function cache(): StatisticsCache {
		return $this->cache;
	}

	/**
	 * Whether a code exists.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $code, $this->codes, true );
	}

	/**
	 * Normalize result codes.
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
	 * Normalize messages.
	 *
	 * @param string[] $messages Messages.
	 * @return string[]
	 */
	private function normalize_messages( array $messages ): array {
		$normalized = array();

		foreach ( $messages as $message ) {
			if ( ! is_scalar( $message ) ) {
				continue;
			}

			$message = trim( (string) $message );

			if ( '' !== $message ) {
				$normalized[] = $message;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}
