<?php
/**
 * Elementor hero background post meta store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Reads and writes one selected Elementor hero background target per post.
 */
interface ElementorHeroBackgroundPostMetaStoreInterface {

	/**
	 * Get the stored selection for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return ElementorHeroBackgroundTargetSelection|null
	 */
	public function get_selection( int $post_id ): ?ElementorHeroBackgroundTargetSelection;

	/**
	 * Persist one selected target for a post.
	 *
	 * @param int                                    $post_id Post ID.
	 * @param ElementorHeroBackgroundTargetSelection $selection Target selection.
	 * @return bool
	 */
	public function update_selection( int $post_id, ElementorHeroBackgroundTargetSelection $selection ): bool;

	/**
	 * Delete one stored selection.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_selection( int $post_id ): bool;
}
