<?php
/**
 * Queue operation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Immutable queue-operation result object.
 */
final class QueueStatus {

	public const CODE_QUEUED              = 'queued';
	public const CODE_ALREADY_QUEUED      = 'already_queued';
	public const CODE_QUEUE_UNAVAILABLE   = 'queue_unavailable';
	public const CODE_ENQUEUE_FAILED      = 'enqueue_failed';
	public const CODE_INVALID_JOB_PAYLOAD = 'invalid_job_payload';

	/**
	 * Whether the queue operation succeeded.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Action ID.
	 *
	 * @var int|null
	 */
	private $action_id;

	/**
	 * Whether async scheduling was used.
	 *
	 * @var bool
	 */
	private $async;

	/**
	 * Scheduled timestamp for delayed jobs.
	 *
	 * @var int|null
	 */
	private $scheduled_timestamp;

	/**
	 * Queue result codes.
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
	 * Create result.
	 *
	 * @param bool     $success Whether the queue operation succeeded.
	 * @param int|null $action_id Action ID.
	 * @param bool     $async Whether async scheduling was used.
	 * @param int|null $scheduled_timestamp Scheduled timestamp.
	 * @param string[] $codes Result codes.
	 * @param string[] $messages Messages.
	 */
	public function __construct(
		bool $success,
		?int $action_id = null,
		bool $async = false,
		?int $scheduled_timestamp = null,
		array $codes = array(),
		array $messages = array()
	) {
		$this->success             = $success;
		$this->action_id           = null !== $action_id && 0 < $action_id ? $action_id : null;
		$this->async               = $async;
		$this->scheduled_timestamp = null !== $scheduled_timestamp ? max( 0, $scheduled_timestamp ) : null;
		$this->codes               = array_values( array_filter( array_map( array( $this, 'normalize_code' ), $codes ) ) );
		$this->messages            = array_values( array_filter( array_map( 'trim', $messages ) ) );
	}

	/**
	 * Build queued result.
	 *
	 * @param int      $action_id Action ID.
	 * @param bool     $async Whether async scheduling was used.
	 * @param int|null $scheduled_timestamp Scheduled timestamp.
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function queued( int $action_id, bool $async, ?int $scheduled_timestamp = null, array $messages = array() ): self {
		return new self(
			true,
			$action_id,
			$async,
			$scheduled_timestamp,
			array( self::CODE_QUEUED ),
			$messages
		);
	}

	/**
	 * Build already-queued result.
	 *
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function already_queued( array $messages = array() ): self {
		return new self(
			true,
			null,
			false,
			null,
			array( self::CODE_ALREADY_QUEUED ),
			$messages
		);
	}

	/**
	 * Build queue-unavailable result.
	 *
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function queue_unavailable( array $messages = array() ): self {
		return new self(
			false,
			null,
			false,
			null,
			array( self::CODE_QUEUE_UNAVAILABLE ),
			$messages
		);
	}

	/**
	 * Build enqueue-failed result.
	 *
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function enqueue_failed( array $messages = array() ): self {
		return new self(
			false,
			null,
			false,
			null,
			array( self::CODE_ENQUEUE_FAILED ),
			$messages
		);
	}

	/**
	 * Build invalid-payload result.
	 *
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function invalid_job_payload( array $messages = array() ): self {
		return new self(
			false,
			null,
			false,
			null,
			array( self::CODE_INVALID_JOB_PAYLOAD ),
			$messages
		);
	}

	/**
	 * Determine whether the queue operation succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->success;
	}

	/**
	 * Get action ID.
	 *
	 * @return int|null
	 */
	public function action_id(): ?int {
		return $this->action_id;
	}

	/**
	 * Determine whether async scheduling was used.
	 *
	 * @return bool
	 */
	public function is_async(): bool {
		return $this->async;
	}

	/**
	 * Get scheduled timestamp.
	 *
	 * @return int|null
	 */
	public function scheduled_timestamp(): ?int {
		return $this->scheduled_timestamp;
	}

	/**
	 * Get queue result codes.
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
	 * Determine whether a code is present.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $this->normalize_code( $code ), $this->codes, true );
	}

	/**
	 * Serialize the queue status.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'successful'          => $this->success,
			'action_id'           => $this->action_id,
			'async'               => $this->async,
			'scheduled_timestamp' => $this->scheduled_timestamp,
			'codes'               => $this->codes,
			'messages'            => $this->messages,
		);
	}

	/**
	 * Normalize a queue result code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return substr( $code, 0, 64 );
	}
}
