<?php
/**
 * Elementor background preload result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Reports one critical background preload evaluation outcome.
 */
final class ElementorBackgroundPreloadResult {

	public const CODE_DISABLED                 = 'disabled';
	public const CODE_INELIGIBLE_REQUEST       = 'ineligible_request';
	public const CODE_NO_SELECTED_TARGET       = 'no_selected_target';
	public const CODE_STALE_INVALID_SELECTION  = 'stale_invalid_selection';
	public const CODE_NO_SUPPORTED_TARGET_PLAN = 'no_supported_target_plan';
	public const CODE_BREAKPOINT_MAP_MISSING   = 'breakpoint_map_missing';
	public const CODE_NO_READY_DERIVATIVE      = 'no_ready_derivative';
	public const CODE_ALREADY_EMITTED          = 'already_emitted';
	public const CODE_READY                    = 'ready';

	/**
	 * Primary result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Safe preload links.
	 *
	 * @var ElementorBackgroundPreloadLink[]
	 */
	private $links;

	/**
	 * Create result.
	 *
	 * @param string                           $code Result code.
	 * @param ElementorBackgroundPreloadLink[] $links Safe preload links.
	 */
	public function __construct( string $code, array $links = array() ) {
		$this->code  = $this->normalize_code( $code );
		$this->links = $this->filter_links( $links );
	}

	/**
	 * Build a ready result.
	 *
	 * @param ElementorBackgroundPreloadLink[] $links Ready links.
	 * @return self
	 */
	public static function ready( array $links ): self {
		return new self( self::CODE_READY, $links );
	}

	/**
	 * Build a noop result.
	 *
	 * @param string $code Result code.
	 * @return self
	 */
	public static function noop( string $code ): self {
		return new self( $code );
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
	 * Whether ready links exist.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return self::CODE_READY === $this->code && array() !== $this->links;
	}

	/**
	 * Get ready links.
	 *
	 * @return ElementorBackgroundPreloadLink[]
	 */
	public function links(): array {
		return $this->links;
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code'  => $this->code,
			'links' => array_map(
				static function ( ElementorBackgroundPreloadLink $link ): array {
					return $link->to_array();
				},
				$this->links
			),
		);
	}

	/**
	 * Normalize result code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$allowed = array(
			self::CODE_DISABLED,
			self::CODE_INELIGIBLE_REQUEST,
			self::CODE_NO_SELECTED_TARGET,
			self::CODE_STALE_INVALID_SELECTION,
			self::CODE_NO_SUPPORTED_TARGET_PLAN,
			self::CODE_BREAKPOINT_MAP_MISSING,
			self::CODE_NO_READY_DERIVATIVE,
			self::CODE_ALREADY_EMITTED,
			self::CODE_READY,
		);

		return in_array( $code, $allowed, true ) ? $code : self::CODE_NO_SUPPORTED_TARGET_PLAN;
	}

	/**
	 * Filter ready links.
	 *
	 * @param array<int,mixed> $links Raw links.
	 * @return ElementorBackgroundPreloadLink[]
	 */
	private function filter_links( array $links ): array {
		return array_values(
			array_filter(
				$links,
				static function ( $link ): bool {
					return $link instanceof ElementorBackgroundPreloadLink;
				}
			)
		);
	}
}
