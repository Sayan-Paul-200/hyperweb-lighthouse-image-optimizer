<?php
/**
 * Safe logs-screen row view.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries one safe row for logs UI consumers.
 */
final class LogRowView {

	/**
	 * Created-at time.
	 *
	 * @var string
	 */
	private $created_at_gmt;

	/**
	 * Level.
	 *
	 * @var string
	 */
	private $level;

	/**
	 * Stable code.
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
	 * Create the row view.
	 *
	 * @param string      $created_at_gmt Created-at time.
	 * @param string      $level Level.
	 * @param string      $code Stable code.
	 * @param string      $message User-safe message.
	 * @param int|null    $attachment_id Attachment ID.
	 * @param string|null $job_id Job ID.
	 */
	public function __construct(
		string $created_at_gmt,
		string $level,
		string $code,
		string $message,
		?int $attachment_id = null,
		?string $job_id = null
	) {
		$this->created_at_gmt = substr( trim( $created_at_gmt ), 0, 19 );
		$this->level          = LogLevel::normalize( $level );
		$this->code           = LogCode::normalize( $code );
		$this->message        = trim( $message );
		$this->attachment_id  = ( null !== $attachment_id && $attachment_id > 0 ) ? $attachment_id : null;
		$this->job_id         = $this->normalize_job_id( $job_id );
	}

	/**
	 * Build one row from raw database data.
	 *
	 * @param array<string,mixed> $row Raw row.
	 * @return self
	 */
	public static function from_row( array $row ): self {
		return new self(
			isset( $row['created_at_gmt'] ) && is_scalar( $row['created_at_gmt'] ) ? (string) $row['created_at_gmt'] : '',
			isset( $row['level'] ) && is_scalar( $row['level'] ) ? (string) $row['level'] : LogLevel::ERROR,
			isset( $row['code'] ) && is_scalar( $row['code'] ) ? (string) $row['code'] : LogCode::UNKNOWN,
			isset( $row['message'] ) && is_scalar( $row['message'] ) ? (string) $row['message'] : '',
			isset( $row['attachment_id'] ) && is_numeric( $row['attachment_id'] ) ? (int) $row['attachment_id'] : null,
			isset( $row['job_id'] ) && is_scalar( $row['job_id'] ) ? (string) $row['job_id'] : null
		);
	}

	/**
	 * Serialize the row for REST/admin consumers.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'created_at_gmt' => $this->created_at_gmt,
			'level'          => $this->level,
			'code'           => $this->code,
			'message'        => $this->message,
			'attachment_id'  => $this->attachment_id,
			'job_id'         => $this->job_id,
		);
	}

	/**
	 * Normalize an optional queue job ID.
	 *
	 * @param string|null $job_id Raw job ID.
	 * @return string|null
	 */
	private function normalize_job_id( ?string $job_id ): ?string {
		if ( null === $job_id ) {
			return null;
		}

		$job_id = trim( $job_id );

		return '' === $job_id ? null : substr( $job_id, 0, 191 );
	}
}
