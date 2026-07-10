<?php
/**
 * Animation detector contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Detects whether a source image is animated without modifying files.
 */
interface AnimationDetectorInterface {

	/**
	 * Detect animation state for a source file.
	 *
	 * @param string $absolute_path Absolute path.
	 * @param string $mime_type Detected MIME type.
	 * @return AnimationStatus
	 */
	public function detect( string $absolute_path, string $mime_type ): AnimationStatus;
}
