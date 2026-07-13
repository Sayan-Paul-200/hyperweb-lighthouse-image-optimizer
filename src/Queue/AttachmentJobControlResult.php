<?php
/**
 * Attachment job control result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Carries global attachment-job control outcomes.
 */
final class AttachmentJobControlResult {

	public const CODE_CANCELLED     = 'attachment_jobs_cancelled';
	public const CODE_UNAVAILABLE   = 'attachment_jobs_unavailable';
	public const CODE_CANCEL_FAILED = 'attachment_job_cancel_failed';

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Stable codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Cancelled count.
	 *
	 * @var int
	 */
	private $cancelled_actions;

	/**
	 * Create the result.
	 *
	 * @param bool     $successful Whether successful.
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $cancelled_actions Cancelled count.
	 */
	public function __construct( bool $successful, array $codes = array(), array $messages = array(), int $cancelled_actions = 0 ) {
		$this->successful        = $successful;
		$this->codes             = array_values( array_filter( array_map( 'strval', $codes ) ) );
		$this->messages          = array_values( array_filter( array_map( 'trim', $messages ) ) );
		$this->cancelled_actions = max( 0, $cancelled_actions );
	}

	/**
	 * Build a success result.
	 *
	 * @param int      $cancelled_actions Cancelled count.
	 * @param string[] $messages Messages.
	 * @param string[] $codes Stable codes.
	 * @return self
	 */
	public static function success( int $cancelled_actions = 0, array $messages = array(), array $codes = array() ): self {
		return new self( true, array() === $codes ? array( self::CODE_CANCELLED ) : $codes, $messages, $cancelled_actions );
	}

	/**
	 * Build a failure result.
	 *
	 * @param string[] $codes Stable codes.
	 * @param string[] $messages Messages.
	 * @param int      $cancelled_actions Cancelled count.
	 * @return self
	 */
	public static function failure( array $codes = array(), array $messages = array(), int $cancelled_actions = 0 ): self {
		return new self( false, $codes, $messages, $cancelled_actions );
	}

	/**
	 * Whether successful.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get codes.
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
	 * Get cancelled count.
	 *
	 * @return int
	 */
	public function cancelled_actions(): int {
		return $this->cancelled_actions;
	}

	/**
	 * Serialize to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful'        => $this->is_successful(),
			'codes'             => $this->codes(),
			'messages'          => $this->messages(),
			'cancelled_actions' => $this->cancelled_actions(),
		);
	}
}
