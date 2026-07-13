<?php
/**
 * Attachment cleanup result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries attachment cleanup outcomes and dry-run reconciliation findings.
 */
final class AttachmentCleanupResult {

	public const SEVERITY_SUCCESS = 'success';
	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_FAILURE = 'failure';

	public const CODE_COMPLETED                     = 'cleanup_completed';
	public const CODE_INVALID_ATTACHMENT            = 'invalid_attachment_id';
	public const CODE_DERIVATIVES_DELETED           = 'derivatives_deleted';
	public const CODE_DERIVATIVE_MISSING            = 'derivative_missing';
	public const CODE_DERIVATIVE_REJECTED           = 'derivative_rejected';
	public const CODE_ORIGINAL_PRESERVED            = 'original_preserved';
	public const CODE_UPLOADS_DIRECTORY_UNAVAILABLE = 'uploads_directory_unavailable';
	public const CODE_ATTACHMENT_JOBS_CANCELLED     = 'attachment_jobs_cancelled';
	public const CODE_ATTACHMENT_JOBS_UNAVAILABLE   = 'attachment_jobs_unavailable';
	public const CODE_ATTACHMENT_JOB_CANCEL_FAILED  = 'attachment_job_cancel_failed';
	public const CODE_ATTACHMENT_META_DELETED       = 'attachment_meta_deleted';
	public const CODE_ATTACHMENT_META_DELETE_FAILED = 'attachment_meta_delete_failed';
	public const CODE_ORPHAN_DERIVATIVES_DETECTED   = 'orphan_derivatives_detected';
	public const CODE_NO_ORPHAN_DERIVATIVES         = 'no_orphan_derivatives';

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
	 * Number of deleted derivative files.
	 *
	 * @var int
	 */
	private $deleted_files;

	/**
	 * Number of cancelled pending actions.
	 *
	 * @var int
	 */
	private $cancelled_actions;

	/**
	 * Number of deleted meta keys.
	 *
	 * @var int
	 */
	private $deleted_meta;

	/**
	 * Number of orphan derivative files detected.
	 *
	 * @var int
	 */
	private $orphan_files;

	/**
	 * Deleted derivative file samples.
	 *
	 * @var string[]
	 */
	private $deleted_file_samples;

	/**
	 * Deleted derivative relative paths.
	 *
	 * @var string[]
	 */
	private $deleted_relative_paths;

	/**
	 * Orphan derivative file samples.
	 *
	 * @var string[]
	 */
	private $orphan_file_samples;

	/**
	 * Create result.
	 *
	 * @param string   $severity Severity.
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $deleted_files Deleted derivative count.
	 * @param int      $cancelled_actions Cancelled action count.
	 * @param int      $deleted_meta Deleted meta count.
	 * @param int      $orphan_files Orphan count.
	 * @param string[] $deleted_file_samples Deleted file samples.
	 * @param string[] $orphan_file_samples Orphan file samples.
	 * @param string[] $deleted_relative_paths Deleted derivative relative paths.
	 */
	private function __construct(
		string $severity,
		array $codes = array(),
		array $messages = array(),
		int $deleted_files = 0,
		int $cancelled_actions = 0,
		int $deleted_meta = 0,
		int $orphan_files = 0,
		array $deleted_file_samples = array(),
		array $orphan_file_samples = array(),
		array $deleted_relative_paths = array()
	) {
		$this->severity             = in_array( $severity, array( self::SEVERITY_SUCCESS, self::SEVERITY_WARNING, self::SEVERITY_FAILURE ), true )
			? $severity
			: self::SEVERITY_FAILURE;
		$this->codes                = $this->normalize_codes( $codes );
		$this->messages             = $this->normalize_messages( $messages );
		$this->deleted_files        = max( 0, $deleted_files );
		$this->cancelled_actions    = max( 0, $cancelled_actions );
		$this->deleted_meta         = max( 0, $deleted_meta );
		$this->orphan_files         = max( 0, $orphan_files );
		$this->deleted_file_samples   = $this->normalize_relative_paths( $deleted_file_samples );
		$this->deleted_relative_paths = $this->normalize_relative_paths(
			array() === $deleted_relative_paths ? $deleted_file_samples : $deleted_relative_paths,
			0
		);
		$this->orphan_file_samples    = $this->normalize_relative_paths( $orphan_file_samples );
	}

