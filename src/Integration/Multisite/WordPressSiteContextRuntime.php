<?php
/**
 * WordPress-backed multisite site-context runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Multisite;

/**
 * Uses conservative WordPress multisite APIs for current-site and network-activation facts.
 */
final class WordPressSiteContextRuntime implements SiteContextRuntimeInterface {

	/**
	 * Get the current site ID.
	 *
	 * @return int
	 */
	public function current_site_id(): int {
		if ( function_exists( 'get_current_blog_id' ) ) {
			return max( 0, (int) \get_current_blog_id() );
		}

		return 0;
	}

	/**
	 * Whether multisite is active.
	 *
	 * @return bool
	 */
	public function is_multisite(): bool {
		return function_exists( 'is_multisite' ) && (bool) \is_multisite();
	}

	/**
	 * Whether the plugin is network-active.
	 *
	 * @param string $plugin_basename Plugin basename.
	 * @return bool
	 */
	public function plugin_network_active( string $plugin_basename ): bool {
		$plugin_basename = trim( $plugin_basename );

		if ( '' === $plugin_basename || ! $this->is_multisite() ) {
			return false;
		}

		if ( function_exists( 'is_plugin_active_for_network' ) ) {
			return (bool) \is_plugin_active_for_network( $plugin_basename );
		}

		if ( ! function_exists( 'get_site_option' ) ) {
			return false;
		}

		$plugins = \get_site_option( 'active_sitewide_plugins', array() );

		return is_array( $plugins ) && array_key_exists( $plugin_basename, $plugins );
	}

	/**
	 * Switch to one site context.
	 *
	 * @param int $site_id Site ID.
	 * @return void
	 */
	public function switch_to_site( int $site_id ): void {
		if ( $site_id > 0 && function_exists( 'switch_to_blog' ) ) {
			\switch_to_blog( $site_id );
		}
	}

	/**
	 * Restore the previous site context.
	 *
	 * @return void
	 */
	public function restore_site(): void {
		if ( function_exists( 'restore_current_blog' ) ) {
			\restore_current_blog();
		}
	}
}
