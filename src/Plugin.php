<?php
/**
 * Application composition placeholder.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer;

/**
 * Minimal namespaced plugin marker used to verify Composer autoloading.
 */
final class Plugin {

	/**
	 * Stable plugin slug.
	 *
	 * @var string
	 */
	public const SLUG = 'hyperweb-lighthouse-image-optimizer';

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}
}
