<?php
/**
 * Attachment action result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Carries one normalized attachment-action result.
 */
final class AttachmentActionResult {

	/**
	 * Whether the action succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Action name.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Refreshed attachment snapshot.
	 *
	 * @var array<string,mixed>
	 */
	private $snapshot;

	/**
	 * Queue result payloads.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $queue;

	/**
	 * Stable error code.
	 *
	 * @var string|null
	 */
	private $error_code;

	/**
	 * User-safe error message.
	 *
	 * @var string|null
	 */
	private $error_message;

	/**
	 * HTTP status code for failures.
	 *
	 * @var int
	 */
	private $status_code;

	/**
	 * Create the result.
	 *
	 * @param bool                     $successful Whether successful.
	 * @param string                   $action Action name.
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>      $snapshot Snapshot payload.
	 * @param array<int,array<string,mixed>> $queue Queue payloads.
	 * @param string|null              $error_code Error code.
	 * @param string|null              $error_message Error message.
	 * @param int                      $status_code HTTP status code.
	 */
	public function __construct(
		bool $successful,
		string $action,
		int $attachment_id,
		array $snapshot,
		array $queue = array(),
		?string $error_code = null,
		?string $error_message = null,
		int $status_code = 200
	) {
		$this->successful    = $successful;
		$this->action        = trim( $action );
		$this->attachment_id = max( 0, $attachment_id );
		$this->snapshot      = $snapshot;
		$this->queue         = array_values( $queue );
		$this->error_code    = null === $error_code ? null : trim( $error_code );
		$this->error_message = null === $error_message ? null : trim( $error_message );
		$this->status_code   = max( 100, $status_code );
	}

	/**
	 * Build a successful result.
	 *
	 * @param string                   $action Action name.
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>      $snapshot Snapshot payload.
	 * @param array<int,array<string,mixed>> $queue Queue payloads.
	 * @return self
	 */
	public static function success( string $action, int $attachment_id, array $snapshot, array $queue = array() ): self {
		return new self( true, $action, $attachment_id, $snapshot, $queue );
	}

	/**
	 * Build a failed result.
	 *
	 * @param string                   $action Action name.
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>      $snapshot Snapshot payload.
	 * @param string                   $error_code Stable error code.
	 * @param string                   $error_message User-safe error message.
	 * @param int                      $status_code HTTP status code.
	 * @param array<int,array<string,mixed>> $queue Queue payloads.
	 * @return self
	 */
	public static function failure(
		string $action,
		int $attachment_id,
		array $snapshot,
		string $error_code,
		string $error_message,
		int $status_code,
		array $queue = array()
	): self {
		return new self( false, $action, $attachment_id, $snapshot, $queue, $error_code, $error_message, $status_code );
	}

	/**
	 * Whether the action succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get the action name.
	 *
	 * @return string
	 */
	public function action(): string {
		return $this->action;
	}

	/**
	 * Get the attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get the snapshot payload.
	 *
	 * @return array<string,mixed>
	 */
	public function snapshot(): array {
		return $this->snapshot;
	}

	/**
	 * Get queue payloads.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function queue(): array {
		return $this->queue;
	}

	/**
	 * Get the stable error code.
	 *
	 * @return string|null
	 */
	public function error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Get the user-safe error message.
	 *
	 * @return string|null
	 */
	public function error_message(): ?string {
		return $this->error_message;
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return int
	 */
	public function status_code(): int {
		return $this->status_code;
	}

	/**
	 * Serialize the result payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'action'        => $this->action,
			'attachment_id' => $this->attachment_id,
			'snapshot'      => $this->snapshot,
			'queue'         => $this->queue,
		);
	}
}
