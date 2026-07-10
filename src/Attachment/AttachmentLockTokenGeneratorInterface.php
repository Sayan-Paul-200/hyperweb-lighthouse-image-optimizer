<?php
/**
 * Attachment lock token generator contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Generates non-public lock ownership tokens.
 */
interface AttachmentLockTokenGeneratorInterface {

	/**
	 * Generate a token.
	 *
	 * @return string
	 */
	public function generate(): string;
}
