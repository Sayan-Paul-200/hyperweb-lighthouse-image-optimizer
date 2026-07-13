<?php
/**
 * WooCommerce integration provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers the narrow WooCommerce primary-image integration.
 */
final class WooCommerceIntegration implements HookProviderInterface {

	/**
	 * Runtime seam.
	 *
	 * @var WooCommerceRuntimeInterface
	 */
	private $runtime;

	/**
	 * Primary-image matcher.
	 *
	 * @var WooCommercePrimaryImageMatcher
	 */
	private $matcher;

	/**
	 * Create provider.
	 *
	 * @param WooCommerceRuntimeInterface    $runtime Runtime seam.
	 * @param WooCommercePrimaryImageMatcher $matcher Primary-image matcher.
	 */
	public function __construct( WooCommerceRuntimeInterface $runtime, WooCommercePrimaryImageMatcher $matcher ) {
		$this->runtime = $runtime;
		$this->matcher = $matcher;
	}

	/**
	 * Register internal WooCommerce integration filters.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( 'hwlio_critical_image_candidates', array( $this, 'filter_critical_image_candidates' ), 10, 2 );
		$hooks->add_filter( 'hwlio_loading_image_role', array( $this, 'filter_loading_image_role' ), 10, 2 );
		$hooks->add_filter( 'hwlio_markup_is_eligible', array( $this, 'filter_markup_eligibility' ), 10, 4 );
	}

	/**
	 * Register the current product primary image as critical on single-product requests.
	 *
	 * @param array<string,mixed> $payload Candidate payload.
	 * @param array<string,mixed> $context Request context.
	 * @return array<string,mixed>
	 */
	public function filter_critical_image_candidates( array $payload, array $context ): array {
		unset( $context );

		if ( ! $this->runtime->is_available() || ! $this->matcher->has_current_primary_image() ) {
			return $payload;
		}

		$primary_attachment_id = $this->matcher->current_primary_image_id();

		if ( $primary_attachment_id < 1 ) {
			return $payload;
		}

		$critical_attachment_ids = array();

		if ( isset( $payload['critical_attachment_ids'] ) && is_array( $payload['critical_attachment_ids'] ) ) {
			$critical_attachment_ids = $payload['critical_attachment_ids'];
		}

		$critical_attachment_ids[] = $primary_attachment_id;

		$payload['primary_attachment_id']   = $primary_attachment_id;
		$payload['critical_attachment_ids'] = array_values( array_unique( array_map( 'intval', $critical_attachment_ids ) ) );
		$payload['preload_attachment_id']   = null;

		return $payload;
	}

	/**
	 * Refine the current loading-image role for WooCommerce image fragments.
	 *
	 * @param string              $role Current role.
	 * @param array<string,mixed> $context Markup context.
	 * @return string
	 */
	public function filter_loading_image_role( string $role, array $context ): string {
		$attachment_id = isset( $context['attachment_id'] ) ? max( 0, (int) $context['attachment_id'] ) : 0;
		$match         = $this->matcher->match( $attachment_id, $context );

		if ( WooCommercePrimaryImageMatcher::MATCH_PRIMARY === $match ) {
			return 'primary';
		}

		if (
			WooCommercePrimaryImageMatcher::MATCH_GALLERY_SECONDARY === $match
			|| WooCommercePrimaryImageMatcher::MATCH_COMMERCE_THUMBNAIL === $match
			|| WooCommercePrimaryImageMatcher::MATCH_VARIATION_OR_UNCERTAIN === $match
		) {
			return 'none';
		}

		return $role;
	}

	/**
	 * Restrict 9.2 delivery to the confirmed primary product image only.
	 *
	 * @param bool                $eligible Current eligibility.
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $html Original HTML.
	 * @param array<string,mixed> $context Delivery context.
	 * @return bool
	 */
	public function filter_markup_eligibility( bool $eligible, int $attachment_id, string $html, array $context ): bool {
		unset( $context );

		if ( ! $eligible ) {
			return false;
		}

		$match = $this->matcher->match(
			$attachment_id,
			array(
				'html' => $html,
			)
		);

		if ( WooCommercePrimaryImageMatcher::MATCH_PRIMARY === $match ) {
			return true;
		}

		if ( WooCommercePrimaryImageMatcher::MATCH_GALLERY_SECONDARY === $match ) {
			return true;
		}

		if (
			WooCommercePrimaryImageMatcher::MATCH_COMMERCE_THUMBNAIL === $match
			|| WooCommercePrimaryImageMatcher::MATCH_VARIATION_OR_UNCERTAIN === $match
		) {
			return false;
		}

		return $eligible;
	}
}
