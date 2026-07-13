<?php
/**
 * Admin screen context value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Carries normalized plugin screen state for PHP rendering and JS bootstrap.
 */
final class AdminScreenContext {

	/**
	 * Requested page slug.
	 *
	 * @var string
	 */
	private $page;

	/**
	 * Normalized current tab.
	 *
	 * @var string
	 */
	private $current_tab;

	/**
	 * Current admin hook suffix.
	 *
	 * @var string
	 */
	private $screen_id;

	/**
	 * Whether this request belongs to the plugin screen.
	 *
	 * @var bool
	 */
	private $plugin_screen;

	/**
	 * Create the context.
	 *
	 * @param string $page Requested page slug.
	 * @param string $current_tab Normalized current tab.
	 * @param string $screen_id Admin hook suffix.
	 * @param bool   $plugin_screen Whether this is the plugin screen.
	 */
	public function __construct( string $page, string $current_tab, string $screen_id, bool $plugin_screen ) {
		$this->page          = $page;
		$this->current_tab   = $current_tab;
		$this->screen_id     = $screen_id;
		$this->plugin_screen = $plugin_screen;
	}

	/**
	 * Get the requested page slug.
	 *
	 * @return string
	 */
	public function page(): string {
		return $this->page;
	}

	/**
	 * Get the normalized current tab.
	 *
	 * @return string
	 */
	public function current_tab(): string {
		return $this->current_tab;
	}

	/**
	 * Get the current admin hook suffix.
	 *
	 * @return string
	 */
	public function screen_id(): string {
		return $this->screen_id;
	}

	/**
	 * Determine whether this request is the plugin screen.
	 *
	 * @return bool
	 */
	public function is_plugin_screen(): bool {
		return $this->plugin_screen;
	}
}
