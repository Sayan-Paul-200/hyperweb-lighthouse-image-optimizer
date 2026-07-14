<?php
/**
 * Offload-aware attachment source collector contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Collects authoritative attachment sources with optional temporary source leases.
 */
interface AttachmentSourceCollectorInterface {

	/**
	 * Collect one attachment source set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return CollectedSourceSet
	 */
	public function collect( int $attachment_id ): CollectedSourceSet;
}
