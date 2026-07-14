<?php
/**
 * Delivery hook policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Defines stable generic delivery hook names and normalized filter contexts.
 */
final class DeliveryHookPolicy {

	public const FILTER_UPLOADS_BASE_URL = 'hwlio_delivery_uploads_base_url';
	public const FILTER_DERIVATIVE_URL   = 'hwlio_delivery_derivative_url';

	/**
	 * Build the normalized uploads-base filter context.
	 *
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @param string               $base_url Current uploads base URL.
	 * @return array<string,mixed>
	 */
	public static function uploads_base_url_context( DerivativeUrlRequest $request, string $base_url ): array {
		return array(
			'relative_path' => $request->relative_path(),
			'attachment_id' => $request->attachment_id(),
			'size_name'     => $request->size_name(),
			'format'        => $request->format(),
			'request'       => $request,
			'base_url'      => $base_url,
		);
	}

	/**
	 * Build the normalized derivative-URL filter context.
	 *
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @param string               $url Current resolved derivative URL.
	 * @return array<string,mixed>
	 */
	public static function derivative_url_context( DerivativeUrlRequest $request, string $url ): array {
		return array(
			'relative_path' => $request->relative_path(),
			'attachment_id' => $request->attachment_id(),
			'size_name'     => $request->size_name(),
			'format'        => $request->format(),
			'request'       => $request,
			'url'           => $url,
		);
	}
}
