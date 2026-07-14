<?php
/**
 * Derivative delete contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Deletes remote derivatives for attachment cleanup and reconciliation.
 */
interface DerivativeDeleteInterface {

	/**
	 * Delete one derivative-path set from remote storage.
	 *
	 * @param DerivativeDeleteRequest $request Delete request.
	 * @return DerivativeDeleteResult
	 */
	public function delete( DerivativeDeleteRequest $request ): DerivativeDeleteResult;
}
