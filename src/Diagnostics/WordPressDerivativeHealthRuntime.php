<?php
/**
 * WordPress derivative health runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Queries attachment IDs with plugin-owned derivative metadata.
 */
final class WordPressDerivativeHealthRuntime implements DerivativeHealthRuntimeInterface {

	/**
	 * Read attachment IDs with plugin-owned derivative metadata after a cursor.
	 *
	 * @param int $after_id Exclusive attachment-ID cursor.
	 * @param int $limit Page size.
	 * @return int[]
	 */
	public function attachment_ids_after( int $after_id, int $limit ): array {
		global $wpdb;

		if (
			! isset( $wpdb )
			|| ! is_object( $wpdb )
			|| ! isset( $wpdb->posts, $wpdb->postmeta )
			|| ! is_string( $wpdb->posts )
			|| ! is_string( $wpdb->postmeta )
		) {
			return array();
		}

		$after_id = max( 0, $after_id );
		$limit    = max( 1, min( 100, $limit ) );
		$sql      = sprintf(
			'SELECT DISTINCT p.ID FROM %s p INNER JOIN %s pm ON pm.post_id = p.ID WHERE p.post_type = %%s AND p.ID > %%d AND pm.meta_key = %%s ORDER BY p.ID ASC LIMIT %%d',
			$wpdb->posts,
			$wpdb->postmeta
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names come from wpdb, values are prepared below.
		$prepared = $wpdb->prepare( $sql, 'attachment', $after_id, LifecyclePolicy::META_DERIVATIVES, $limit );

		if ( ! is_string( $prepared ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is prepared above.
		$ids = $wpdb->get_col( $prepared );

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
