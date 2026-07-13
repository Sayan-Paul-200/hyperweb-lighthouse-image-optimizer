<?php
/**
 * Bulk scan filters.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Carries one normalized dry-run scan filter set.
 */
final class BulkScanFilters {

	public const SCOPE_ALL_ELIGIBLE = 'all_eligible';
	public const SCOPE_MISSING_ONLY = 'missing_only';
	public const SCOPE_FAILED_ONLY  = 'failed_only';
	public const SCOPE_STALE_ONLY   = 'stale_only';

	public const TARGET_ALL_ENABLED = 'all_enabled';
	public const TARGET_WEBP        = 'webp';
	public const TARGET_AVIF        = 'avif';

	/**
	 * Scan scope.
	 *
	 * @var string
	 */
	private $scan_scope;

	/**
	 * Target format selector.
	 *
	 * @var string
	 */
	private $target_format;

	/**
	 * Optional uploaded-after date.
	 *
	 * @var string|null
	 */
	private $date_from;

	/**
	 * Optional uploaded-before date.
	 *
	 * @var string|null
	 */
	private $date_to;

	/**
	 * Optional narrowed attachment IDs.
	 *
	 * @var int[]
	 */
	private $attachment_ids;

	/**
	 * Create filters.
	 *
	 * @param string      $scan_scope Scan scope.
	 * @param string      $target_format Target format selector.
	 * @param string|null $date_from Optional start date.
	 * @param string|null $date_to Optional end date.
	 * @param int[]       $attachment_ids Optional narrowed attachment IDs.
	 */
	public function __construct(
		string $scan_scope = self::SCOPE_ALL_ELIGIBLE,
		string $target_format = self::TARGET_ALL_ENABLED,
		?string $date_from = null,
		?string $date_to = null,
		array $attachment_ids = array()
	) {
		$this->scan_scope     = self::normalize_scan_scope( $scan_scope );
		$this->target_format  = self::normalize_target_format( $target_format );
		$this->date_from      = self::normalize_date( $date_from );
		$this->date_to        = self::normalize_date( $date_to );
		$this->attachment_ids = self::normalize_attachment_ids( $attachment_ids );
	}

	/**
	 * Build filters from raw request data.
	 *
	 * @param array<string,mixed> $values Raw values.
	 * @return self
	 */
	public static function from_array( array $values ): self {
		return new self(
			isset( $values['scan_scope'] ) ? (string) $values['scan_scope'] : self::SCOPE_ALL_ELIGIBLE,
			isset( $values['target_format'] ) ? (string) $values['target_format'] : self::TARGET_ALL_ENABLED,
			isset( $values['date_from'] ) ? self::scalar_to_string( $values['date_from'] ) : null,
			isset( $values['date_to'] ) ? self::scalar_to_string( $values['date_to'] ) : null,
			self::normalize_attachment_ids( $values['attachment_ids'] ?? array() )
		);
	}

	/**
	 * Get valid scope slugs.
	 *
	 * @return string[]
	 */
	public static function scopes(): array {
		return array(
			self::SCOPE_ALL_ELIGIBLE,
			self::SCOPE_MISSING_ONLY,
			self::SCOPE_FAILED_ONLY,
			self::SCOPE_STALE_ONLY,
		);
	}

	/**
	 * Get valid target-format selectors.
	 *
	 * @return string[]
	 */
	public static function target_formats(): array {
		return array(
			self::TARGET_ALL_ENABLED,
			self::TARGET_WEBP,
			self::TARGET_AVIF,
		);
	}

	/**
	 * Normalize one scan scope.
	 *
	 * @param string $value Raw scope.
	 * @return string
	 */
	public static function normalize_scan_scope( string $value ): string {
		$value = strtolower( trim( $value ) );

		return in_array( $value, self::scopes(), true ) ? $value : self::SCOPE_ALL_ELIGIBLE;
	}

	/**
	 * Normalize one target-format selector.
	 *
	 * @param string $value Raw target format selector.
	 * @return string
	 */
	public static function normalize_target_format( string $value ): string {
		$value = strtolower( trim( $value ) );

		return in_array( $value, self::target_formats(), true ) ? $value : self::TARGET_ALL_ENABLED;
	}

	/**
	 * Normalize one optional YYYY-MM-DD date string.
	 *
	 * @param mixed $value Raw date value.
	 * @return string|null
	 */
	public static function normalize_date( $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$value = trim( (string) $value );

		if ( '' === $value || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Normalize one attachment-ID filter list.
	 *
	 * @param mixed $value Raw IDs.
	 * @return int[]
	 */
	public static function normalize_attachment_ids( $value ): array {
		$values = array();

		if ( is_string( $value ) ) {
			$split  = preg_split( '/[\s,]+/', trim( $value ) );
			$values = is_array( $split ) ? $split : array();
		} elseif ( is_array( $value ) ) {
			$values = $value;
		}

		$seen = array();

		foreach ( $values as $item ) {
			if ( ! is_scalar( $item ) || ! is_numeric( $item ) ) {
				continue;
			}

			$attachment_id = (int) $item;

			if ( 0 < $attachment_id ) {
				$seen[ $attachment_id ] = true;
			}
		}

		$ids = array_keys( $seen );
		sort( $ids, SORT_NUMERIC );

		return array_values(
			array_map(
				'intval',
				$ids
			)
		);
	}

	/**
	 * Get the scan scope.
	 *
	 * @return string
	 */
	public function scan_scope(): string {
		return $this->scan_scope;
	}

	/**
	 * Get the target-format selector.
	 *
	 * @return string
	 */
	public function target_format(): string {
		return $this->target_format;
	}

	/**
	 * Get the uploaded-after date.
	 *
	 * @return string|null
	 */
	public function date_from(): ?string {
		return $this->date_from;
	}

	/**
	 * Get the uploaded-before date.
	 *
	 * @return string|null
	 */
	public function date_to(): ?string {
		return $this->date_to;
	}

	/**
	 * Get narrowed attachment IDs.
	 *
	 * @return int[]
	 */
	public function attachment_ids(): array {
		return $this->attachment_ids;
	}

	/**
	 * Whether the filter narrows to explicit IDs.
	 *
	 * @return bool
	 */
	public function has_attachment_ids(): bool {
		return array() !== $this->attachment_ids;
	}

	/**
	 * Serialize filters.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'scan_scope'     => $this->scan_scope(),
			'target_format'  => $this->target_format(),
			'date_from'      => $this->date_from(),
			'date_to'        => $this->date_to(),
			'attachment_ids' => $this->attachment_ids(),
		);
	}

	/**
	 * Normalize one scalar string.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private static function scalar_to_string( $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}
}
