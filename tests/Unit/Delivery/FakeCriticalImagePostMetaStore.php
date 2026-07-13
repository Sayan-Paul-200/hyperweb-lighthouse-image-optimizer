<?php
/**
 * Fake critical-image post meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImagePostMetaStoreInterface;

/**
 * In-memory post meta store for critical-image registry tests.
 */
final class FakeCriticalImagePostMetaStore implements CriticalImagePostMetaStoreInterface {

	/**
	 * Stored IDs keyed by post ID.
	 *
	 * @var array<int,int>
	 */
	public $values = array();

	/**
	 * Get the stored critical attachment ID for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function get_critical_image_id( int $post_id ): int {
		return $this->values[ $post_id ] ?? 0;
	}

	/**
	 * Persist one critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function update_critical_image_id( int $post_id, int $attachment_id ): bool {
		$this->values[ $post_id ] = $attachment_id;

		return true;
	}

	/**
	 * Delete the stored critical attachment ID.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_critical_image_id( int $post_id ): bool {
		unset( $this->values[ $post_id ] );

		return true;
	}
}
