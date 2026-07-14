<?php
/**
 * Multisite site-context runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Multisite;

/**
 * Provides current-site and network-activation facts for multisite-safe behavior.
 */
interface SiteContextRuntimeInterface {

	/**
	 * Get the current site ID.
	 *
	 * @return int
	 */
	public function current_site_id(): int;

	/**
	 * Whether multisite is active.
	 *
	 * @return bool
	 */
	public function is_multisite(): bool;

	/**
	 * Whether the plugin is network-active.
	 *
	 * @param string $plugin_basename Plugin basename.
	 * @return bool
	 */
	public function plugin_network_active( string $plugin_basename ): bool;

	/**
	 * Switch to one site context.
	 *
	 * @param int $site_id Site ID.
	 * @return void
	 */
	public function switch_to_site( int $site_id ): void;

	/**
	 * Restore the previous site context.
	 *
	 * @return void
	 */
	public function restore_site(): void;
}
