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
	 * Attachment statuses that should be included in dashboard statistics.
	 *
	 * WordPress stores normal Media Library attachments as `inherit`; `any` can
	 * exclude that status, which would make a fresh statistics cache look empty.
	 *
	 * @var string[]
	 */
	private const ATTACHMENT_STATUSES = array( 'inherit', 'private', 'publish' );

	/**
	 * WordPress get_posts callback.
	 *
	 * @var callable
	 */
	private $get_posts;

	/**
	 * Create the scanner.
	 *
	 * @param callable|null $get_posts Optional get_posts-compatible callback.
	 */
	public function __construct( ?callable $get_posts = null ) {
		$this->get_posts = $get_posts ?? static function ( array $args ): array {
			if ( ! function_exists( 'get_posts' ) ) {
				return array();
			}

			return \get_posts( $args );
		};
	}

	/**
	 * Get one bounded page of attachment IDs that own plugin metadata.
	 *
	 * @param int $page Page number, starting at 1.
	 * @param int $page_size Maximum attachment IDs to return.
	 * @return int[]
	 */
	public function scan_page( int $page, int $page_size ): array {
		$page      = max( 1, $page );
		$page_size = max( 1, min( 100, $page_size ) );
		$ids       = call_user_func(
			$this->get_posts,
			array(
				'post_type'              => 'attachment',
				'post_status'            => self::ATTACHMENT_STATUSES,
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
