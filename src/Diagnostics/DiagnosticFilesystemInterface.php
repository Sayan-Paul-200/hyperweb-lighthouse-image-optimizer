<?php
/**
 * Diagnostic filesystem contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Provides bounded file operations for diagnostics.
 */
interface DiagnosticFilesystemInterface {

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string;

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool;

	/**
	 * Determine whether a path is a regular file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool;

	/**
	 * Write a file.
	 *
	 * @param string $path Path.
	 * @param string $contents Contents.
	 * @return bool
	 */
	public function write( string $path, string $contents ): bool;

	/**
	 * Rename a file.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function rename( string $source, string $destination ): bool;

	/**
	 * Delete a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete( string $path ): bool;

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int;
}
