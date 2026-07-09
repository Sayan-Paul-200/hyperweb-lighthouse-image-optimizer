<?php
/**
 * Filesystem abstraction.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Provides testable filesystem operations for lifecycle cleanup.
 */
interface FilesystemInterface {

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function exists( string $path ): bool;

	/**
	 * Determine whether a path is a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_file( string $path ): bool;

	/**
	 * Determine whether a path is a symbolic link.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_link( string $path ): bool;

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Absolute path.
	 * @return string
	 */
	public function realpath( string $path ): string;

	/**
	 * Delete a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function delete( string $path ): bool;
}
