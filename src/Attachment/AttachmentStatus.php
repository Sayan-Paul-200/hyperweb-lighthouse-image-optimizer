<?php
/**
 * Attachment status summary.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries the small `_hwlio_status` summary for list-table and workflow use.
 */
final class AttachmentStatus {

	public const STATE_UNPROCESSED = 'unprocessed';
	public const STATE_QUEUED      = 'queued';
	public const STATE_PROCESSING  = 'processing';
	public const STATE_PARTIAL     = 'partial';
	public const STATE_OPTIMIZED   = 'optimized';
	public const STATE_FAILED      = 'failed';
	public const STATE_STALE       = 'stale';
	public const STATE_EXCLUDED    = 'excluded';
	public const STATE_SKIPPED     = 'skipped';

	public const FORMAT_WEBP = 'webp';
	public const FORMAT_AVIF = 'avif';

	/**
	 * State.
	 *
	 * @var string
	 */
	private $state;

	/**
	 * Ready formats.
	 *
	 * @var string[]
	 */
	private $formats;

	/**
	 * Updated timestamp.
	 *
	 * @var int
	 */
	private $updated_at;

	/**
	 * Error code.
	 *
	 * @var string|null
	 */
	private $error_code;

	/**
	 * Whether attachment is excluded.
	 *
	 * @var bool
	 */
	private $excluded;

	/**
	 * Create status.
	 *
	 * @param string      $state State.
	 * @param string[]    $formats Ready formats.
	 * @param int         $updated_at Updated timestamp.
	 * @param string|null $error_code Error code.
	 * @param bool        $excluded Whether excluded.
	 */
	public function __construct(
		string $state = self::STATE_UNPROCESSED,
		array $formats = array(),
		int $updated_at = 0,
		?string $error_code = null,
		bool $excluded = false
	) {
		$this->state      = self::normalize_state( $state );
		$this->formats    = self::normalize_formats( $formats );
		$this->updated_at = max( 0, $updated_at );
		$this->error_code = self::normalize_error_code( $error_code );
		$this->excluded   = $excluded;
	}

	/**
	 * Build default status.
	 *
	 * @return self
	 */
	public static function unprocessed(): self {
		return new self();
	}

	/**
	 * Build status from stored metadata.
	 *
	 * @param mixed $raw Raw status.
	 * @return self
	 */
	public static function from_stored( $raw ): self {
		if ( ! is_array( $raw ) ) {
			return self::unprocessed();
		}

		return new self(
			self::array_string( $raw, 'state', self::STATE_UNPROCESSED ),
			self::array_list( $raw, 'formats' ),
			self::array_int( $raw, 'updated_at' ),
			self::array_nullable_string( $raw, 'error_code' ),
			self::array_bool( $raw, 'excluded' )
		);
	}

	/**
	 * Get valid states.
	 *
	 * @return string[]
	 */
	public static function states(): array {
		return array(
			self::STATE_UNPROCESSED,
			self::STATE_QUEUED,
			self::STATE_PROCESSING,
			self::STATE_PARTIAL,
			self::STATE_OPTIMIZED,
			self::STATE_FAILED,
			self::STATE_STALE,
			self::STATE_EXCLUDED,
			self::STATE_SKIPPED,
		);
	}

	/**
	 * Get valid formats.
	 *
	 * @return string[]
	 */
	public static function formats(): array {
		return array(
			self::FORMAT_WEBP,
			self::FORMAT_AVIF,
		);
	}

	/**
	 * Normalize state.
	 *
	 * @param string $state State.
	 * @return string
	 */
	public static function normalize_state( string $state ): string {
		$state = strtolower( trim( $state ) );

		return in_array( $state, self::states(), true ) ? $state : self::STATE_UNPROCESSED;
	}

	/**
	 * Normalize formats.
	 *
	 * @param array<mixed> $formats Formats.
	 * @return string[]
	 */
	public static function normalize_formats( array $formats ): array {
		$seen = array();

		foreach ( $formats as $format ) {
			if ( ! is_scalar( $format ) ) {
				continue;
			}

			$format = strtolower( trim( (string) $format ) );

			if ( in_array( $format, self::formats(), true ) ) {
				$seen[ $format ] = true;
			}
		}

		return array_values(
			array_filter(
				self::formats(),
				static function ( string $format ) use ( $seen ): bool {
					return isset( $seen[ $format ] );
				}
			)
		);
	}

	/**
	 * Get state.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * Get ready formats.
	 *
	 * @return string[]
	 */
	public function formats_ready(): array {
		return $this->formats;
	}

	/**
	 * Get updated timestamp.
	 *
	 * @return int
	 */
	public function updated_at(): int {
		return $this->updated_at;
	}

	/**
	 * Get error code.
	 *
	 * @return string|null
	 */
	public function error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Whether excluded.
	 *
	 * @return bool
	 */
	public function excluded(): bool {
		return $this->excluded;
	}

	/**
	 * Serialize status.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'state'      => $this->state,
			'formats'    => $this->formats,
			'updated_at' => $this->updated_at,
			'error_code' => $this->error_code,
			'excluded'   => $this->excluded,
		);
	}

	/**
	 * Normalize error code.
	 *
	 * @param string|null $error_code Error code.
	 * @return string|null
	 */
	private static function normalize_error_code( ?string $error_code ): ?string {
		if ( null === $error_code ) {
			return null;
		}

		$error_code = strtolower( trim( $error_code ) );
		$error_code = (string) preg_replace( '/[^a-z0-9_]/', '_', $error_code );
		$error_code = trim( $error_code, '_' );

		return '' === $error_code ? null : substr( $error_code, 0, 64 );
	}

	/**
	 * Get string from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @param string       $fallback Fallback.
	 * @return string
	 */
	private static function array_string( array $values, string $key, string $fallback ): string {
		return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? (string) $values[ $key ] : $fallback;
	}

	/**
	 * Get nullable string from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return string|null
	 */
	private static function array_nullable_string( array $values, string $key ): ?string {
		return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? (string) $values[ $key ] : null;
	}

	/**
	 * Get list from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return array<mixed>
	 */
	private static function array_list( array $values, string $key ): array {
		return isset( $values[ $key ] ) && is_array( $values[ $key ] ) ? $values[ $key ] : array();
	}

	/**
	 * Get int from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return int
	 */
	private static function array_int( array $values, string $key ): int {
		return isset( $values[ $key ] ) && is_numeric( $values[ $key ] ) ? max( 0, (int) $values[ $key ] ) : 0;
	}

	/**
	 * Get bool from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return bool
	 */
	private static function array_bool( array $values, string $key ): bool {
		return isset( $values[ $key ] ) && (bool) $values[ $key ];
	}
}
