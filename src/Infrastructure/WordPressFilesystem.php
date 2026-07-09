<?php
/**
 * WordPress filesystem adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Uses WordPress-safe file helpers where available.
 */
final class WordPressFilesystem implements FilesystemInterface {

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return file_exists( $path );
	}

	/**
	 * Determine whether a path is a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return is_file( $path );
	}

	/**
	 * Determine whether a path is a symbolic link.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_link( string $path ): bool {
		return is_link( $path );
	}

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Absolute path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$realpath = realpath( $path );

		return false === $realpath ? '' : $realpath;
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function delete( string $path ): bool {
		if ( function_exists( 'wp_delete_file' ) ) {
			return \wp_delete_file( $path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Fallback only when wp_delete_file() is unavailable.
		return unlink( $path );
	}
}
