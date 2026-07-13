<?php
/**
 * Responsive preload result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reports one responsive preload evaluation outcome.
 */
final class ResponsivePreloadResult {

	public const CODE_READY                = 'ready';
	public const CODE_DISABLED             = 'disabled';
	public const CODE_INELIGIBLE_REQUEST   = 'ineligible_request';
	public const CODE_NO_PRELOAD_SELECTION = 'no_preload_selection';
	public const CODE_NO_UNIQUE_MATCH      = 'no_unique_match';
	public const CODE_MISSING_SIZES        = 'missing_sizes';
	public const CODE_NO_SOURCE_SETS       = 'no_source_sets';
	public const CODE_NO_MATCHING_SOURCE   = 'no_matching_source';
	public const CODE_ALREADY_EMITTED      = 'already_emitted';

	/**
	 * Result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Link payload.
	 *
	 * @var ResponsivePreloadLink|null
	 */
	private $link;

	/**
	 * Create result.
	 *
	 * @param string                     $code Result code.
	 * @param ResponsivePreloadLink|null $link Link payload.
	 */
	public function __construct( string $code, ?ResponsivePreloadLink $link = null ) {
		$this->code = strtolower( trim( $code ) );
		$this->link = $link;
	}

	/**
	 * Build a ready result.
	 *
	 * @param ResponsivePreloadLink $link Link payload.
	 * @return self
	 */
	public static function ready( ResponsivePreloadLink $link ): self {
		return new self( self::CODE_READY, $link );
	}

	/**
	 * Build a no-op result.
	 *
	 * @param string $code Result code.
	 * @return self
	 */
	public static function noop( string $code ): self {
		return new self( $code );
	}

	/**
	 * Whether a link is ready to emit.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return self::CODE_READY === $this->code && $this->link instanceof ResponsivePreloadLink;
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
	 * Get link payload.
	 *
	 * @return ResponsivePreloadLink|null
	 */
	public function link(): ?ResponsivePreloadLink {
		return $this->link;
	}

	/**
	 * Serialize result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code' => $this->code,
			'link' => $this->link instanceof ResponsivePreloadLink ? $this->link->to_array() : null,
		);
	}
}
