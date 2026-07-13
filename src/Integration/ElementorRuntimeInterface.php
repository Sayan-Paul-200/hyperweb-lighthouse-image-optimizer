<?php
/**
 * Elementor runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Provides narrow Elementor request-mode facts.
 */
interface ElementorRuntimeInterface {

	/**
	 * Whether Elementor runtime is available for the current request.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Whether the current request is in Elementor editor mode.
	 *
	 * @return bool
	 */
	public function is_editor_mode(): bool;

	/**
	 * Whether the current request is in Elementor preview mode.
	 *
	 * @return bool
	 */
	public function is_preview_mode(): bool;
}
