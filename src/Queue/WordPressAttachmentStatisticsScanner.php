<?php
/**
 * WordPress attachment statistics scanner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Scans attachment IDs with plugin-owned metadata through bounded WordPress queries.
 */
final class WordPressAttachmentStatisticsScanner implements AttachmentStatisticsScannerInterface {

	/**
	 * Get one bounded page of attachment IDs that own plugin metadata.
	 *
	 * @param int $page Page number, starting at 1.
	 * @param int $page_size Maximum attachment IDs to return.
	 * @return int[]
	 */
	public function scan_page( int $page, int $page_size ): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}

		$page      = max( 1, $page );
		$page_size = max( 1, min( 100, $page_size ) );
		$ids       = \get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => $page_size,
				'paged'                  => $page,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => LifecyclePolicy::META_STATUS,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => LifecyclePolicy::META_DERIVATIVES,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => LifecyclePolicy::META_EXCLUDED,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'intval', $ids ),
				static function ( int $attachment_id ): bool {
					return 0 < $attachment_id;
				}
			)
		);
	}
}
