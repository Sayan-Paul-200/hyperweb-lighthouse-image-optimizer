<?php
/**
 * Derivative push contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Publishes newly generated derivatives to remote storage before metadata treats them as ready.
 */
interface DerivativePushInterface {

	/**
	 * Publish one conversion-result set to remote storage.
	 *
	 * @param DerivativePushRequest $request Push request.
	 * @return DerivativePushResult
	 */
	public function publish( DerivativePushRequest $request ): DerivativePushResult;
}
