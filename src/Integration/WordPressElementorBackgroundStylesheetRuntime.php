<?php
/**
 * WordPress-backed Elementor background stylesheet runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Isolates Elementor background companion runtime checks and enqueue behavior.
 */
final class WordPressElementorBackgroundStylesheetRuntime implements ElementorBackgroundStylesheetRuntimeInterface {

	/**
	 * Optional request-context provider.
	 *
	 * @var callable|null
	 */
	private $request_context_provider;

	/**
	 * Optional plugin-instance provider.
	 *
	 * @var callable|null
	 */
	private $plugin_provider;

	/**
	 * Optional enqueue callback.
	 *
	 * @var callable|null
	 */
	private $enqueue_callback;

	/**
	 * Create runtime seam.
	 *
	 * @param callable|null $request_context_provider Optional request-context provider for tests.
	 * @param callable|null $plugin_provider Optional Elementor plugin-instance provider for tests.
	 * @param callable|null $enqueue_callback Optional enqueue callback for tests.
	 */
	public function __construct(
		?callable $request_context_provider = null,
		?callable $plugin_provider = null,
		?callable $enqueue_callback = null
	) {
		$this->request_context_provider = $request_context_provider;
		$this->plugin_provider          = $plugin_provider;
		$this->enqueue_callback         = $enqueue_callback;
	}

	/**
	 * Whether the current request is a frontend request eligible for companion CSS.
	 *
	 * @return bool
	 */
	public function is_frontend_request(): bool {
		$context = $this->request_context();

		return ! $context['admin'] && ! $context['feed'] && ! $context['ajax'] && ! $context['rest'];
	}

	/**
	 * Get the current singular frontend document ID.
	 *
	 * @return int
	 */
	public function current_singular_document_id(): int {
		$context = $this->request_context();

		if ( ! $context['singular'] ) {
			return 0;
		}

		return max( 0, $context['queried_object_id'] );
	}

	/**
	 * Resolve the current Elementor breakpoint map when reliable.
	 *
	 * @return ElementorBackgroundBreakpointMap|null
	 */
	public function breakpoint_map(): ?ElementorBackgroundBreakpointMap {
		$plugin = $this->plugin_instance();

		if ( ! is_object( $plugin ) || ! isset( $plugin->breakpoints ) || ! is_object( $plugin->breakpoints ) ) {
			return null;
		}

		$values = $this->breakpoint_values( $plugin->breakpoints );

		if ( ! isset( $values['mobile'], $values['tablet'] ) ) {
			return null;
		}

		return ElementorBackgroundBreakpointMap::from_max_widths( (int) $values['mobile'], (int) $values['tablet'] );
	}

	/**
	 * Enqueue one stylesheet.
	 *
	 * @param string $handle Style handle.
	 * @param string $url Public URL.
	 * @param string $version Version string.
	 * @return void
	 */
	public function enqueue_stylesheet( string $handle, string $url, string $version ): void {
		if ( is_callable( $this->enqueue_callback ) ) {
			call_user_func( $this->enqueue_callback, $handle, $url, $version );

			return;
		}

		if ( function_exists( 'wp_enqueue_style' ) ) {
			\wp_enqueue_style( $handle, $url, array(), $version );
		}
	}

	/**
	 * Read normalized request context.
	 *
	 * @return array<string,mixed>
	 */
	private function request_context(): array {
		if ( null !== $this->request_context_provider ) {
			$context = call_user_func( $this->request_context_provider );

			if ( is_array( $context ) ) {
				return array(
					'admin'             => ! empty( $context['admin'] ),
					'feed'              => ! empty( $context['feed'] ),
					'ajax'              => ! empty( $context['ajax'] ),
					'rest'              => ! empty( $context['rest'] ),
					'singular'          => ! empty( $context['singular'] ),
					'queried_object_id' => isset( $context['queried_object_id'] ) ? (int) $context['queried_object_id'] : 0,
				);
			}
		}

		$rest_request = false;

		if ( function_exists( 'wp_is_json_request' ) ) {
			$rest_request = \wp_is_json_request();
		} elseif ( defined( 'REST_REQUEST' ) ) {
			$rest_request = (bool) constant( 'REST_REQUEST' );
		}

		return array(
			'admin'             => function_exists( 'is_admin' ) ? \is_admin() : false,
			'feed'              => function_exists( 'is_feed' ) ? \is_feed() : false,
			'ajax'              => function_exists( 'wp_doing_ajax' ) ? \wp_doing_ajax() : false,
			'rest'              => $rest_request,
			'singular'          => function_exists( 'is_singular' ) ? \is_singular() : false,
			'queried_object_id' => function_exists( 'get_queried_object_id' ) ? (int) \get_queried_object_id() : 0,
		);
	}

	/**
	 * Resolve current Elementor plugin instance.
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

			return is_object( $plugin ) ? $plugin : null;
		}

		return null;
	}

	/**
	 * Resolve reliable breakpoint values from the Elementor runtime.
	 *
	 * @param object $breakpoints Elementor breakpoints manager.
	 * @return array<string,int>
	 */
	private function breakpoint_values( object $breakpoints ): array {
		$values = array();

		if ( method_exists( $breakpoints, 'get_active_breakpoints' ) ) {
			$active = $breakpoints->get_active_breakpoints();

			if ( is_array( $active ) ) {
				foreach ( array( 'mobile', 'tablet' ) as $name ) {
					if ( ! array_key_exists( $name, $active ) ) {
						continue;
					}

					$value = $this->extract_breakpoint_value( $active[ $name ] );

					if ( null !== $value ) {
						$values[ $name ] = $value;
					}
				}
			}
		}

		if ( isset( $values['mobile'], $values['tablet'] ) ) {
			return $values;
		}

		if ( method_exists( $breakpoints, 'get_breakpoints_config' ) ) {
			$config = $breakpoints->get_breakpoints_config();

			if ( is_array( $config ) ) {
				foreach ( array( 'mobile', 'tablet' ) as $name ) {
					if ( ! isset( $config[ $name ] ) || ! is_array( $config[ $name ] ) ) {
						continue;
					}

					$value = isset( $config[ $name ]['value'] ) && is_numeric( $config[ $name ]['value'] )
						? (int) $config[ $name ]['value']
						: null;

					if ( null !== $value ) {
						$values[ $name ] = $value;
					}
				}
			}
		}

		return $values;
	}

	/**
	 * Extract one breakpoint value.
	 *
	 * @param mixed $breakpoint Breakpoint config.
	 * @return int|null
	 */
	private function extract_breakpoint_value( $breakpoint ): ?int {
		if ( is_array( $breakpoint ) && isset( $breakpoint['value'] ) && is_numeric( $breakpoint['value'] ) ) {
			return (int) $breakpoint['value'];
		}

		if ( is_object( $breakpoint ) ) {
			if ( method_exists( $breakpoint, 'get_value' ) ) {
				$value = $breakpoint->get_value();

				if ( is_numeric( $value ) ) {
					return (int) $value;
				}
			}

			if ( isset( $breakpoint->value ) && is_numeric( $breakpoint->value ) ) {
				return (int) $breakpoint->value;
			}
		}

		return null;
	}
}