	/**
	 * Build success result.
	 *
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $deleted_files Deleted derivative count.
	 * @param int      $cancelled_actions Cancelled action count.
	 * @param int      $deleted_meta Deleted meta count.
	 * @param int      $orphan_files Orphan count.
	 * @param string[] $deleted_file_samples Deleted file samples.
	 * @param string[] $orphan_file_samples Orphan file samples.
	 * @param string[] $deleted_relative_paths Deleted derivative relative paths.
	 * @return self
	 */
	public static function success(
		array $codes = array(),
		array $messages = array(),
		int $deleted_files = 0,
		int $cancelled_actions = 0,
		int $deleted_meta = 0,
		int $orphan_files = 0,
		array $deleted_file_samples = array(),
		array $orphan_file_samples = array(),
		array $deleted_relative_paths = array()
	): self {
		return new self(
			self::SEVERITY_SUCCESS,
			$codes,
			$messages,
			$deleted_files,
			$cancelled_actions,
			$deleted_meta,
			$orphan_files,
			$deleted_file_samples,
			$orphan_file_samples,
			$deleted_relative_paths
		);
	}

	/**
	 * Build warning result.
	 *
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $deleted_files Deleted derivative count.
	 * @param int      $cancelled_actions Cancelled action count.
	 * @param int      $deleted_meta Deleted meta count.
	 * @param int      $orphan_files Orphan count.
	 * @param string[] $deleted_file_samples Deleted file samples.
	 * @param string[] $orphan_file_samples Orphan file samples.
	 * @param string[] $deleted_relative_paths Deleted derivative relative paths.
	 * @return self
	 */
	public static function warning(
		array $codes = array(),
		array $messages = array(),
		int $deleted_files = 0,
		int $cancelled_actions = 0,
		int $deleted_meta = 0,
		int $orphan_files = 0,
		array $deleted_file_samples = array(),
		array $orphan_file_samples = array(),
		array $deleted_relative_paths = array()
	): self {
		return new self(
			self::SEVERITY_WARNING,
			$codes,
			$messages,
			$deleted_files,
			$cancelled_actions,
			$deleted_meta,
			$orphan_files,
			$deleted_file_samples,
			$orphan_file_samples,
			$deleted_relative_paths
		);
	}

	/**
	 * Build failure result.
	 *
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $deleted_files Deleted derivative count.
	 * @param int      $cancelled_actions Cancelled action count.
	 * @param int      $deleted_meta Deleted meta count.
	 * @param int      $orphan_files Orphan count.
	 * @param string[] $deleted_file_samples Deleted file samples.
	 * @param string[] $orphan_file_samples Orphan file samples.
	 * @param string[] $deleted_relative_paths Deleted derivative relative paths.
	 * @return self
	 */
	public static function failure(
		array $codes = array(),
		array $messages = array(),
		int $deleted_files = 0,
		int $cancelled_actions = 0,
		int $deleted_meta = 0,
		int $orphan_files = 0,
		array $deleted_file_samples = array(),
		array $orphan_file_samples = array(),
		array $deleted_relative_paths = array()
	): self {
		return new self(
			self::SEVERITY_FAILURE,
			$codes,
			$messages,
			$deleted_files,
			$cancelled_actions,
			$deleted_meta,
			$orphan_files,
			$deleted_file_samples,
			$orphan_file_samples,
			$deleted_relative_paths
		);
	}

