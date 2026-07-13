<?php
/**
 * Fake cache invalidation dispatcher.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationDispatcherInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;

/**
 * Captures cache invalidation requests in tests.
 */
final class FakeCacheInvalidationDispatcher implements CacheInvalidationDispatcherInterface {

	/**
	 * Captured payloads.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $requests = array();

	/**
	 * Dispatch one invalidation request.
	 *
	 * @param CacheInvalidationRequest $request Request.
	 * @return void
	 */
	public function dispatch( CacheInvalidationRequest $request ): void {
		$this->requests[] = $request->to_array();
	}
}
