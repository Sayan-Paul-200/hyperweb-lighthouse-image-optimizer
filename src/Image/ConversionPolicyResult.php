<?php
/**
 * Conversion policy result value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries the outcome of a conversion policy decision.
 *
 * Tells the caller whether conversion should proceed and provides a stable
 * machine-readable code and a human-readable reason.
 */
final class ConversionPolicyResult {

	/**
	 * Whether conversion should proceed.
	 *
	 * @var bool
	 */
	private $should_convert;

	/**
	 * Stable machine-readable code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Human-readable reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Create the result.
	 *
	 * @param bool   $should_convert Whether to convert.
	 * @param string $code Stable code.
	 * @param string $reason Human-readable reason.
	 */
	private function __construct( bool $should_convert, string $code, string $reason ) {
		$this->should_convert = $should_convert;
		$this->code           = $this->normalize_code( $code );
		$this->reason         = '' === trim( $reason ) ? 'Conversion policy result.' : trim( $reason );
	}

	/**
	 * Build an eligible (should convert) result.
	 *
	 * @param string $reason Human-readable reason.
	 * @return self
	 */
	public static function eligible( string $reason = 'The source is eligible for conversion.' ): self {
		return new self( true, 'eligible', $reason );
	}

	/**
	 * Build a skipped (should not convert) result.
	 *
	 * @param string $code Stable skip code from ConversionResultCode.
	 * @param string $reason Human-readable reason.
	 * @return self
	 */
	public static function skip( string $code, string $reason ): self {
		return new self( false, $code, $reason );
	}

	/**
	 * Whether conversion should proceed.
	 *
	 * @return bool
	 */
	public function should_convert(): bool {
		return $this->should_convert;
	}

	/**
	 * Whether the decision is to skip.
	 *
	 * @return bool
	 */
	public function is_skipped(): bool {
		return ! $this->should_convert;
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
	 * Get the human-readable reason.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'should_convert' => $this->should_convert,
			'code'           => $this->code,
			'reason'         => $this->reason,
		);
	}

	/**
	 * Normalize a code into machine-readable shape.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return '' === $code ? 'unknown' : substr( $code, 0, 64 );
	}
}
