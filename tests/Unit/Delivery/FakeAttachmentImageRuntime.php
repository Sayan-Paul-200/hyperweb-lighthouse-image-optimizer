<?php
/**
 * Fake attachment image runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageRuntimeInterface;

/**
 * Deterministic runtime seam for delivery-manager tests.
 */
final class FakeAttachmentImageRuntime implements AttachmentImageRuntimeInterface {

	/**
	 * Whether the attachment is an image.
	 *
	 * @var bool
	 */
	public $is_image = true;

	/**
	 * Attachment metadata.
	 *
	 * @var array<string,mixed>
	 */
	public $metadata = array();

	/**
	 * Attachment metadata keyed by attachment ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $metadata_map = array();

	/**
	 * Request-context flags.
	 *
	 * @var array<string,bool>
	 */
	public $request_context = array(
		'is_admin' => false,
		'is_feed'  => false,
		'is_ajax'  => false,
		'is_rest'  => false,
	);

	/**
	 * Optional known width.
	 *
	 * @var int|null
	 */
	public $requested_width = 2400;

	/**
	 * Current singular post ID.
	 *
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Current singular post type.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * Current custom-logo attachment ID.
	 *
	 * @var int
	 */
	public $custom_logo_attachment_id = 0;

	/**
	 * Current singular post content.
	 *
	 * @var string
	 */
	public $post_content = '';

	/**
	 * Whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		unset( $attachment_id );

		return $this->is_image;
	}

	/**
	 * Read attachment image metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	public function attachment_metadata( int $attachment_id ): array {
		$metadata = $this->metadata_map[ $attachment_id ] ?? null;

		if ( is_array( $metadata ) ) {
			return $metadata;
		}

		return $this->metadata;
	}

	/**
	 * Read current request-context flags.
	 *
	 * @return array<string,bool>
	 */
	public function request_context(): array {
		return $this->request_context;
	}

	/**
	 * Get the current singular post ID when on a frontend singular request.
	 *
	 * @return int
	 */
	public function current_singular_post_id(): int {
		return $this->post_id;
	}

	/**
	 * Get the current singular post type when on a frontend singular request.
	 *
	 * @return string
	 */
	public function current_singular_post_type(): string {
		return $this->post_type;
	}

	/**
	 * Get the current custom-logo attachment ID when available.
	 *
	 * @return int
	 */
	public function custom_logo_attachment_id(): int {
		return $this->custom_logo_attachment_id;
	}

	/**
	 * Get the current singular post content when available.
	 *
	 * @return string
	 */
	public function current_singular_post_content(): string {
		return $this->post_content;
	}

	/**
	 * Determine the most reliable known width for the current attachment image request.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param mixed               $size Requested size.
	 * @param mixed               $attr Requested attributes.
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return int|null
	 */
	public function requested_image_width( int $attachment_id, $size, $attr, array $metadata ): ?int {
		unset( $attachment_id, $size, $attr, $metadata );

		return $this->requested_width;
	}
}
