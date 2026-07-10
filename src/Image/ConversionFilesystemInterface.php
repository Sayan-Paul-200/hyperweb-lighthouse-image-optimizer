<?php
/**
 * Conversion filesystem contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Provides bounded file operations for generated derivatives.
 */
interface ConversionFilesystemInterface {

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string;

	/**
	 * Determine whether path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool;

	/**
	 * Determine whether path is a regular file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool;

	/**
	 * Determine whether path is readable.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_readable( string $path ): bool;

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int;

	/**
	 * Detect MIME type.
	 *
	 * @param string $path Path.
	 * @return string|null
	 */
	public function mime_type( string $path ): ?string;

	/**
	 * Read image dimensions.
	 *
	 * @param string $path Path.
	 * @return array{width:int,height:int}|null
	 */
	public function dimensions( string $path ): ?array;

	/**
	 * Delete a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete( string $path ): bool;

	/**
	 * Move a file into place.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool;
}
