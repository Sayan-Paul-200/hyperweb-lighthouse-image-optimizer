<?php
/**
 * WordPress-backed WooCommerce runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Isolates WooCommerce request and product-image access.
 */
final class WordPressWooCommerceRuntime implements WooCommerceRuntimeInterface {

	/**
	 * Whether WooCommerce runtime is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return function_exists( 'is_product' );
	}

	/**
	 * Whether the current request is a single-product request.
	 *
	 * @return bool
	 */
	public function is_single_product_request(): bool {
		return $this->is_available() && (bool) call_user_func( 'is_product' );
	}

	/**
	 * Get the current product ID.
	 *
	 * @return int
	 */
	public function current_product_id(): int {
		if ( ! $this->is_single_product_request() || ! function_exists( 'get_queried_object_id' ) ) {
			return 0;
		}

		return max( 0, (int) \get_queried_object_id() );
	}

	/**
	 * Get the current product primary image attachment ID.
	 *
	 * @return int
	 */
	public function current_product_primary_image_id(): int {
		$product_id = $this->current_product_id();

		if ( $product_id < 1 || ! function_exists( 'get_post_thumbnail_id' ) ) {
			return 0;
		}

		return max( 0, (int) \get_post_thumbnail_id( $product_id ) );
	}

	/**
	 * Get the current product primary image URL for the visible single-product size.
	 *
	 * @return string
	 */
	public function current_product_primary_image_url(): string {
		$attachment_id = $this->current_product_primary_image_id();

		if ( $attachment_id < 1 || ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return '';
		}

		$url = \wp_get_attachment_image_url( $attachment_id, 'woocommerce_single' );

		return is_string( $url ) ? trim( $url ) : '';
	}

	/**
	 * Get current product gallery attachment IDs.
	 *
	 * @return int[]
	 */
	public function current_product_gallery_image_ids(): array {
		$product_id = $this->current_product_id();

		if ( $product_id < 1 || ! function_exists( 'get_post_meta' ) ) {
			return array();
		}

		$raw = \get_post_meta( $product_id, '_product_image_gallery', true );

		if ( is_array( $raw ) ) {
			$parts = $raw;
		} elseif ( is_string( $raw ) ) {
			$parts = array_filter(
				array_map( 'trim', explode( ',', $raw ) ),
				static function ( string $value ): bool {
					return '' !== $value;
				}
			);
		} else {
			$parts = array();
		}

		$ids = array();

		foreach ( $parts as $value ) {
			if ( is_numeric( $value ) ) {
				$id = max( 0, (int) $value );

				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
