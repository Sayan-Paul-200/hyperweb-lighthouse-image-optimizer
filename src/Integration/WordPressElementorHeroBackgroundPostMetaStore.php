<?php
/**
 * WordPress-backed Elementor hero background post meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Reads and writes one selected Elementor hero background target through WordPress post-meta APIs.
 */
final class WordPressElementorHeroBackgroundPostMetaStore implements ElementorHeroBackgroundPostMetaStoreInterface {

	/**
	 * Stable post meta key.
	 *
	 * @var string
	 */
	public const META_KEY = '_hwlio_elementor_hero_background';

	/**
	 * Get the stored selection for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return ElementorHeroBackgroundTargetSelection|null
	 */
	public function get_selection( int $post_id ): ?ElementorHeroBackgroundTargetSelection {
		$post_id = max( 0, $post_id );

		if ( $post_id < 1 || ! function_exists( 'metadata_exists' ) || ! function_exists( 'get_post_meta' ) ) {
			return null;
		}

		if ( ! \metadata_exists( 'post', $post_id, self::META_KEY ) ) {
			return null;
		}

		return ElementorHeroBackgroundTargetSelection::from_array( \get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * Persist one selected target for a post.
	 *
	 * @param int                                    $post_id Post ID.
	 * @param ElementorHeroBackgroundTargetSelection $selection Target selection.
	 * @return bool
	 */
	public function update_selection( int $post_id, ElementorHeroBackgroundTargetSelection $selection ): bool {
		return false !== \update_post_meta( max( 0, $post_id ), self::META_KEY, $selection->to_array() );
	}

	/**
	 * Delete one stored selection.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_selection( int $post_id ): bool {
		return \delete_post_meta( max( 0, $post_id ), self::META_KEY );
	}
}
