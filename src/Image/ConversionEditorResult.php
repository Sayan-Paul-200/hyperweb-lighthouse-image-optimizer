<?php
/**
 * Conversion editor result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries the result of asking an image editor to save a derivative.
 */
final class ConversionEditorResult {

	/**
	 * Whether the editor operation succeeded.
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
	 * Safe message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Safe scalar details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create result.
	 *
	 * @param bool         $success Whether operation succeeded.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 */
	private function __construct( bool $success, string $code, string $message, array $details = array() ) {
		$this->success = $success;
		$this->code    = ConversionResultCode::normalize_for_status(
			$success ? ConversionResult::STATUS_SUCCESS : ConversionResult::STATUS_FAILED,
			$code
		);
		$this->message = '' === trim( $message ) ? 'Conversion editor result.' : trim( $message );
		$this->details = $details;
	}

	/**
	 * Build success result.
	 *
	 * @param array<mixed> $details Details.
	 * @return self
	 */
	public static function success( array $details = array() ): self {
		return new self(
			true,
			ConversionResultCode::OPTIMIZED,
			'The image editor saved the temporary derivative.',
			$details
		);
	}

	/**
	 * Build failure result.
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
	 * Whether operation succeeded.
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
