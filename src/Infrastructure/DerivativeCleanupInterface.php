<?php
/**
 * Derivative cleanup contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Cleans plugin-owned derivative files.
 */
interface DerivativeCleanupInterface {

	/**
	 * Delete eligible derivative files.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult;
}
