<?php
/**
 * Elementor background delivery plan result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries all safe delivery plans for one Elementor document.
 */
final class ElementorBackgroundDeliveryPlanResult {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $document_id;

	/**
	 * Whether discovery found any supported structured background sources.
	 *
	 * @var bool
	 */
	private $has_supported_sources;

	/**
	 * Whether any responsive plan lacked a reliable breakpoint map.
	 *
	 * @var bool
	 */
	private $breakpoint_map_missing;

	/**
	 * Safe plans keyed by element/group identity.
	 *
	 * @var array<string,ElementorBackgroundDeliveryPlan>
	 */
	private $plans;

	/**
	 * Create result.
	 *
	 * @param int                                           $document_id Document ID.
	 * @param bool                                          $has_supported_sources Whether supported sources existed.
	 * @param bool                                          $breakpoint_map_missing Whether any responsive plan lacked a safe map.
	 * @param array<string,ElementorBackgroundDeliveryPlan> $plans Safe plans.
	 */
	public function __construct( int $document_id, bool $has_supported_sources, bool $breakpoint_map_missing, array $plans ) {
		$this->document_id            = max( 0, $document_id );
		$this->has_supported_sources  = $has_supported_sources;
		$this->breakpoint_map_missing = $breakpoint_map_missing;
		$this->plans                  = $this->filter_plans( $plans );
	}

	/**
	 * Whether any supported structured background sources were discovered.
	 *
	 * @return bool
	 */
	public function has_supported_sources(): bool {
		return $this->has_supported_sources;
	}

	/**
	 * Whether any responsive target lacked a reliable breakpoint map.
	 *
	 * @return bool
	 */
	public function breakpoint_map_missing(): bool {
		return $this->breakpoint_map_missing;
	}

	/**
	 * Whether any safe plans exist.
	 *
	 * @return bool
	 */
	public function has_plans(): bool {
		return array() !== $this->plans;
	}

	/**
	 * Get all safe plans.
	 *
	 * @return array<string,ElementorBackgroundDeliveryPlan>
	 */
	public function plans(): array {
		return $this->plans;
	}

	/**
	 * Get one safe plan by key.
	 *
	 * @param string $key Plan key.
	 * @return ElementorBackgroundDeliveryPlan|null
	 */
	public function plan( string $key ): ?ElementorBackgroundDeliveryPlan {
		$key = trim( $key );

		return isset( $this->plans[ $key ] ) ? $this->plans[ $key ] : null;
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'document_id'            => $this->document_id,
			'has_supported_sources'  => $this->has_supported_sources,
			'breakpoint_map_missing' => $this->breakpoint_map_missing,
			'plans'                  => array_map(
				static function ( ElementorBackgroundDeliveryPlan $plan ): array {
					return $plan->to_array();
				},
				$this->plans
			),
		);
	}

	/**
	 * Filter valid plans.
	 *
	 * @param array<string,mixed> $plans Raw plans.
	 * @return array<string,ElementorBackgroundDeliveryPlan>
	 */
	private function filter_plans( array $plans ): array {
		$filtered = array();

		foreach ( $plans as $key => $plan ) {
			if ( $plan instanceof ElementorBackgroundDeliveryPlan ) {
				$filtered[ (string) $key ] = $plan;
			}
		}

		return $filtered;
	}
}
