<?php
/**
 * WooCommerce runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Provides narrow WooCommerce request and product-image facts.
 */
interface WooCommerceRuntimeInterface {

	/**
	 * Whether WooCommerce runtime is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Whether the current request is a single-product request.
	 *
	 * @return bool
	 */
	public function is_single_product_request(): bool;

	/**
	 * Get the current product ID.
	 *
	 * @return int
	 */
	public function current_product_id(): int;

	/**
	 * Get the current product primary image attachment ID.
	 *
	 * @return int
	 */
	public function current_product_primary_image_id(): int;

	/**
	 * Get the current product primary image URL for the visible single-product size.
	 *
	 * @return string
	 */
	public function current_product_primary_image_url(): string;

	/**
	 * Get current product gallery attachment IDs.
	 *
	 * @return int[]
	 */
	public function current_product_gallery_image_ids(): array;
}
