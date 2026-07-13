<?php
/**
 * Admin page contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Describes one plugin-owned admin tab.
 */
interface AdminPageInterface {

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Get the visible page title.
	 *
	 * @return string
	 */
	public function title(): string;

	/**
	 * Get the page body copy.
	 *
	 * @return string
	 */
	public function description(): string;

	/**
	 * Render the page body.
	 *
	 * @return void
	 */
	public function render(): void;
}
