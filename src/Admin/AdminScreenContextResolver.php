<?php
/**
 * Admin screen context resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Normalizes current screen state from the request and hook suffix.
 */
final class AdminScreenContextResolver {

	/**
	 * Menu helper.
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Query provider callback.
	 *
	 * @var callable|null
	 */
	private $query_provider;

	/**
	 * Create the resolver.
	 *
	 * @param Menu          $menu Menu helper.
	 * @param callable|null $query_provider Optional query provider.
	 */
	public function __construct( Menu $menu, ?callable $query_provider = null ) {
		$this->menu           = $menu;
		$this->query_provider = $query_provider;
	}

	/**
	 * Resolve the current screen context.
	 *
	 * @param string $screen_id Optional admin hook suffix.
	 * @return AdminScreenContext
	 */
	public function resolve( string $screen_id = '' ): AdminScreenContext {
		$request       = $this->request_data();
		$page          = $this->normalize_request_string( $request['page'] ?? null );
		$current_tab   = $this->menu->resolve_tab( $request['tab'] ?? null );
		$normalized_id = $this->normalize_request_string( $screen_id );
		$is_plugin     = ( Menu::MENU_SLUG === $page );

		if ( '' !== $normalized_id && $this->menu->is_plugin_screen( $normalized_id ) ) {
			$is_plugin = true;
		}

		return new AdminScreenContext(
			$page,
			$current_tab,
			$normalized_id,
			$is_plugin
		);
	}

	/**
	 * Get current request data.
	 *
	 * @return array<string,mixed>
	 */
	private function request_data(): array {
		if ( null === $this->query_provider ) {
			return array();
		}

		$request = call_user_func( $this->query_provider );

		return is_array( $request ) ? $request : array();
	}

	/**
	 * Normalize a request string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function normalize_request_string( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return trim( $value );
	}
}
