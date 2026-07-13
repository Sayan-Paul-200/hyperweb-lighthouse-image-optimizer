<?php
/**
 * Elementor integration provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers narrow Elementor eligibility guards.
 */
final class ElementorIntegration implements HookProviderInterface {

	/**
	 * Widget matcher.
	 *
	 * @var ElementorWidgetMatcher
	 */
	private $matcher;

	/**
	 * Create provider.
	 *
	 * @param ElementorWidgetMatcher $matcher Widget matcher.
	 */
	public function __construct( ElementorWidgetMatcher $matcher ) {
		$this->matcher = $matcher;
	}

	/**
	 * Register the narrow Elementor eligibility filter.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( 'hwlio_markup_is_eligible', array( $this, 'filter_markup_eligibility' ), 10, 4 );
	}

	/**
	 * Allow only safe attachment-backed frontend widget fragments to continue through delivery.
	 *
	 * @param bool                $eligible Current eligibility.
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $html Original markup.
	 * @param array<string,mixed> $context Delivery context.
	 * @return bool
	 */
	public function filter_markup_eligibility( bool $eligible, int $attachment_id, string $html, array $context ): bool {
		unset( $attachment_id, $context );

		if ( ! $eligible ) {
			return false;
		}

		$match = $this->matcher->match( $html );

		if ( ElementorWidgetMatcher::MATCH_EXCLUDED_GALLERY_OR_CAROUSEL === $match || ElementorWidgetMatcher::MATCH_EDITOR_OR_PREVIEW === $match ) {
			return false;
		}

		return $eligible;
	}
}
