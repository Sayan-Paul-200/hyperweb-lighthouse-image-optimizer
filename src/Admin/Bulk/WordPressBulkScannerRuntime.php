<?php
/**
 * WordPress bulk scanner runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Uses bounded WordPress and database lookups for bulk dry-run scans.
 */
final class WordPressBulkScannerRuntime implements BulkScannerRuntimeInterface {

	/**
	 * Read one bounded page of attachment IDs after the given cursor.
	 *
	 * @param BulkScanFilters $filters Normalized filters.
	 * @param int             $after_id Exclusive attachment-ID cursor.
	 * @param int             $limit Page size.
	 * @return int[]
	 */
	public function scan_page( BulkScanFilters $filters, int $after_id, int $limit ): array {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb || ! is_string( $wpdb->posts ) ) {
			return array();
		}

		$limit      = max( 1, min( 100, $limit ) );
		$after_id   = max( 0, $after_id );
		$where      = array(
			'post_type = %s',
			'ID > %d',
		);
		$parameters = array(
			'attachment',
			$after_id,
		);

		if ( null !== $filters->date_from() ) {
			$where[]      = 'post_date >= %s';
			$parameters[] = $filters->date_from() . ' 00:00:00';
		}

		if ( null !== $filters->date_to() ) {
			$where[]      = 'post_date <= %s';
			$parameters[] = $filters->date_to() . ' 23:59:59';
		}

		if ( $filters->has_attachment_ids() ) {
			$placeholders = implode(
				', ',
				array_fill( 0, count( $filters->attachment_ids() ), '%d' )
			);
			$where[]      = 'ID IN (' . $placeholders . ')';
			$parameters   = array_merge( $parameters, $filters->attachment_ids() );
		}

		$parameters[] = $limit;
		$sql          = sprintf(
			'SELECT ID FROM %s WHERE %s ORDER BY ID ASC LIMIT %%d',
			$wpdb->posts,
			implode( ' AND ', $where )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic placeholders are assembled from sanitized segments before prepare().
		$prepared = $wpdb->prepare( $sql, $parameters );

		if ( ! is_string( $prepared ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is already prepared above.
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

	/**
	 * Determine whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return \wp_attachment_is_image( max( 0, $attachment_id ) );
	}

	/**
	 * Read lightweight preview records for the given attachment IDs.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 * @return array<int,array<string,mixed>>
	 */
	public function preview_records( array $attachment_ids ): array {
		if ( array() === $attachment_ids || ! function_exists( 'get_posts' ) ) {
			return array();
		}

		$ids = array_values(
			array_filter(
				array_map( 'intval', $attachment_ids ),
				static function ( int $attachment_id ): bool {
					return 0 < $attachment_id;
				}
			)
		);

		if ( array() === $ids ) {
			return array();
		}

		$posts = \get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'any',
				'post__in'               => $ids,
				'orderby'                => 'post__in',
				'posts_per_page'         => count( $ids ),
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		if ( ! is_array( $posts ) ) {
			return array();
		}

		$records = array();

		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$attachment_id = (int) $post->ID;
			$attached_file = function_exists( 'get_post_meta' ) ? \get_post_meta( $attachment_id, '_wp_attached_file', true ) : '';
			$filename      = is_string( $attached_file ) && '' !== $attached_file
				? basename( str_replace( '\\', '/', $attached_file ) )
				: '';
			$created_at    = '0000-00-00 00:00:00' !== $post->post_date_gmt
				? $post->post_date_gmt
				: $post->post_date;

			$records[ $attachment_id ] = array(
				'attachment_id'   => $attachment_id,
				'title'           => $post->post_title,
				'filename'        => $filename,
				'uploaded_at_gmt' => $created_at,
			);
		}

		return $records;
	}
}
