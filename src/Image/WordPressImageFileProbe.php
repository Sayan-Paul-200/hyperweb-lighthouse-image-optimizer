<?php
/**
 * WordPress image file probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Reads source file facts without modifying files.
 */
final class WordPressImageFileProbe implements ImageFileProbeInterface {

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
	 * Determine whether a path is a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return is_file( $path );
	}

	/**
	 * Determine whether a path is readable.
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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- Read-only file fact after source path validation.
		$size = filesize( $path );

		return false === $size ? null : (int) $size;
	}

	/**
	 * Get modified time.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function modified_time( string $path ): ?int {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filemtime -- Read-only file fact after source path validation.
		$modified_time = filemtime( $path );

		return false === $modified_time ? null : (int) $modified_time;
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
}
