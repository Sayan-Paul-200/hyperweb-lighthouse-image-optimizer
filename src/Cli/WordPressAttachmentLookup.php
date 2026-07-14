<?php
/**
 * WordPress-backed attachment lookup runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Uses conservative core attachment lookups for CLI validation.
 */
final class WordPressAttachmentLookup implements AttachmentLookupInterface {

	/**
	 * Whether the attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function exists( int $attachment_id ): bool {
		$attachment_id = max( 0, $attachment_id );

		if ( 0 === $attachment_id || ! function_exists( 'get_post' ) ) {
			return false;
		}

		return null !== \get_post( $attachment_id );
	}

	/**
	 * Whether the attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_image( int $attachment_id ): bool {
		if ( 0 === max( 0, $attachment_id ) || ! function_exists( 'wp_attachment_is_image' ) ) {
			return false;
		}

		return (bool) \wp_attachment_is_image( $attachment_id );
	}
}
