<?php
/**
 * Elementor background delivery plan.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one selector-scoped background delivery plan.
 */
final class ElementorBackgroundDeliveryPlan {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $document_id;

	/**
	 * Elementor element ID.
	 *
	 * @var string
	 */
	private $element_id;

	/**
	 * Setting group.
	 *
	 * @var string
	 */
	private $setting_group;

	/**
	 * Canonical selector.
	 *
	 * @var string
	 */
	private $selector;

	/**
	 * Device variants.
	 *
	 * @var array<string,ElementorBackgroundDeliveryVariant>
	 */
	private $variants;

	/**
	 * Whether explicit smaller responsive variants exist.
	 *
	 * @var bool
	 */
	private $responsive;

	/**
	 * Whether explicit responsive variants exist but no safe breakpoint map was available.
	 *
	 * @var bool
	 */
	private $breakpoint_map_missing;

	/**
	 * Create plan.
	 *
	 * @param int                                              $document_id Document ID.
	 * @param string                                           $element_id Elementor element ID.
	 * @param string                                           $setting_group Setting group.
	 * @param string                                           $selector Canonical selector.
	 * @param array<string,ElementorBackgroundDeliveryVariant> $variants Device variants.
	 * @param bool                                             $responsive Whether the plan is responsive.
	 * @param bool                                             $breakpoint_map_missing Whether responsive output was blocked by a missing map.
	 */
	public function __construct(
		int $document_id,
		string $element_id,
		string $setting_group,
		string $selector,
		array $variants,
		bool $responsive = false,
		bool $breakpoint_map_missing = false
	) {
		$this->document_id            = max( 0, $document_id );
		$this->element_id             = trim( $element_id );
		$this->setting_group          = 'background_overlay' === trim( $setting_group ) ? 'background_overlay' : 'background';
		$this->selector               = trim( $selector );
		$this->variants               = $this->filter_variants( $variants );
		$this->responsive             = $responsive;
		$this->breakpoint_map_missing = $breakpoint_map_missing;
	}

	/**
	 * Get plan key.
	 *
	 * @return string
	 */
	public function key(): string {
		return $this->element_id . '|' . $this->setting_group;
	}

	/**
	 * Get selector.
	 *
	 * @return string
	 */
	public function selector(): string {
		return $this->selector;
	}

	/**
	 * Get element ID.
	 *
	 * @return string
	 */
	public function element_id(): string {
		return $this->element_id;
	}

	/**
	 * Get setting group.
	 *
	 * @return string
	 */
	public function setting_group(): string {
		return $this->setting_group;
	}

	/**
	 * Get device variants.
	 *
	 * @return array<string,ElementorBackgroundDeliveryVariant>
	 */
	public function variants(): array {
		return $this->variants;
	}

	/**
	 * Get one device variant.
	 *
	 * @param string $device Device scope.
	 * @return ElementorBackgroundDeliveryVariant|null
	 */
	public function variant( string $device ): ?ElementorBackgroundDeliveryVariant {
		$device = trim( $device );

		return isset( $this->variants[ $device ] ) ? $this->variants[ $device ] : null;
	}

	/**
	 * Whether the plan has any safe variants.
	 *
	 * @return bool
	 */
	public function has_variants(): bool {
		return array() !== $this->variants;
	}

	/**
	 * Whether explicit smaller responsive variants exist.
	 *
	 * @return bool
	 */
	public function is_responsive(): bool {
		return $this->responsive;
	}

	/**
	 * Whether responsive output was blocked by a missing breakpoint map.
	 *
	 * @return bool
	 */
	public function breakpoint_map_missing(): bool {
		return $this->breakpoint_map_missing;
	}

	/**
	 * Serialize the plan.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'document_id'            => $this->document_id,
			'element_id'             => $this->element_id,
			'setting_group'          => $this->setting_group,
			'selector'               => $this->selector,
			'responsive'             => $this->responsive,
			'breakpoint_map_missing' => $this->breakpoint_map_missing,
			'variants'               => array_map(
				static function ( ElementorBackgroundDeliveryVariant $variant ): array {
					return $variant->to_array();
				},
				$this->variants
			),
		);
	}

	/**
	 * Filter valid variants keyed by device.
	 *
	 * @param array<string,mixed> $variants Raw variants.
	 * @return array<string,ElementorBackgroundDeliveryVariant>
	 */
	private function filter_variants( array $variants ): array {
		$filtered = array();

		foreach ( $variants as $device => $variant ) {
			if ( $variant instanceof ElementorBackgroundDeliveryVariant ) {
				$filtered[ (string) $device ] = $variant;
			}
		}

		return $filtered;
	}
}
