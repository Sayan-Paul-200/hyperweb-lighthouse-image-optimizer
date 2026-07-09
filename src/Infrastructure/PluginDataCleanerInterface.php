<?php
/**
 * Plugin data cleanup contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Cleans plugin-owned persistent data during explicit uninstall cleanup.
 */
interface PluginDataCleanerInterface {

	/**
	 * Delete plugin-owned data.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult;
}
