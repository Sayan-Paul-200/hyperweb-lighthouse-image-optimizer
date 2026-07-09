<?php
/**
 * Diagnostic result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Structured result for one diagnostic check.
 */
final class DiagnosticResult {

	/**
	 * Stable check ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Stable reason code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-facing label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * User-safe message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Sanitized developer details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create a diagnostic result.
	 *
	 * @param string       $id Stable check ID.
	 * @param string       $status Status.
	 * @param string       $code Stable reason code.
	 * @param string       $label User-facing label.
	 * @param string       $message User-safe message.
	 * @param array<mixed> $details Developer details.
	 */
	public function __construct(
		string $id,
		string $status,
		string $code,
		string $label,
		string $message,
		array $details = array()
	) {
		$this->id      = $this->normalize_key( $id, 'diagnostic' );
		$this->status  = DiagnosticStatus::normalize( $status );
		$this->code    = $this->normalize_key( $code, 'unknown' );
		$this->label   = '' === trim( $label ) ? 'Diagnostic check' : trim( $label );
		$this->message = trim( $message );
		$this->details = $details;
	}

	/**
	 * Get check ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get label.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
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
	 * Get details.
	 *
	 * @return array<mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Serialize for REST/admin consumers.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'id'      => $this->id,
			'status'  => $this->status,
			'code'    => $this->code,
			'label'   => $this->label,
			'message' => $this->message,
			'details' => $this->details,
		);
	}

	/**
	 * Normalize machine-readable keys.
	 *
	 * @param string $value Value.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function normalize_key( string $value, string $fallback ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? $fallback : substr( $value, 0, 64 );
	}
}
