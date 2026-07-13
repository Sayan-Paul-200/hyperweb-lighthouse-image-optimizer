<?php
/**
 * Shared preload link contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Describes one safe preload link payload that can be deduplicated per request.
 */
interface PreloadLinkInterface {

	/**
	 * Build a stable dedupe key.
	 *
	 * @return string
	 */
	public function key(): string;

	/**
	 * Render final HTML.
	 *
	 * @return string
	 */
	public function html(): string;

	/**
	 * Serialize the link payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array;
}
