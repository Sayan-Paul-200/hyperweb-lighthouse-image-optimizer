<?php
/**
 * File-backed animation detector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Parses GIF and WebP containers just enough to avoid flattening animated sources.
 */
final class FileAnimationDetector implements AnimationDetectorInterface {

	/**
	 * Maximum bytes to read for a conservative animation scan.
	 *
	 * @var int
	 */
	private const MAX_SCAN_BYTES = 16777216;

	/**
	 * Detect animation state for a source file.
	 *
	 * @param string $absolute_path Absolute path.
	 * @param string $mime_type Detected MIME type.
	 * @return AnimationStatus
	 */
	public function detect( string $absolute_path, string $mime_type ): AnimationStatus {
		$mime_type = strtolower( trim( $mime_type ) );

		if ( SourceMimePolicy::MIME_GIF !== $mime_type && SourceMimePolicy::MIME_WEBP !== $mime_type ) {
			return AnimationStatus::not_applicable( $mime_type );
		}

		$contents = $this->read_scan_bytes( $absolute_path );

		if ( null === $contents ) {
			return AnimationStatus::unknown( $mime_type, 'animation_read_failed' );
		}

		if ( SourceMimePolicy::MIME_GIF === $mime_type ) {
			return $this->detect_gif_animation( $contents, $mime_type );
		}

		return $this->detect_webp_animation( $contents, $mime_type );
	}

