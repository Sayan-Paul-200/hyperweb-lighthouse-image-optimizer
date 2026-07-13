<?php
/**
 * Elementor critical background preload manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadRegistry;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Emits opt-in modern preload tags for one explicitly selected Elementor hero background target.
 */
final class ElementorCriticalBackgroundPreloadManager implements HookProviderInterface {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Elementor runtime seam.
	 *
	 * @var ElementorRuntimeInterface
	 */
	private $elementor_runtime;

	/**
	 * Background stylesheet runtime seam.
	 *
	 * @var ElementorBackgroundStylesheetRuntimeInterface
	 */
	private $runtime;

	/**
	 * Stored selection seam.
	 *
	 * @var ElementorHeroBackgroundPostMetaStoreInterface
	 */
	private $selections;

	/**
	 * Shared delivery-plan builder.
	 *
	 * @var ElementorBackgroundDeliveryPlanBuilder
	 */
	private $plans;

	/**
	 * Shared request-local preload registry.
	 *
	 * @var ResponsivePreloadRegistry
	 */
	private $registry;

	/**
	 * Create manager.
	 *
	 * @param SettingsRepositoryInterface                   $settings Settings repository.
	 * @param ElementorRuntimeInterface                     $elementor_runtime Elementor runtime.
	 * @param ElementorBackgroundStylesheetRuntimeInterface $runtime Background stylesheet runtime seam.
	 * @param ElementorHeroBackgroundPostMetaStoreInterface $selections Stored target selections.
	 * @param ElementorBackgroundDeliveryPlanBuilder        $plans Shared background delivery-plan builder.
	 * @param ResponsivePreloadRegistry                     $registry Shared request-local preload registry.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		ElementorRuntimeInterface $elementor_runtime,
		ElementorBackgroundStylesheetRuntimeInterface $runtime,
		ElementorHeroBackgroundPostMetaStoreInterface $selections,
		ElementorBackgroundDeliveryPlanBuilder $plans,
		ResponsivePreloadRegistry $registry
	) {
		$this->settings          = $settings;
		$this->elementor_runtime = $elementor_runtime;
		$this->runtime           = $runtime;
		$this->selections        = $selections;
		$this->plans             = $plans;
		$this->registry          = $registry;
	}

	/**
	 * Register runtime hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'wp_head', array( $this, 'emit_preload_tags' ), 1, 0 );
	}

	/**
	 * Emit ready preload tags.
	 *
	 * @return void
	 */
	public function emit_preload_tags(): void {
		$result = $this->build_for_current_request();

		if ( ! $result->is_ready() ) {
			return;
		}

		foreach ( $result->links() as $link ) {
			if ( $this->registry->has( $link ) ) {
				continue;
			}

			$this->registry->record( $link );
			$html = $link->html();
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ElementorBackgroundPreloadLink::html() returns fully escaped final tag markup.
			echo $html;
		}
	}

	/**
	 * Build preload links for the current request.
	 *
	 * @return ElementorBackgroundPreloadResult
	 */
	public function build_for_current_request(): ElementorBackgroundPreloadResult {
		if ( ! $this->settings->critical_background_preload_enabled() ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_DISABLED );
		}

		if ( ! $this->settings->delivery_enabled() || $this->settings->delivery_emergency_disabled() ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_INELIGIBLE_REQUEST );
		}

		if ( ! $this->runtime->is_frontend_request() ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_INELIGIBLE_REQUEST );
		}

		if ( ! $this->elementor_runtime->is_available() || $this->elementor_runtime->is_editor_mode() || $this->elementor_runtime->is_preview_mode() ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_INELIGIBLE_REQUEST );
		}

		$document_id = $this->runtime->current_singular_document_id();

		if ( 1 > $document_id ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_INELIGIBLE_REQUEST );
		}

		$selection = $this->selections->get_selection( $document_id );

		if ( ! $selection instanceof ElementorHeroBackgroundTargetSelection ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_NO_SELECTED_TARGET );
		}

		$plans = $this->plans->build( $document_id, $this->runtime->breakpoint_map() );
		$plan  = $plans->plan( $selection->key() );

		if ( ! $plan instanceof ElementorBackgroundDeliveryPlan ) {
			if ( ! $plans->has_supported_sources() ) {
				return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_NO_SUPPORTED_TARGET_PLAN );
			}

			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_STALE_INVALID_SELECTION );
		}

		if ( $plan->breakpoint_map_missing() ) {
			return ElementorBackgroundPreloadResult::noop( ElementorBackgroundPreloadResult::CODE_BREAKPOINT_MAP_MISSING );
		}

		$links           = array();
		$candidate_count = 0;

		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			$variant = $plan->variant( $device );

			if ( ! $variant instanceof ElementorBackgroundDeliveryVariant ) {
				continue;
			}

			$candidate = $variant->preferred_candidate();

			if ( ! is_array( $candidate ) || empty( $candidate['url'] ) || empty( $candidate['mime'] ) || empty( $candidate['format'] ) ) {
				continue;
			}

			++$candidate_count;

			$link = new ElementorBackgroundPreloadLink(
				$document_id,
				$plan->element_id(),
				$plan->setting_group(),
				$device,
				(string) $candidate['format'],
				(string) $candidate['url'],
				(string) $candidate['mime'],
				$variant->media_query()
			);

			if ( $this->registry->has( $link ) ) {
				continue;
			}

			$links[] = $link;
		}

		if ( array() === $links ) {
			return ElementorBackgroundPreloadResult::noop(
				$candidate_count > 0
					? ElementorBackgroundPreloadResult::CODE_ALREADY_EMITTED
					: ElementorBackgroundPreloadResult::CODE_NO_READY_DERIVATIVE
			);
		}

		return ElementorBackgroundPreloadResult::ready( $links );
	}
}
