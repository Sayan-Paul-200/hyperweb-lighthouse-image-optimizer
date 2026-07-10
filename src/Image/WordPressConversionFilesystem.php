<?php
/**
 * WordPress conversion filesystem adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Performs bounded filesystem operations for generated derivatives.
 */
final class WordPressConversionFilesystem implements ConversionFilesystemInterface {

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
	 * Determine whether path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return file_exists( $path );
	}

	/**
	 * Determine whether path is a regular file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return is_file( $path );
	}

	/**
	 * Determine whether path is readable.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_readable( string $path ): bool {
		return is_readable( $path );
	}

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- Bounded read after conversion path validation.
		$size = filesize( $path );

		return false === $size ? null : (int) $size;
	}

	/**
	 * Detect MIME type.
	 *
	 * @param string $path Path.
	 * @return string|null
	 */
	public function mime_type( string $path ): ?string {
		if ( ! function_exists( 'wp_get_image_mime' ) ) {
			return null;
		}

		try {
			$mime_type = \wp_get_image_mime( $path );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return null;
		}

		return is_string( $mime_type ) && '' !== trim( $mime_type ) ? strtolower( trim( $mime_type ) ) : null;
	}

	/**
	 * Read image dimensions.
	 *
	 * @param string $path Path.
	 * @return array{width:int,height:int}|null
	 */
	public function dimensions( string $path ): ?array {
		if ( ! function_exists( 'wp_getimagesize' ) ) {
			return null;
		}

		try {
			$dimensions = \wp_getimagesize( $path );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return null;
		}

		if ( ! is_array( $dimensions ) || ! isset( $dimensions[0], $dimensions[1] ) ) {
			return null;
		}

		$width  = (int) $dimensions[0];
		$height = (int) $dimensions[1];

		if ( 0 >= $width || 0 >= $height ) {
			return null;
		}

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete( string $path ): bool {
		if ( ! $this->exists( $path ) ) {
			return true;
		}

		if ( function_exists( 'wp_delete_file' ) ) {
			\wp_delete_file( $path );
			clearstatcache( true, $path );

			return ! file_exists( $path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Fallback cleanup for plugin-owned derivative after path validation.
		$deleted = unlink( $path );
		clearstatcache( true, $path );

		return $deleted || ! file_exists( $path );
	}

	/**
	 * Move a file into place.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Same-directory atomic move for plugin-owned derivative after path validation.
		return rename( $source, $destination );
	}
}
