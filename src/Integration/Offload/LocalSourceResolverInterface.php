<?php
/**
 * Local source resolver contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Resolves a usable local file for an authoritative offloaded attachment source.
 */
interface LocalSourceResolverInterface {

	/**
	 * Resolve one authoritative source to a local file.
	 *
	 * @param LocalSourceResolutionRequest $request Resolution request.
	 * @return LocalSourceResolutionResult
	 */
	public function resolve( LocalSourceResolutionRequest $request ): LocalSourceResolutionResult;
}
