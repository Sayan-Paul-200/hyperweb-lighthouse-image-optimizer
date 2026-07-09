<?php
/**
 * Sample conversion probe contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Wraps image-editor sample conversion behavior.
 */
interface SampleConversionProbeInterface {

	/**
	 * Convert a sample source to the target MIME type.
	 *
	 * @param string $source_path Source path.
	 * @param string $destination_path Destination path.
	 * @param string $mime_type Target MIME type.
	 * @return SampleConversionResult
	 */
	public function convert( string $source_path, string $destination_path, string $mime_type ): SampleConversionResult;
}
