<?php
/**
 * Elementor background stylesheet runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Provides the narrow runtime facts needed for companion stylesheet delivery.
 */
interface ElementorBackgroundStylesheetRuntimeInterface {

	/**
	 * Whether the current request is a frontend request eligible for companion CSS.
	 *
	 * @return bool
	 */
	public function is_frontend_request(): bool;

	/**
	 * Get the current singular frontend document ID.
	 *
	 * @return int
	 */
	public function current_singular_document_id(): int;

	/**
	 * Resolve the current Elementor breakpoint map when reliable.
	 *
	 * @return ElementorBackgroundBreakpointMap|null
	 */
	public function breakpoint_map(): ?ElementorBackgroundBreakpointMap;

	/**
	 * Enqueue one stylesheet.
	 *
	 * @param string $handle Style handle.
	 * @param string $url Public URL.
	 * @param string $version Version string.
	 * @return void
	 */
	public function enqueue_stylesheet( string $handle, string $url, string $version ): void;
}