	/**
	 * Read bounded bytes for animation scanning.
	 *
	 * @param string $path Path.
	 * @return string|null
	 */
	private function read_scan_bytes( string $path ): ?string {
		if ( ! is_readable( $path ) || ! is_file( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- Read-only bounded file fact for animation detection.
		$size = filesize( $path );

		if ( false === $size || self::MAX_SCAN_BYTES < (int) $size ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read-only bounded scan after source validation.
		$contents = file_get_contents( $path );

		return is_string( $contents ) ? $contents : null;
	}

	/**
	 * Detect GIF animation by counting image descriptor blocks.
	 *
	 * @param string $contents File contents.
	 * @param string $mime_type MIME type.
	 * @return AnimationStatus
	 */
	private function detect_gif_animation( string $contents, string $mime_type ): AnimationStatus {
		$length = strlen( $contents );

		if ( 13 > $length || ! in_array( substr( $contents, 0, 6 ), array( 'GIF87a', 'GIF89a' ), true ) ) {
			return AnimationStatus::unknown( $mime_type, 'invalid_gif_header' );
		}

		$cursor = 13;
		$packed = ord( $contents[10] );

		if ( 0 !== ( $packed & 0x80 ) ) {
			$cursor += 3 * ( 2 ** ( ( $packed & 0x07 ) + 1 ) );
		}

		$frames = 0;

		while ( $cursor < $length ) {
			$block = ord( $contents[ $cursor ] );
			++$cursor;

			if ( 0x2c === $block ) {
				++$frames;

				if ( 1 < $frames ) {
					return AnimationStatus::animated( $mime_type, 'animated_gif' );
				}

				if ( ! $this->skip_gif_image_data( $contents, $cursor ) ) {
					return AnimationStatus::unknown( $mime_type, 'truncated_gif_image' );
				}

				continue;
			}

			if ( 0x21 === $block ) {
				if ( $cursor >= $length ) {
					return AnimationStatus::unknown( $mime_type, 'truncated_gif_extension' );
				}

				++$cursor;

				if ( ! $this->skip_gif_sub_blocks( $contents, $cursor ) ) {
					return AnimationStatus::unknown( $mime_type, 'truncated_gif_extension' );
				}

				continue;
			}

			if ( 0x3b === $block ) {
				return 1 === $frames
					? AnimationStatus::not_animated( $mime_type )
					: AnimationStatus::unknown( $mime_type, 'gif_without_image' );
			}

			return AnimationStatus::unknown( $mime_type, 'unexpected_gif_block' );
		}

		return AnimationStatus::unknown( $mime_type, 'truncated_gif' );
	}

	/**
	 * Skip a GIF image descriptor, optional local color table, and image data.
	 *
	 * @param string $contents File contents.
	 * @param int    $cursor Cursor.
	 * @return bool
	 */
	private function skip_gif_image_data( string $contents, int &$cursor ): bool {
		$length = strlen( $contents );

		if ( $cursor + 9 > $length ) {
			return false;
		}

		$descriptor = substr( $contents, $cursor, 9 );
		$cursor    += 9;
		$packed     = ord( $descriptor[8] );

		if ( 0 !== ( $packed & 0x80 ) ) {
			$cursor += 3 * ( 2 ** ( ( $packed & 0x07 ) + 1 ) );
		}

		if ( $cursor >= $length ) {
			return false;
		}

		++$cursor;

		return $this->skip_gif_sub_blocks( $contents, $cursor );
	}

	/**
	 * Skip GIF data sub-blocks.
	 *
	 * @param string $contents File contents.
	 * @param int    $cursor Cursor.
	 * @return bool
	 */
	private function skip_gif_sub_blocks( string $contents, int &$cursor ): bool {
		$length = strlen( $contents );

		while ( $cursor < $length ) {
			$size = ord( $contents[ $cursor ] );
			++$cursor;

			if ( 0 === $size ) {
				return true;
			}

			$cursor += $size;

			if ( $cursor > $length ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Detect WebP animation from RIFF/WebP chunks.
	 *
	 * @param string $contents File contents.
	 * @param string $mime_type MIME type.
	 * @return AnimationStatus
	 */
	private function detect_webp_animation( string $contents, string $mime_type ): AnimationStatus {
		$length = strlen( $contents );

		if ( 12 > $length || 'RIFF' !== substr( $contents, 0, 4 ) || 'WEBP' !== substr( $contents, 8, 4 ) ) {
			return AnimationStatus::unknown( $mime_type, 'invalid_webp_header' );
		}

		$cursor    = 12;
		$has_image = false;

		while ( $cursor + 8 <= $length ) {
			$chunk_type = substr( $contents, $cursor, 4 );
			$chunk_size = $this->little_endian_uint32( substr( $contents, $cursor + 4, 4 ) );
			$cursor    += 8;

			if ( null === $chunk_size || $cursor + $chunk_size > $length ) {
				return AnimationStatus::unknown( $mime_type, 'truncated_webp_chunk' );
			}

			$payload = substr( $contents, $cursor, $chunk_size );

			if ( 'ANIM' === $chunk_type || 'ANMF' === $chunk_type ) {
				return AnimationStatus::animated( $mime_type, 'animated_webp' );
			}

			if ( 'VP8X' === $chunk_type ) {
				$has_image = true;

				if ( 10 > strlen( $payload ) ) {
					return AnimationStatus::unknown( $mime_type, 'truncated_webp_vp8x' );
				}

				if ( 0 !== ( ord( $payload[0] ) & 0x02 ) ) {
					return AnimationStatus::animated( $mime_type, 'animated_webp' );
				}
			}

			if ( 'VP8 ' === $chunk_type || 'VP8L' === $chunk_type ) {
				$has_image = true;
			}

			$cursor += $chunk_size + ( $chunk_size % 2 );
		}

		return $has_image
			? AnimationStatus::not_animated( $mime_type )
			: AnimationStatus::unknown( $mime_type, 'webp_without_image' );
	}

	/**
	 * Decode an unsigned little-endian 32-bit integer.
	 *
	 * @param string $bytes Bytes.
	 * @return int|null
	 */
	private function little_endian_uint32( string $bytes ): ?int {
		if ( 4 !== strlen( $bytes ) ) {
			return null;
		}

		$unpacked = unpack( 'Vvalue', $bytes );

		if ( ! is_array( $unpacked ) || ! isset( $unpacked['value'] ) ) {
			return null;
		}

		return (int) $unpacked['value'];
	}
}
