<?php
/**
 * Uploads runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Wraps current uploads runtime facts and delivery URL filtering.
 */
interface UploadsRuntimeInterface {

	/**
	 * Read the current uploads base URL.
	 *
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string|null
	 */
	public function uploads_base_url( DerivativeUrlRequest $request ): ?string;

	/**
	 * Read the current uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string;

	/**
	 * Allow runtime filters to rewrite a resolved derivative URL.
	 *
	 * @param string               $url Resolved derivative URL.
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string
	 */
	public function filter_derivative_url( string $url, DerivativeUrlRequest $request ): string;
}
