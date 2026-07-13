<?php
/**
 * Intrinsic dimension repair result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reports the outcome of one intrinsic-dimension repair attempt.
 */
final class IntrinsicDimensionRepairResult {

	/**
	 * Whether markup was repaired.
	 *
	 * @var bool
	 */
	private $repaired;

	/**
	 * Final HTML.
	 *
	 * @var string
	 */
	private $html;

	/**
	 * Result codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Create result.
	 *
	 * @param bool     $repaired Whether markup was repaired.
	 * @param string   $html Final HTML.
	 * @param string[] $codes Result codes.
	 */
	public function __construct( bool $repaired, string $html, array $codes = array() ) {
		$this->repaired = $repaired;
		$this->html     = $html;
		$this->codes    = $this->normalize_codes( $codes );
	}

	/**
	 * Build a repaired result.
	 *
	 * @param string   $html Final HTML.
	 * @param string[] $codes Result codes.
	 * @return self
	 */
	public static function repaired( string $html, array $codes = array() ): self {
		array_unshift( $codes, PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_REPAIRED );

		return new self( true, $html, $codes );
	}

	/**
	 * Build an unchanged result.
	 *
	 * @param string   $html Final HTML.
	 * @param string[] $codes Result codes.
	 * @return self
	 */
	public static function unchanged( string $html, array $codes = array() ): self {
		return new self( false, $html, $codes );
	}

	/**
	 * Whether markup was repaired.
	 *
	 * @return bool
	 */
	public function is_repaired(): bool {
		return $this->repaired;
	}

	/**
	 * Get final HTML.
	 *
	 * @return string
	 */
	public function html(): string {
		return $this->html;
	}

	/**
	 * Get result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Whether one code exists.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( strtolower( trim( $code ) ), $this->codes, true );
	}

	/**
	 * Normalize result codes.
	 *
	 * @param string[] $codes Codes.
	 * @return string[]
	 */
	private function normalize_codes( array $codes ): array {
		$normalized = array();

		foreach ( $codes as $code ) {
			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = strtolower( trim( (string) $code ) );
			$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
			$code = trim( $code, '_' );

			if ( '' !== $code && ! in_array( $code, $normalized, true ) ) {
				$normalized[] = $code;
			}
		}

		return $normalized;
	}
}
