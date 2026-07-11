<?php
/**
 * Attachment processor contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Processes one attachment-format request.
 */
interface AttachmentProcessorInterface {

	/**
	 * Process one attachment-format request.
	 *
	 * @param AttachmentProcessRequest $request Processing request.
	 * @return AttachmentProcessResult
	 */
	public function process_request( AttachmentProcessRequest $request ): AttachmentProcessResult;
}
