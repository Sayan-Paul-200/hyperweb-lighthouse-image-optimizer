<?php
/**
 * WordPress critical-image post meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reads and writes the per-post critical-image meta key via WordPress APIs.
 */
final class WordPressCriticalImagePostMetaStore implements CriticalImagePostMetaStoreInterface {

	/**
	 * Critical-image post meta key.
	 *
	 * @var string
	 */
	public const META_KEY = '_hwlio_critical_image_id';

	/**
	 * Get the stored critical attachment ID for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function get_critical_image_id( int $post_id ): int {
		$post_id = max( 0, $post_id );

		if ( $post_id < 1 || ! function_exists( 'metadata_exists' ) || ! function_exists( 'get_post_meta' ) ) {
			return 0;
		}

		if ( ! \metadata_exists( 'post', $post_id, self::META_KEY ) ) {
			return 0;
		}

		return max( 0, (int) \get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * Persist one critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function update_critical_image_id( int $post_id, int $attachment_id ): bool {
		return false !== \update_post_meta( max( 0, $post_id ), self::META_KEY, max( 0, $attachment_id ) );
	}

	/**
	 * Delete the stored critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_critical_image_id( int $post_id ): bool {
		return \delete_post_meta( max( 0, $post_id ), self::META_KEY );
	}
}
