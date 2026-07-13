<?php
/**
 * WordPress conflict runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Conflict;

/**
 * Reads current-site plugin activation state without mutating third-party plugins.
 */
final class WordPressConflictRuntime implements ConflictRuntimeInterface {

	/**
	 * Optional active-plugin provider.
	 *
	 * @var callable|null
	 */
	private $active_provider;

	/**
	 * Optional network-active provider.
	 *
	 * @var callable|null
	 */
	private $network_provider;

	/**
	 * Optional multisite detector.
	 *
	 * @var callable|null
	 */
	private $multisite_provider;

	/**
	 * Create runtime.
	 *
	 * @param callable|null $active_provider Optional active-plugin provider.
	 * @param callable|null $network_provider Optional network-active provider.
	 * @param callable|null $multisite_provider Optional multisite detector.
	 */
	public function __construct(
		?callable $active_provider = null,
		?callable $network_provider = null,
		?callable $multisite_provider = null
	) {
		$this->active_provider    = $active_provider;
		$this->network_provider   = $network_provider;
		$this->multisite_provider = $multisite_provider;
	}

	/**
	 * Get current-site active plugin basenames.
	 *
	 * @return string[]
	 */
	public function active_plugin_basenames(): array {
		if ( null !== $this->active_provider ) {
			return $this->normalize_plugin_list( call_user_func( $this->active_provider ) );
		}

		if ( ! function_exists( 'get_option' ) ) {
			return array();
		}

		return $this->normalize_plugin_list( \get_option( 'active_plugins', array() ) );
	}

	/**
	 * Get current-site network-active plugin basenames.
	 *
	 * @return string[]
	 */
	public function network_active_plugin_basenames(): array {
		if ( ! $this->is_multisite() ) {
			return array();
		}

		if ( null !== $this->network_provider ) {
			return $this->normalize_plugin_list( call_user_func( $this->network_provider ) );
		}

		if ( ! function_exists( 'get_site_option' ) ) {
			return array();
		}

		$plugins = \get_site_option( 'active_sitewide_plugins', array() );

		if ( ! is_array( $plugins ) ) {
			return array();
		}

		return $this->normalize_plugin_list( array_keys( $plugins ) );
	}

	/**
	 * Whether the current runtime is multisite.
	 *
	 * @return bool
	 */
	public function is_multisite(): bool {
		if ( null !== $this->multisite_provider ) {
			return (bool) call_user_func( $this->multisite_provider );
		}

		return function_exists( 'is_multisite' ) && \is_multisite();
	}

	/**
	 * Normalize plugin basename values.
	 *
	 * @param mixed $plugins Plugin list.
	 * @return string[]
	 */
	private function normalize_plugin_list( $plugins ): array {
		if ( ! is_array( $plugins ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $plugins as $plugin ) {
			if ( ! is_string( $plugin ) ) {
				continue;
			}

			$plugin = trim( $plugin );

			if ( '' !== $plugin ) {
				$normalized[] = $plugin;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}
