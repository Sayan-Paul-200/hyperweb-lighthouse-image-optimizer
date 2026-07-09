<?php
/**
 * Attachment source provider contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Wraps read-only WordPress attachment source data.
 */
interface AttachmentSourceProviderInterface {

	/**
	 * Get the current attached display file.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attached_file( int $attachment_id ): ?string;

	/**
	 * Get attachment metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public function metadata( int $attachment_id ): ?array;

	/**
	 * Get uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string;
}
