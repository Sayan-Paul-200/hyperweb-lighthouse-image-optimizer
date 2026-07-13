<?php
/**
 * Fake WooCommerce runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\WooCommerceRuntimeInterface;

/**
 * Deterministic WooCommerce runtime seam for integration tests.
 */
final class FakeWooCommerceRuntime implements WooCommerceRuntimeInterface {

	/**
	 * Whether WooCommerce is available.
	 *
	 * @var bool
	 */
	public $available = true;

	/**
	 * Whether the current request is a single-product request.
	 *
	 * @var bool
	 */
	public $single_product = false;

	/**
	 * Current product ID.
	 *
	 * @var int
	 */
	public $product_id = 0;

	/**
	 * Current product primary image attachment ID.
	 *
	 * @var int
	 */
	public $primary_image_id = 0;

	/**
	 * Current product primary image URL.
	 *
	 * @var string
	 */
	public $primary_image_url = '';

	/**
	 * Current product gallery image attachment IDs.
	 *
	 * @var int[]
	 */
	public $gallery_image_ids = array();

	/**
	 * Whether WooCommerce runtime is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->available;
	}

	/**
	 * Whether the current request is a single-product request.
	 *
	 * @return bool
	 */
	public function is_single_product_request(): bool {
		return $this->single_product;
	}

	/**
	 * Get the current product ID.
	 *
	 * @return int
	 */
	public function current_product_id(): int {
		return $this->product_id;
	}

	/**
	 * Get the current product primary image attachment ID.
	 *
	 * @return int
	 */
	public function current_product_primary_image_id(): int {
		return $this->primary_image_id;
	}

	/**
	 * Get the current product primary image URL.
	 *
	 * @return string
	 */
	public function current_product_primary_image_url(): string {
		return $this->primary_image_url;
	}

	/**
	 * Get current product gallery image IDs.
	 *
	 * @return int[]
	 */
	public function current_product_gallery_image_ids(): array {
		return $this->gallery_image_ids;
	}
}
