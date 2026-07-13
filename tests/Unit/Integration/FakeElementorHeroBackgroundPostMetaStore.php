<?php
/**
 * Fake Elementor hero background post meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundPostMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundTargetSelection;

/**
 * In-memory hero background selection store for tests.
 */
final class FakeElementorHeroBackgroundPostMetaStore implements ElementorHeroBackgroundPostMetaStoreInterface {

	/**
	 * Stored selections keyed by post ID.
	 *
	 * @var array<int,ElementorHeroBackgroundTargetSelection>
	 */
	public $values = array();

	/**
	 * Get stored selection.
	 *
	 * @param int $post_id Post ID.
	 * @return ElementorHeroBackgroundTargetSelection|null
	 */
	public function get_selection( int $post_id ): ?ElementorHeroBackgroundTargetSelection {
		return isset( $this->values[ $post_id ] ) ? $this->values[ $post_id ] : null;
	}

	/**
	 * Persist one selection.
	 *
	 * @param int                                    $post_id Post ID.
	 * @param ElementorHeroBackgroundTargetSelection $selection Selection.
	 * @return bool
	 */
	public function update_selection( int $post_id, ElementorHeroBackgroundTargetSelection $selection ): bool {
		$this->values[ $post_id ] = $selection;

		return true;
	}

	/**
	 * Delete one selection.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_selection( int $post_id ): bool {
		unset( $this->values[ $post_id ] );

		return true;
	}
}
