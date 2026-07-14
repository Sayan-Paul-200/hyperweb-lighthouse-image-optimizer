<?php
/**
 * Content inventory runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Provides arbitrary content and URL facts for read-only page inventory.
 */
interface ContentInventoryRuntimeInterface {

	/**
	 * Whether one content record exists.
	 *
	 * @param int $content_id Content ID.
	 * @return bool
	 */
	public function content_exists( int $content_id ): bool;

	/**
	 * Get content type.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_type( int $content_id ): string;

	/**
	 * Get content status.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_status( int $content_id ): string;

	/**
	 * Get content title.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_title( int $content_id ): string;

	/**
	 * Get raw stored post content.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_body( int $content_id ): string;

	/**
	 * Get featured image ID.
	 *
	 * @param int $content_id Content ID.
	 * @return int
	 */
	public function featured_image_id( int $content_id ): int;

	/**
	 * Get WooCommerce gallery IDs.
	 *
	 * @param int $content_id Content ID.
	 * @return int[]
	 */
	public function product_gallery_image_ids( int $content_id ): array;

	/**
	 * Get site URL.
	 *
	 * @return string
	 */
	public function site_url(): string;

	/**
	 * Get home URL.
	 *
	 * @return string
	 */
	public function home_url(): string;

	/**
	 * Get current uploads base URL.
	 *
	 * @return string
	 */
	public function uploads_base_url(): string;

	/**
	 * Get one safe public content URL for optional external reporting.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_public_url( int $content_id ): string;
}
