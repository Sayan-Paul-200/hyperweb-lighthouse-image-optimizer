<?php
/**
 * WordPress attachment lock scanner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Finds attachments with plugin lock metadata using WordPress APIs.
 */
final class WordPressAttachmentLockScanner implements AttachmentLockScannerInterface {

	/**
	 * Get locked attachment IDs.
	 *
	 * @param int $limit Maximum attachments to return.
	 * @return int[]
	 */
	public function locked_attachment_ids( int $limit ): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}

		$limit = max( 1, min( 100, $limit ) );
		$ids   = \get_posts(
			array(
				'post_type'     => 'attachment',
				'post_status'   => 'any',
				'fields'        => 'ids',
				'numberposts'   => $limit,
				'meta_key'      => LifecyclePolicy::META_LOCK,
				'no_found_rows' => true,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'intval', $ids ),
				static function ( int $id ): bool {
					return 0 < $id;
				}
			)
		);
	}
}
