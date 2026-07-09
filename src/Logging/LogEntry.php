<?php
/**
 * Immutable log entry.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries a single sanitized or raw log entry.
 */
final class LogEntry {

	/**
	 * GMT datetime text.
	 *
	 * @var string
	 */
	private $created_at_gmt;

	/**
	 * Log level.
	 *
	 * @var string
	 */
	private $level;

	/**
	 * Stable log code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Human-readable message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Structured diagnostic context.
	 *
	 * @var array<mixed>
	 */
	private $context;

	/**
	 * Attachment ID.
	 *
	 * @var int|null
	 */
	private $attachment_id;

	/**
	 * Queue job ID.
	 *
	 * @var string|null
	 */
	private $job_id;

	/**
	 * Create a log entry.
	 *
	 * @param string       $created_at_gmt GMT datetime text.
	 * @param string       $level Log level.
	 * @param string       $code Stable log code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 */
	public function __construct(
		string $created_at_gmt,
		string $level,
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	) {
		$this->created_at_gmt = $created_at_gmt;
		$this->level          = $level;
		$this->code           = $code;
		$this->message        = $message;
		$this->context        = $context;
		$this->attachment_id  = ( null !== $attachment_id && $attachment_id > 0 ) ? $attachment_id : null;
		$this->job_id         = $this->normalize_job_id( $job_id );
	}

	/**
	 * Get the GMT datetime text.
	 *
	 * @return string
	 */
	public function created_at_gmt(): string {
		return $this->created_at_gmt;
	}

	/**
	 * Get the log level.
	 *
	 * @return string
	 */
	public function level(): string {
		return $this->level;
	}

	/**
	 * Get the stable code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get the message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get the context.
	 *
	 * @return array<mixed>
	 */
	public function context(): array {
		return $this->context;
	}

	/**
	 * Get the attachment ID.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get the job ID.
	 *
	 * @return string|null
	 */
	public function job_id(): ?string {
		return $this->job_id;
	}

	/**
	 * Normalize a job ID into a bounded nullable string.
	 *
	 * @param string|null $job_id Queue job ID.
	 * @return string|null
	 */
	private function normalize_job_id( ?string $job_id ): ?string {
		if ( null === $job_id ) {
			return null;
		}

		$job_id = trim( $job_id );

		if ( '' === $job_id ) {
			return null;
		}

		return substr( $job_id, 0, 191 );
	}
}
