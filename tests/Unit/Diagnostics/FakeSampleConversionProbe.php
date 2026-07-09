<?php
/**
 * Fake sample conversion probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionResult;

/**
 * Simulates sample conversion for unit tests.
 */
final class FakeSampleConversionProbe implements SampleConversionProbeInterface {

	/**
	 * Result to return.
	 *
	 * @var SampleConversionResult
	 */
	public $result;

	/**
	 * Fake filesystem.
	 *
	 * @var FakeDiagnosticFilesystem|null
	 */
	public $filesystem;

	/**
	 * Whether success should write an output file.
	 *
	 * @var bool
	 */
	public $write_output = true;

	/**
	 * Conversion calls.
	 *
	 * @var array<int,array{source:string,destination:string,mime_type:string}>
	 */
	public $calls = array();

	/**
	 * Create probe.
	 *
	 * @param SampleConversionResult|null   $result Result.
	 * @param FakeDiagnosticFilesystem|null $filesystem Filesystem.
	 */
	public function __construct( ?SampleConversionResult $result = null, ?FakeDiagnosticFilesystem $filesystem = null ) {
		$this->result     = $result ?? SampleConversionResult::success();
		$this->filesystem = $filesystem;
	}

	/**
	 * Convert a sample.
	 *
	 * @param string $source_path Source path.
	 * @param string $destination_path Destination path.
	 * @param string $mime_type MIME type.
	 * @return SampleConversionResult
	 */
	public function convert( string $source_path, string $destination_path, string $mime_type ): SampleConversionResult {
		$this->calls[] = array(
			'source'      => $source_path,
			'destination' => $destination_path,
			'mime_type'   => $mime_type,
		);

		if ( $this->result->is_success() && $this->write_output && null !== $this->filesystem ) {
			$this->filesystem->write( $destination_path, 'converted' );
		}

		return $this->result;
	}
}
