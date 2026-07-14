<?php
/**
 * Fake conversion editor.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionEditorInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionEditorResult;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationPath;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;

/**
 * Saves fake derivative facts to the fake filesystem.
 */
final class FakeConversionEditor implements ConversionEditorInterface {

	/**
	 * Filesystem.
	 *
	 * @var FakeConversionFilesystem
	 */
	private $filesystem;

	/**
	 * Result to return.
	 *
	 * @var ConversionEditorResult
	 */
	private $result;

	/**
	 * Output bytes.
	 *
	 * @var int
	 */
	private $output_bytes = 300;

	/**
	 * Output MIME.
	 *
	 * @var string
	 */
	private $output_mime = 'image/webp';

	/**
	 * Output width.
	 *
	 * @var int|null
	 */
	private $output_width = 100;

	/**
	 * Output height.
	 *
	 * @var int|null
	 */
	private $output_height = 100;

	/**
	 * Actual output path to simulate, or null for deterministic temp path.
	 *
	 * @var string|null
	 */
	private $output_path;

	/**
	 * Save calls.
	 *
	 * @var int
	 */
	public $save_calls = 0;

	/**
	 * Recorded source path.
	 *
	 * @var string|null
	 */
	public $source_path;

	/**
	 * Recorded temporary path.
	 *
	 * @var string|null
	 */
	public $temporary_path;

	/**
	 * Recorded target MIME.
	 *
	 * @var string|null
	 */
	public $target_mime;

	/**
	 * Recorded quality.
	 *
	 * @var int|null
	 */
	public $quality;

	/**
	 * Create fake editor.
	 *
	 * @param FakeConversionFilesystem $filesystem Filesystem.
	 */
	public function __construct( FakeConversionFilesystem $filesystem ) {
		$this->filesystem = $filesystem;
		$this->result     = ConversionEditorResult::success();
	}

	/**
	 * Configure output facts.
	 *
	 * @param int      $bytes Bytes.
	 * @param string   $mime_type MIME type.
	 * @param int|null $width Width.
	 * @param int|null $height Height.
	 * @return void
	 */
	public function output( int $bytes, string $mime_type = 'image/webp', ?int $width = 100, ?int $height = 100 ): void {
		$this->output_bytes  = $bytes;
		$this->output_mime   = $mime_type;
		$this->output_width  = $width;
		$this->output_height = $height;
	}

	/**
	 * Configure result.
	 *
	 * @param ConversionEditorResult $result Result.
	 * @return void
	 */
	public function result( ConversionEditorResult $result ): void {
		$this->result = $result;
	}

	/**
	 * Configure the actual path written by the editor.
	 *
	 * @param string $path Output path.
	 * @return void
	 */
	public function output_path( string $path ): void {
		$this->output_path = $path;
	}

	/**
	 * Save fake derivative.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @param int             $quality Quality.
	 * @return ConversionEditorResult
	 */
	public function save( SourceImage $source, DestinationPath $destination, int $quality ): ConversionEditorResult {
		++$this->save_calls;

		$this->source_path    = $source->absolute_path();
		$this->temporary_path = $destination->temporary_absolute_path();
		$this->target_mime    = $destination->target_mime();
		$this->quality        = $quality;

		if ( ! $this->result->is_success() ) {
			return $this->result;
		}

		$output_path = null === $this->output_path ? $destination->temporary_absolute_path() : $this->output_path;

		$this->filesystem->add_file(
			$output_path,
			$this->output_bytes,
			$this->output_mime,
			$this->output_width,
			$this->output_height
		);

		return ConversionEditorResult::success( $this->result->details(), $output_path );
	}
}