	/**
	 * Combine multiple cleanup results.
	 *
	 * @param self ...$results Results.
	 * @return self
	 */
	public static function combine( self ...$results ): self {
		$severity             = self::SEVERITY_SUCCESS;
		$codes                = array();
		$messages             = array();
		$deleted_files        = 0;
		$cancelled_actions    = 0;
		$deleted_meta         = 0;
		$orphan_files         = 0;
		$deleted_file_samples   = array();
		$orphan_file_samples    = array();
		$deleted_relative_paths = array();

		foreach ( $results as $result ) {
			if ( self::SEVERITY_FAILURE === $result->severity() ) {
				$severity = self::SEVERITY_FAILURE;
			} elseif ( self::SEVERITY_WARNING === $result->severity() && self::SEVERITY_FAILURE !== $severity ) {
				$severity = self::SEVERITY_WARNING;
			}

			$codes                = array_merge( $codes, $result->codes() );
			$messages             = array_merge( $messages, $result->messages() );
			$deleted_files       += $result->deleted_files();
			$cancelled_actions   += $result->cancelled_actions();
			$deleted_meta        += $result->deleted_meta();
			$orphan_files        += $result->orphan_files();
			$deleted_file_samples   = array_merge( $deleted_file_samples, $result->deleted_file_samples() );
			$orphan_file_samples    = array_merge( $orphan_file_samples, $result->orphan_file_samples() );
			$deleted_relative_paths = array_merge( $deleted_relative_paths, $result->deleted_relative_paths() );
		}

		return new self(
			$severity,
			$codes,
			$messages,
			$deleted_files,
			$cancelled_actions,
			$deleted_meta,
			$orphan_files,
			$deleted_file_samples,
			$orphan_file_samples,
			$deleted_relative_paths
		);
	}

	/**
	 * Whether the operation succeeded without fatal failure.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return self::SEVERITY_FAILURE !== $this->severity;
	}

	/**
	 * Whether the result has warnings.
	 *
	 * @return bool
	 */
	public function has_warnings(): bool {
		return self::SEVERITY_WARNING === $this->severity;
	}

	/**
	 * Get severity.
	 *
	 * @return string
	 */
	public function severity(): string {
		return $this->severity;
	}

	/**
	 * Get stable codes.
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
	 * Whether result contains a code.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $code, $this->codes, true );
	}

	/**
	 * Get deleted derivative count.
	 *
	 * @return int
	 */
	public function deleted_files(): int {
		return $this->deleted_files;
	}

	/**
	 * Get cancelled action count.
	 *
	 * @return int
	 */
	public function cancelled_actions(): int {
		return $this->cancelled_actions;
	}

	/**
	 * Get deleted meta count.
	 *
	 * @return int
	 */
	public function deleted_meta(): int {
		return $this->deleted_meta;
	}

	/**
	 * Get orphan count.
	 *
	 * @return int
	 */
	public function orphan_files(): int {
		return $this->orphan_files;
	}

	/**
	 * Get deleted file samples.
	 *
	 * @return string[]
	 */
	public function deleted_file_samples(): array {
		return $this->deleted_file_samples;
	}

	/**
	 * Get deleted derivative relative paths.
	 *
	 * @return string[]
	 */
	public function deleted_relative_paths(): array {
		return $this->deleted_relative_paths;
	}

	/**
	 * Get orphan file samples.
	 *
	 * @return string[]
	 */
	public function orphan_file_samples(): array {
		return $this->orphan_file_samples;
	}

	/**
	 * Serialize safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful'           => $this->is_successful(),
			'warnings'             => $this->has_warnings(),
			'codes'                => $this->codes,
			'messages'             => $this->messages,
			'deleted_files'        => $this->deleted_files,
			'cancelled_actions'    => $this->cancelled_actions,
			'deleted_meta'         => $this->deleted_meta,
			'orphan_files'         => $this->orphan_files,
			'deleted_file_samples'   => $this->deleted_file_samples,
			'deleted_relative_paths' => $this->deleted_relative_paths,
			'orphan_file_samples'    => $this->orphan_file_samples,
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
				$normalized[] = substr( $message, 0, 500 );
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalize uploads-relative path samples.
	 *
	 * @param string[] $paths Paths.
	 * @param int      $limit Maximum items, or 0 for unbounded.
	 * @return string[]
	 */
	private function normalize_relative_paths( array $paths, int $limit = 20 ): array {
		$normalized = array();

		foreach ( $paths as $path ) {
			if ( ! is_scalar( $path ) ) {
				continue;
			}

			$path = str_replace( '\\', '/', trim( (string) $path ) );

			if ( '' !== $path && false === strpos( $path, "\0" ) && false === strpos( $path, '://' ) && 1 !== preg_match( '#^(?:[A-Za-z]:)?/#', $path ) ) {
				$normalized[] = ltrim( $path, '/' );
			}
		}

		$normalized = array_values( array_unique( $normalized ) );

		return 0 < $limit ? array_slice( $normalized, 0, $limit ) : $normalized;
	}
}
