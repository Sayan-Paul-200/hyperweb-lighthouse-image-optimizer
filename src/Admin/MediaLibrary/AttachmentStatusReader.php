<?php
/**
 * Lightweight attachment-status reader.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Reads `_hwlio_status` without touching derivative manifests or filesystems.
 */
final class AttachmentStatusReader {

	/**
	 * Attachment meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Create the reader.
	 *
	 * @param AttachmentMetaStoreInterface $meta Attachment meta store.
	 */
	public function __construct( AttachmentMetaStoreInterface $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Read one attachment status summary.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentStatus
	 */
	public function read( int $attachment_id ): AttachmentStatus {
		return AttachmentStatus::from_stored(
			$this->meta->get( max( 0, $attachment_id ), LifecyclePolicy::META_STATUS, array() )
		);
	}
}
