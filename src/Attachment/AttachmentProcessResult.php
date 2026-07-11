<?php
/**
 * Attachment process result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;

/**
 * Immutable value object representing the outcome of an attachment processing run.
 */
final class AttachmentProcessResult {

	public const CODE_PROCESSED          = 'processed';
	public const CODE_SKIPPED_LOCKED     = 'skipped_locked';
	public const CODE_SKIPPED_NO_SOURCES = 'skipped_no_sources';
	public const CODE_SKIPPED_NO_FORMAT  = 'skipped_no_target_format';
	public const CODE_FINGERPRINT_FAILED = 'fingerprint_failed';
	public const CODE_SKIPPED_EXCLUDED   = 'skipped_excluded';
	public const CODE_UNEXPECTED_ERROR   = 'unexpected_error';

	/**
	 * Whether the run completed successfully (including successful skips).
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Whether the run was blocked by a lock.
	 *
	 * @var bool
	 */
	private $locked;

	/**
	 * Conversion results.
	 *
	 * @var ConversionResultCollection|null
	 */
	private $results;

	/**
	 * Taxonomy codes.
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
	 * Target format processed.
	 *
	 * @var string|null
	 */
	private $target_format;

	/**
	 * Start cursor.
	 *
	 * @var int
	 */
	private $cursor;

	/**
	 * Cursor for the next run.
	 *
	 * @var int
	 */
	private $next_cursor;

	/**
	 * Whether processing for the target format is complete.
	 *
	 * @var bool
	 */
	private $complete;

	/**
	 * Create process result.
	 *
	 * @param bool                            $success Whether the run completed.
	 * @param bool                            $locked Whether the run was locked.
	 * @param ConversionResultCollection|null $results Conversion results.
	 * @param string[]                        $codes Taxonomy codes.
	 * @param string[]                        $messages Human-readable messages.
	 * @param string|null                     $target_format Target format.
	 * @param int                             $cursor Start cursor.
	 * @param int                             $next_cursor Next cursor.
	 * @param bool                            $complete Whether processing is complete.
	 */
	public function __construct(
		bool $success,
		bool $locked,
		?ConversionResultCollection $results = null,
		array $codes = array(),
		array $messages = array(),
		?string $target_format = null,
		int $cursor = 0,
		int $next_cursor = 0,
		bool $complete = true
	) {
		$this->success       = $success;
		$this->locked        = $locked;
		$this->results       = $results;
		$this->codes         = array_values( array_filter( array_map( 'trim', $codes ) ) );
		$this->messages      = array_values( array_filter( array_map( 'trim', $messages ) ) );
		$this->target_format = $this->normalize_target_format( $target_format );
		$this->cursor        = max( 0, $cursor );
		$this->next_cursor   = max( $this->cursor, $next_cursor );
		$this->complete      = $complete;
	}

	/**
	 * Create a successful result.
	 *
	 * @param ConversionResultCollection $results Results.
	 * @param string[]                   $codes Codes.
	 * @param string[]                   $messages Messages.
	 * @param string|null                $target_format Target format.
	 * @param int                        $cursor Start cursor.
	 * @param int                        $next_cursor Next cursor.
	 * @param bool                       $complete Whether processing is complete.
	 * @return self
	 */
	public static function success(
		ConversionResultCollection $results,
		array $codes = array(),
		array $messages = array(),
		?string $target_format = null,
		int $cursor = 0,
		int $next_cursor = 0,
		bool $complete = true
	): self {
		return new self( true, false, $results, $codes, $messages, $target_format, $cursor, $next_cursor, $complete );
	}

	/**
	 * Create a skipped result.
	 *
	 * @param string      $code Skip code.
	 * @param string      $message Skip message.
	 * @param string|null $target_format Target format.
	 * @param int         $cursor Start cursor.
	 * @param int         $next_cursor Next cursor.
	 * @return self
	 */
	public static function skip( string $code, string $message, ?string $target_format = null, int $cursor = 0, int $next_cursor = 0 ): self {
		return new self( true, false, null, array( $code ), array( $message ), $target_format, $cursor, $next_cursor, true );
	}

	/**
	 * Create a locked result.
	 *
	 * @return self
	 */
	public static function locked(): self {
		return new self( false, true, null, array( self::CODE_SKIPPED_LOCKED ), array( 'Attachment is currently locked by another process.' ) );
	}

	/**
	 * Create a failed result.
	 *
	 * @param string      $code Failure code.
	 * @param string      $message Failure message.
	 * @param string|null $target_format Target format.
	 * @param int         $cursor Start cursor.
	 * @param int         $next_cursor Next cursor.
	 * @return self
	 */
	public static function failure( string $code, string $message, ?string $target_format = null, int $cursor = 0, int $next_cursor = 0 ): self {
		return new self( false, false, null, array( $code ), array( $message ), $target_format, $cursor, $next_cursor, true );
	}

	/**
	 * Whether the run completed successfully.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->success;
	}

	/**
	 * Whether the run was locked.
	 *
	 * @return bool
	 */
	public function is_locked(): bool {
		return $this->locked;
	}

	/**
	 * Get conversion results.
	 *
	 * @return ConversionResultCollection|null
	 */
	public function results(): ?ConversionResultCollection {
		return $this->results;
	}

	/**
	 * Get taxonomy codes.
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
	 * Get processed target format.
	 *
	 * @return string|null
	 */
	public function target_format(): ?string {
		return $this->target_format;
	}

	/**
	 * Get start cursor.
	 *
	 * @return int
	 */
	public function cursor(): int {
		return $this->cursor;
	}

	/**
	 * Get next cursor.
	 *
	 * @return int
	 */
	public function next_cursor(): int {
		return $this->next_cursor;
	}

	/**
	 * Whether the target format is complete.
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return $this->complete;
	}

	/**
	 * Get process summary.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		return array(
			'successful'    => $this->success,
			'locked'        => $this->locked,
			'target_format' => $this->target_format,
			'cursor'        => $this->cursor,
			'next_cursor'   => $this->next_cursor,
			'complete'      => $this->complete,
			'results'       => $this->results instanceof ConversionResultCollection ? $this->results->summary() : array(),
			'codes'         => $this->codes,
		);
	}

	/**
	 * Normalize target format.
	 *
	 * @param string|null $target_format Target format.
	 * @return string|null
	 */
	private function normalize_target_format( ?string $target_format ): ?string {
		if ( null === $target_format ) {
			return null;
		}

		$target_format = strtolower( trim( $target_format ) );
		$target_format = (string) preg_replace( '/[^a-z0-9_]/', '_', $target_format );
		$target_format = trim( $target_format, '_' );

		return '' === $target_format ? null : substr( $target_format, 0, 32 );
	}
}
