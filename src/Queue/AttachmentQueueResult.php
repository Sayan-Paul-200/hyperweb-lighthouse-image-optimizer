<?php
/**
 * Attachment queue result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Carries per-attachment selected-format queueing outcomes.
 */
final class AttachmentQueueResult {

	public const CODE_QUEUED = 'queued';
	public const CODE_ALREADY_QUEUED = 'already_queued';
	public const CODE_QUEUE_PAUSED = 'queue_paused';
	public const CODE_ATTACHMENT_EXCLUDED = 'attachment_excluded';
	public const CODE_QUEUE_UNAVAILABLE = 'queue_unavailable';
	public const CODE_NO_ENABLED_FORMATS = 'no_enabled_formats';
	public const CODE_ATTACHMENT_SOURCE_UNAVAILABLE = 'attachment_source_unavailable';
	public const CODE_ATTACHMENT_STATE_UPDATE_FAILED = 'attachment_state_update_failed';
	public const CODE_ENQUEUE_FAILED = 'queue_enqueue_failed';

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Primary code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Queue statuses keyed by target.
	 *
	 * @var array<string,QueueStatus>
	 */
	private $queue_statuses;

	/**
	 * Create the result.
	 *
	 * @param bool                      $successful Whether successful.
	 * @param string                    $code Primary code.
	 * @param string                    $message Message.
	 * @param array<string,QueueStatus> $queue_statuses Queue statuses keyed by target.
	 */
	public function __construct( bool $successful, string $code, string $message, array $queue_statuses = array() ) {
		$this->successful     = $successful;
		$this->code           = strtolower( trim( $code ) );
		$this->message        = trim( $message );
		$this->queue_statuses = array_filter(
			$queue_statuses,
			static function ( $status ): bool {
				return $status instanceof QueueStatus;
			}
		);
	}

	/**
	 * Build a failure result.
	 *
	 * @param string                    $code Primary code.
	 * @param string                    $message Message.
	 * @param array<string,QueueStatus> $queue_statuses Queue statuses keyed by target.
	 * @return self
	 */
	public static function failure( string $code, string $message, array $queue_statuses = array() ): self {
		return new self( false, $code, $message, $queue_statuses );
	}

	/**
	 * Build a success result.
	 *
	 * @param string                    $code Primary code.
	 * @param string                    $message Message.
	 * @param array<string,QueueStatus> $queue_statuses Queue statuses keyed by target.
	 * @return self
	 */
	public static function success( string $code, string $message, array $queue_statuses = array() ): self {
		return new self( true, $code, $message, $queue_statuses );
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
	 * Get message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get queue statuses keyed by target.
	 *
	 * @return array<string,QueueStatus>
	 */
	public function queue_statuses(): array {
		return $this->queue_statuses;
	}

	/**
	 * Whether any target was newly queued.
	 *
	 * @return bool
	 */
	public function has_newly_queued_work(): bool {
		foreach ( $this->queue_statuses as $status ) {
			if ( $status->has_code( QueueStatus::CODE_QUEUED ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether all successful targets were already queued.
	 *
	 * @return bool
	 */
	public function all_already_queued(): bool {
		if ( array() === $this->queue_statuses ) {
			return false;
		}

		foreach ( $this->queue_statuses as $status ) {
			if ( ! $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) ) {
				return false;
			}
		}

		return true;
	}
}
