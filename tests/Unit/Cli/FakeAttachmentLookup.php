<?php
/**
 * Fake attachment lookup runtime for CLI tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Cli\AttachmentLookupInterface;

/**
 * Provides deterministic attachment existence and image checks.
 */
final class FakeAttachmentLookup implements AttachmentLookupInterface {

	/**
	 * Existing attachment IDs.
	 *
	 * @var array<int,bool>
	 */
	public $existing = array();

	/**
	 * Image attachment IDs.
	 *
	 * @var array<int,bool>
	 */
	public $images = array();

	/**
	 * Whether the attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function exists( int $attachment_id ): bool {
		return ! empty( $this->existing[ $attachment_id ] );
	}

	/**
	 * Whether the attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_image( int $attachment_id ): bool {
		return ! empty( $this->images[ $attachment_id ] );
	}
}
