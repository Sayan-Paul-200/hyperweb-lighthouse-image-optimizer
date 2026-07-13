<?php
/**
 * Media Library runtime adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

/**
 * Describes the WordPress Media Library APIs needed by the 6.4 integration.
 */
interface MediaLibraryRuntimeInterface {

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool;

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool;

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool;

	/**
	 * Get the current screen ID when available.
	 *
	 * @return string
	 */
	public function current_screen_id(): string;

	/**
	 * Get the current post type when available.
	 *
	 * @return string
	 */
	public function current_post_type(): string;

	/**
	 * Get the current post ID from request state when available.
	 *
	 * @return int
	 */
	public function current_post_id(): int;
}
