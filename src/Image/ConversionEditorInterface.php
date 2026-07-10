<?php
/**
 * Conversion editor contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Saves a source image to a temporary derivative path through an image editor.
 */
interface ConversionEditorInterface {

	/**
	 * Save a converted derivative to the request temporary path.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @param int             $quality Conversion quality.
	 * @return ConversionEditorResult
	 */
	public function save( SourceImage $source, DestinationPath $destination, int $quality ): ConversionEditorResult;
}
