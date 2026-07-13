<?php
/**
 * Admin menu helper.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;

/**
 * Owns the Phase 6.1 menu and tab routing primitives.
 */
final class Menu {

	public const PARENT_SLUG = 'upload.php';
	public const MENU_SLUG   = 'hwlio';
	public const DEFAULT_TAB = 'dashboard';

	/**
	 * Ordered tab slugs.
	 *
	 * @var string[]
	 */
	private $tabs;

	/**
	 * Admin runtime adapter.
	 *
	 * @var AdminRuntimeInterface
	 */
	private $runtime;

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * Captured submenu hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Create the menu helper.
	 *
	 * @param AdminRuntimeInterface $runtime Admin runtime adapter.
	 * @param string                $capability Required capability.
	 * @param string[]              $tabs Ordered tab slugs.
	 */
	public function __construct(
		AdminRuntimeInterface $runtime,
		string $capability = SettingsSchema::CAPABILITY_MANAGE_OPTIONS,
		array $tabs = array( 'dashboard', 'bulk-optimize', 'settings', 'diagnostics', 'logs' )
	) {
		$this->runtime    = $runtime;
		$this->capability = $capability;
		$this->tabs       = array_values( $tabs );
	}

	/**
	 * Register the Media submenu and capture its hook suffix.
	 *
	 * @param callable $callback Render callback.
	 * @return void
	 */
	public function register( callable $callback ): void {
		$this->hook_suffix = $this->runtime->register_submenu_page(
			self::PARENT_SLUG,
			$this->title(),
			$this->title(),
			$this->capability,
			self::MENU_SLUG,
			$callback
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @return string
	 */
	public function capability(): string {
		return $this->capability;
	}

	/**
	 * Get ordered tab slugs.
	 *
	 * @return string[]
	 */
	public function tabs(): array {
		return $this->tabs;
	}

	/**
	 * Resolve a request tab to a known slug.
	 *
	 * @param mixed $value Raw request value.
	 * @return string
	 */
	public function resolve_tab( $value ): string {
		if ( ! is_string( $value ) ) {
			return self::DEFAULT_TAB;
		}

		$tab = strtolower( trim( $value ) );
		$tab = (string) preg_replace( '/[^a-z0-9-]/', '', $tab );

		if ( ! in_array( $tab, $this->tabs, true ) ) {
			return self::DEFAULT_TAB;
		}

		return $tab;
	}

	/**
	 * Build one plugin screen URL.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	public function page_url( string $tab = self::DEFAULT_TAB ): string {
		$resolved_tab = $this->resolve_tab( $tab );
		$query = array(
			'page' => self::MENU_SLUG,
		);

		if ( self::DEFAULT_TAB !== $resolved_tab ) {
			$query['tab'] = $resolved_tab;
		}

		return rtrim( $this->runtime->admin_url( self::PARENT_SLUG ), '?' ) . '?' . http_build_query( $query );
	}

	/**
	 * Get the captured screen IDs for later asset scoping.
	 *
	 * @return string[]
	 */
	public function screen_ids(): array {
		if ( '' === $this->hook_suffix ) {
			return array();
		}

		return array( $this->hook_suffix );
	}

	/**
	 * Determine whether one screen ID belongs to the plugin shell.
	 *
	 * @param string $screen_id Screen ID.
	 * @return bool
	 */
	public function is_plugin_screen( string $screen_id ): bool {
		return in_array( $screen_id, $this->screen_ids(), true );
	}

	/**
	 * Get the visible screen title.
	 *
	 * @return string
	 */
	private function title(): string {
		if ( function_exists( '__' ) ) {
			return __( 'Lighthouse Image Optimizer', 'hyperweb-lighthouse-image-optimizer' );
		}

		return 'Lighthouse Image Optimizer';
	}
}
