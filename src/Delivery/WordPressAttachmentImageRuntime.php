<?php
/**
 * WordPress-backed attachment image runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Isolates WordPress frontend attachment-image runtime calls.
 */
final class WordPressAttachmentImageRuntime implements AttachmentImageRuntimeInterface {

	/**
	 * Whether one attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return $attachment_id > 0 && function_exists( 'wp_attachment_is_image' ) && (bool) \wp_attachment_is_image( $attachment_id );
	}

	/**
	 * Read attachment image metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	public function attachment_metadata( int $attachment_id ): array {
		if ( $attachment_id < 1 || ! function_exists( 'wp_get_attachment_metadata' ) ) {
			return array();
		}

		$metadata = \wp_get_attachment_metadata( $attachment_id );

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Read current request-context flags.
	 *
	 * @return array<string,bool>
	 */
	public function request_context(): array {
		$is_rest = false;

		if ( function_exists( 'wp_is_serving_rest_request' ) ) {
			$is_rest = (bool) \wp_is_serving_rest_request();
		} elseif ( function_exists( 'wp_is_json_request' ) ) {
			$is_rest = (bool) \wp_is_json_request();
		} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$is_rest = true;
		}

		return array(
			'is_admin' => function_exists( 'is_admin' ) && (bool) \is_admin(),
			'is_feed'  => function_exists( 'is_feed' ) && (bool) \is_feed(),
			'is_ajax'  => function_exists( 'wp_doing_ajax' ) && (bool) \wp_doing_ajax(),
			'is_rest'  => $is_rest,
		);
	}

	/**
	 * Determine the most reliable known width for the current attachment image request.
	 *
	 * @param int                $attachment_id Attachment ID.
	 * @param mixed              $size Requested size.
	 * @param mixed              $attr Requested attributes.
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return int|null
	 */
	public function requested_image_width( int $attachment_id, $size, $attr, array $metadata ): ?int {
		unset( $attachment_id );

		if ( is_array( $attr ) && isset( $attr['width'] ) && is_numeric( $attr['width'] ) ) {
			$width = (int) $attr['width'];

			if ( $width > 0 ) {
				return $width;
			}
		}

		if ( is_array( $size ) && isset( $size[0] ) && is_numeric( $size[0] ) ) {
			$width = (int) $size[0];

			if ( $width > 0 ) {
				return $width;
			}
		}

		if ( is_numeric( $size ) ) {
			$width = (int) $size;

			if ( $width > 0 ) {
				return $width;
			}
		}

		if ( is_string( $size ) ) {
			$key = strtolower( trim( $size ) );

			if ( 'full' === $key && isset( $metadata['width'] ) && is_numeric( $metadata['width'] ) ) {
				$width = (int) $metadata['width'];

				if ( $width > 0 ) {
					return $width;
				}
			}

			if (
				isset( $metadata['sizes'] )
				&& is_array( $metadata['sizes'] )
				&& isset( $metadata['sizes'][ $key ] )
				&& is_array( $metadata['sizes'][ $key ] )
				&& isset( $metadata['sizes'][ $key ]['width'] )
				&& is_numeric( $metadata['sizes'][ $key ]['width'] )
			) {
				$width = (int) $metadata['sizes'][ $key ]['width'];

				if ( $width > 0 ) {
					return $width;
				}
			}
		}

		return null;
	}
}
