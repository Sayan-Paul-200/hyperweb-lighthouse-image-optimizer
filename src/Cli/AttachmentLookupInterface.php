<?php
/**
 * Attachment lookup contract for WP-CLI commands.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Wraps basic attachment existence and image validation.
 */
interface AttachmentLookupInterface {

	/**
	 * Whether the attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function exists( int $attachment_id ): bool;

	/**
	 * Whether the attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_image( int $attachment_id ): bool;
}
