<?php
/**
 * Conflict runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Conflict;

/**
 * Provides current-site plugin activation facts for compatibility detection.
 */
interface ConflictRuntimeInterface {

	/**
	 * Get current-site active plugin basenames.
	 *
	 * @return string[]
	 */
	public function active_plugin_basenames(): array;

	/**
	 * Get current-site network-active plugin basenames.
	 *
	 * @return string[]
	 */
	public function network_active_plugin_basenames(): array;

	/**
	 * Whether the current runtime is multisite.
	 *
	 * @return bool
	 */
	public function is_multisite(): bool;
}
