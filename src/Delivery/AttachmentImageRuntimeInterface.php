<?php
/**
 * Attachment image runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Provides WordPress attachment-image runtime facts for frontend delivery.
 */
interface AttachmentImageRuntimeInterface {

	/**
	 * Whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool;

	/**
	 * Read attachment image metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	public function attachment_metadata( int $attachment_id ): array;

	/**
	 * Read current request-context flags.
	 *
	 * @return array<string,bool>
	 */
	public function request_context(): array;

	/**
	 * Get the current singular post ID when on a frontend singular request.
	 *
	 * @return int
	 */
	public function current_singular_post_id(): int;

	/**
	 * Get the current singular post type when on a frontend singular request.
	 *
	 * @return string
	 */
	public function current_singular_post_type(): string;

	/**
	 * Get the current custom-logo attachment ID when available.
	 *
	 * @return int
	 */
	public function custom_logo_attachment_id(): int;

	/**
	 * Get the current singular post content when available.
	 *
	 * @return string
	 */
	public function current_singular_post_content(): string;

	/**
	 * Determine the most reliable known width for the current attachment image request.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param mixed               $size Requested size.
	 * @param mixed               $attr Requested attributes.
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return int|null
	 */
	public function requested_image_width( int $attachment_id, $size, $attr, array $metadata ): ?int;
}
