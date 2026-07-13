<?php
/**
 * Elementor document-data read result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries normalized Elementor document elements plus read state.
 */
final class ElementorDocumentData {

	public const STATE_VALID   = 'valid';
	public const STATE_MISSING = 'missing';
	public const STATE_INVALID = 'invalid';

	/**
	 * Read state.
	 *
	 * @var string
	 */
	private $state;

	/**
	 * Normalized Elementor elements.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $elements;

	/**
	 * Create the result.
	 *
	 * @param string                         $state Read state.
	 * @param array<int,array<string,mixed>> $elements Normalized elements.
	 */
	public function __construct( string $state, array $elements = array() ) {
		$this->state    = $this->normalize_state( $state );
		$this->elements = $elements;
	}

	/**
	 * Build a valid document-data result.
	 *
	 * @param array<int,array<string,mixed>> $elements Normalized elements.
	 * @return self
	 */
	public static function valid( array $elements ): self {
		return new self( self::STATE_VALID, $elements );
	}

	/**
	 * Build a missing-data result.
	 *
	 * @return self
	 */
	public static function missing(): self {
		return new self( self::STATE_MISSING, array() );
	}

	/**
	 * Build an invalid-data result.
	 *
	 * @return self
	 */
	public static function invalid(): self {
		return new self( self::STATE_INVALID, array() );
	}

	/**
	 * Get the read state.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * Whether the stored document data is valid.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return self::STATE_VALID === $this->state;
	}

	/**
	 * Whether the document data is missing.
	 *
	 * @return bool
	 */
	public function is_missing(): bool {
		return self::STATE_MISSING === $this->state;
	}

	/**
	 * Whether the document data is invalid.
	 *
	 * @return bool
	 */
	public function is_invalid(): bool {
		return self::STATE_INVALID === $this->state;
	}

	/**
	 * Get normalized elements.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function elements(): array {
		return $this->elements;
	}

	/**
	 * Normalize the read state.
	 *
	 * @param string $state Raw state.
	 * @return string
	 */
	private function normalize_state( string $state ): string {
		$state = strtolower( trim( $state ) );

		if ( ! in_array( $state, array( self::STATE_VALID, self::STATE_MISSING, self::STATE_INVALID ), true ) ) {
			return self::STATE_INVALID;
		}

		return $state;
	}
}
