<?php
/**
 * Critical-image post meta store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Provides narrow access to the per-post critical-image meta key.
 */
interface CriticalImagePostMetaStoreInterface {

	/**
	 * Get the stored critical attachment ID for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function get_critical_image_id( int $post_id ): int;

	/**
	 * Persist one critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function update_critical_image_id( int $post_id, int $attachment_id ): bool;

	/**
	 * Delete the stored critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_critical_image_id( int $post_id ): bool;
}
