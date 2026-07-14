<?php
/**
 * Attachment reconciliation queue result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Carries one attachment reconciliation enqueue outcome.
 */
final class AttachmentReconciliationResult {

	public const CODE_QUEUED                         = 'queued';
	public const CODE_ALREADY_QUEUED                 = 'already_queued';
	public const CODE_QUEUE_PAUSED                   = 'queue_paused';
	public const CODE_ATTACHMENT_EXCLUDED            = 'attachment_excluded';
	public const CODE_QUEUE_UNAVAILABLE              = 'queue_unavailable';
	public const CODE_ATTACHMENT_SOURCE_UNAVAILABLE  = 'attachment_source_unavailable';
	public const CODE_OFFLOAD_UNSUPPORTED            = 'offload_unsupported';
	public const CODE_ATTACHMENT_STATE_UPDATE_FAILED = 'attachment_state_update_failed';
	public const CODE_ENQUEUE_FAILED                 = 'queue_enqueue_failed';

	/**
	 * Whether reconciliation was queued successfully.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Stable primary code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-safe message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Queue status for the attempted reconciliation job.
	 *
	 * @var QueueStatus|null
	 */
	private $queue_status;

	/**
	 * Create the result.
	 *
	 * @param bool             $successful Whether successful.
	 * @param string           $code Stable code.
	 * @param string           $message User-safe message.
	 * @param QueueStatus|null $queue_status Queue status.
	 */
	public function __construct( bool $successful, string $code, string $message, ?QueueStatus $queue_status = null ) {
		$this->successful   = $successful;
		$this->code         = strtolower( trim( $code ) );
		$this->message      = trim( $message );
		$this->queue_status = $queue_status;
	}

	/**
	 * Build a failure result.
	 *
	 * @param string           $code Stable code.
	 * @param string           $message User-safe message.
	 * @param QueueStatus|null $queue_status Queue status.
	 * @return self
	 */
	public static function failure( string $code, string $message, ?QueueStatus $queue_status = null ): self {
		return new self( false, $code, $message, $queue_status );
	}

	/**
	 * Build a success result.
	 *
	 * @param string           $code Stable code.
	 * @param string           $message User-safe message.
	 * @param QueueStatus|null $queue_status Queue status.
	 * @return self
	 */
	public static function success( string $code, string $message, ?QueueStatus $queue_status = null ): self {
		return new self( true, $code, $message, $queue_status );
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
	 * Get primary code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get user-safe message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get queue status.
	 *
	 * @return QueueStatus|null
	 */
	public function queue_status(): ?QueueStatus {
		return $this->queue_status;
	}
}
