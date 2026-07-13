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
		unset( $attachment_id );

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
	 * Determine the most reliable known width for the current attachment image request.
	 *
	 * @param int                $attachment_id Attachment ID.
	 * @param mixed              $size Requested size.
	 * @param mixed              $attr Requested attributes.
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return int|null
	 */
	public function requested_image_width( int $attachment_id, $size, $attr, array $metadata ): ?int {
		unset( $attachment_id, $size, $attr, $metadata );

		return $this->requested_width;
	}
}
