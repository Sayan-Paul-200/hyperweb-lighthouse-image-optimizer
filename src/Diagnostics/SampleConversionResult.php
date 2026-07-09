<?php
/**
 * Sample conversion result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Result returned by the sample conversion probe.
 */
final class SampleConversionResult {

	/**
	 * Whether conversion succeeded.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Stable code.
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
	 * Details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create result.
	 *
	 * @param bool         $success Whether conversion succeeded.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 */
	public function __construct( bool $success, string $code, string $message, array $details = array() ) {
		$this->success = $success;
		$this->code    = '' === trim( $code ) ? 'unknown' : strtolower( trim( $code ) );
		$this->message = trim( $message );
		$this->details = $details;
	}

	/**
	 * Build a success result.
	 *
	 * @return self
	 */
	public static function success(): self {
		return new self( true, 'sample_conversion_succeeded', 'Sample conversion succeeded.' );
	}

	/**
	 * Build a failure result.
	 *
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 * @return self
	 */
	public static function failure( string $code, string $message, array $details = array() ): self {
		return new self( false, $code, $message, $details );
	}

	/**
	 * Whether conversion succeeded.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
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
}
