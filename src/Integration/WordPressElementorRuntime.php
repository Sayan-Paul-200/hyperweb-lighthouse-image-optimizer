<?php
/**
 * WordPress-backed Elementor runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Isolates Elementor runtime detection and request-mode checks.
 */
final class WordPressElementorRuntime implements ElementorRuntimeInterface {

	/**
	 * Optional plugin-instance provider.
	 *
	 * @var callable|null
	 */
	private $plugin_provider;

	/**
	 * Optional query-argument provider.
	 *
	 * @var callable|null
	 */
	private $query_provider;

	/**
	 * Create runtime seam.
	 *
	 * @param callable|null $plugin_provider Optional plugin-instance provider for tests.
	 * @param callable|null $query_provider Optional query provider for tests.
	 */
	public function __construct( ?callable $plugin_provider = null, ?callable $query_provider = null ) {
		$this->plugin_provider = $plugin_provider;
		$this->query_provider  = $query_provider;
	}

	/**
	 * Whether Elementor runtime is available for the current request.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return is_object( $this->plugin_instance() ) || $this->query_has_value( 'elementor-preview' ) || 'elementor' === $this->query_value( 'action' );
	}

	/**
	 * Whether the current request is in Elementor editor mode.
	 *
	 * @return bool
	 */
	public function is_editor_mode(): bool {
		$plugin = $this->plugin_instance();

		if ( is_object( $plugin ) && isset( $plugin->editor ) && is_object( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) ) {
			return (bool) $plugin->editor->is_edit_mode();
		}

		return 'elementor' === $this->query_value( 'action' );
	}

	/**
	 * Whether the current request is in Elementor preview mode.
	 *
	 * @return bool
	 */
	public function is_preview_mode(): bool {
		$plugin = $this->plugin_instance();

		if ( is_object( $plugin ) && isset( $plugin->preview ) && is_object( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) ) {
			return (bool) $plugin->preview->is_preview_mode();
		}

		return $this->query_has_value( 'elementor-preview' );
	}

	/**
	 * Resolve the current Elementor plugin instance when available.
	 *
	 * @return object|null
	 */
	private function plugin_instance(): ?object {
		if ( null !== $this->plugin_provider ) {
			$plugin = call_user_func( $this->plugin_provider );

			return is_object( $plugin ) ? $plugin : null;
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		if ( property_exists( '\\Elementor\\Plugin', 'instance' ) && is_object( \Elementor\Plugin::$instance ) ) {
			return \Elementor\Plugin::$instance;
		}

		if ( is_callable( array( '\\Elementor\\Plugin', 'instance' ) ) ) {
			$plugin = \Elementor\Plugin::instance();

			if ( is_object( $plugin ) ) {
				return $plugin;
			}
		}

		return null;
	}

	/**
	 * Read normalized query arguments.
	 *
	 * @return array<string,mixed>
	 */
	private function query_args(): array {
		if ( null !== $this->query_provider ) {
			$query = call_user_func( $this->query_provider );

			return is_array( $query ) ? $query : array();
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only request-mode detection inside the isolated Elementor runtime seam.
		$query = $_GET;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( function_exists( 'wp_unslash' ) ) {
			$query = wp_unslash( $query );
		}

		return $query;
	}

	/**
	 * Read one normalized query value.
	 *
	 * @param string $key Query key.
	 * @return string
	 */
	private function query_value( string $key ): string {
		$query = $this->query_args();

		if ( ! array_key_exists( $key, $query ) || ! is_scalar( $query[ $key ] ) ) {
			return '';
		}

		return strtolower( trim( (string) $query[ $key ] ) );
	}

	/**
	 * Whether one query key has a non-empty scalar value.
	 *
	 * @param string $key Query key.
	 * @return bool
	 */
	private function query_has_value( string $key ): bool {
		return '' !== $this->query_value( $key );
	}
}
