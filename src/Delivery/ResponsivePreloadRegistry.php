<?php
/**
 * Responsive preload registry.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Deduplicates emitted responsive preload links within one request.
 */
final class ResponsivePreloadRegistry {

	/**
	 * Seen preload keys.
	 *
	 * @var array<string,bool>
	 */
	private $seen = array();

	/**
	 * Whether a link has already been recorded.
	 *
	 * @param PreloadLinkInterface $link Link.
	 * @return bool
	 */
	public function has( PreloadLinkInterface $link ): bool {
		return isset( $this->seen[ $link->key() ] );
	}

	/**
	 * Record one emitted link.
	 *
	 * @param PreloadLinkInterface $link Link.
	 * @return void
	 */
	public function record( PreloadLinkInterface $link ): void {
		$this->seen[ $link->key() ] = true;
	}
}
