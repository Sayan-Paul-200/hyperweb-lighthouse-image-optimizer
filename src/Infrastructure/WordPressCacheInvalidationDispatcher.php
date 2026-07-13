<?php
/**
 * WordPress cache invalidation dispatcher.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Emits plugin cache invalidation requests through WordPress actions.
 */
final class WordPressCacheInvalidationDispatcher implements CacheInvalidationDispatcherInterface {

	/**
	 * Dispatch one invalidation request.
	 *
	 * @param CacheInvalidationRequest $request Request.
	 * @return void
	 */
	public function dispatch( CacheInvalidationRequest $request ): void {
		if ( 0 >= $request->attachment_id() || array() === $request->relative_paths() || ! function_exists( 'do_action' ) ) {
			return;
		}

		\do_action(
			LifecyclePolicy::HOOK_CACHE_INVALIDATION_REQUESTED,
			$request->attachment_id(),
			$request->to_array()
		);
	}
}
