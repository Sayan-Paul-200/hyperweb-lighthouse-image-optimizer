<?php
/**
 * Elementor background breakpoint map.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries normalized Elementor breakpoint media queries.
 */
final class ElementorBackgroundBreakpointMap {

	/**
	 * Desktop media query.
	 *
	 * @var string
	 */
	private $desktop_query;

	/**
	 * Tablet media query.
	 *
	 * @var string
	 */
	private $tablet_query;

	/**
	 * Mobile media query.
	 *
	 * @var string
	 */
	private $mobile_query;

	/**
	 * Create one breakpoint map.
	 *
	 * @param string $desktop_query Desktop media query.
	 * @param string $tablet_query Tablet media query.
	 * @param string $mobile_query Mobile media query.
	 */
	public function __construct( string $desktop_query, string $tablet_query, string $mobile_query ) {
		$this->desktop_query = trim( $desktop_query );
		$this->tablet_query  = trim( $tablet_query );
		$this->mobile_query  = trim( $mobile_query );
	}

	/**
	 * Build a normalized map from Elementor-style max widths.
	 *
	 * @param int $mobile_max Mobile max width.
	 * @param int $tablet_max Tablet max width.
	 * @return self|null
	 */
	public static function from_max_widths( int $mobile_max, int $tablet_max ): ?self {
		$mobile_max = max( 0, $mobile_max );
		$tablet_max = max( 0, $tablet_max );

		if ( 1 > $mobile_max || $tablet_max <= $mobile_max ) {
			return null;
		}

		return new self(
			sprintf( '(min-width: %dpx)', $tablet_max + 1 ),
			sprintf( '(min-width: %dpx) and (max-width: %dpx)', $mobile_max + 1, $tablet_max ),
			sprintf( '(max-width: %dpx)', $mobile_max )
		);
	}

	/**
	 * Get desktop media query.
	 *
	 * @return string
	 */
	public function desktop_query(): string {
		return $this->desktop_query;
	}

	/**
	 * Get tablet media query.
	 *
	 * @return string
	 */
	public function tablet_query(): string {
		return $this->tablet_query;
	}

	/**
	 * Get mobile media query.
	 *
	 * @return string
	 */
	public function mobile_query(): string {
		return $this->mobile_query;
	}

	/**
	 * Whether the map is complete enough for responsive output.
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return '' !== $this->desktop_query && '' !== $this->tablet_query && '' !== $this->mobile_query;
	}

	/**
	 * Serialize the map.
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'desktop' => $this->desktop_query,
			'tablet'  => $this->tablet_query,
			'mobile'  => $this->mobile_query,
		);
	}
}
