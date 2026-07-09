<?php
/**
 * WordPress diagnostic filesystem adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Uses bounded native file operations for plugin-owned diagnostic files.
 */
final class WordPressDiagnosticFilesystem implements DiagnosticFilesystemInterface {

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$realpath = realpath( $path );

		return false === $realpath ? '' : $realpath;
	}

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return file_exists( $path );
	}

	/**
	 * Determine whether a path is a regular file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return is_file( $path );
	}

	/**
	 * Write a file.
	 *
	 * @param string $path Path.
	 * @param string $contents Contents.
	 * @return bool
	 */
	public function write( string $path, string $contents ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Bounded write of plugin-owned diagnostic file after path validation.
		return false !== file_put_contents( $path, $contents );
	}

	/**
	 * Rename a file.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function rename( string $source, string $destination ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Bounded rename of plugin-owned diagnostic file after path validation.
		return rename( $source, $destination );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete( string $path ): bool {
		if ( function_exists( 'wp_delete_file' ) ) {
			return \wp_delete_file( $path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Fallback for plugin-owned diagnostic file cleanup.
		return unlink( $path );
	}

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- Bounded read of plugin-owned diagnostic file after path validation.
		$size = filesize( $path );

		return false === $size ? null : (int) $size;
	}
}
