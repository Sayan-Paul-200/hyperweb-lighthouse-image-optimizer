<?php
/**
 * Cache invalidation dispatcher contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Dispatches cache invalidation requests for derivative state changes.
 */
interface CacheInvalidationDispatcherInterface {

	/**
	 * Dispatch one invalidation request.
	 *
	 * @param CacheInvalidationRequest $request Request.
	 * @return void
	 */
	public function dispatch( CacheInvalidationRequest $request ): void;
}
