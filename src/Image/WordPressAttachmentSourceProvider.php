<?php
/**
 * WordPress attachment source provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Reads source attachment facts through WordPress APIs.
 */
final class WordPressAttachmentSourceProvider implements AttachmentSourceProviderInterface {

	/**
	 * Get the current attached display file.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attached_file( int $attachment_id ): ?string {
		if ( ! function_exists( 'get_attached_file' ) ) {
			return null;
		}

		$file = \get_attached_file( $attachment_id );

		return is_string( $file ) && '' !== trim( $file ) ? $file : null;
	}

	/**
	 * Get attachment metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public function metadata( int $attachment_id ): ?array {
		if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
			return null;
		}

		$metadata = \wp_get_attachment_metadata( $attachment_id );

		return is_array( $metadata ) ? $metadata : null;
	}

	/**
	 * Get uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return null;
		}

		$uploads = \wp_upload_dir( null, false );

		if ( ! is_array( $uploads ) ) {
			return null;
		}

		if ( is_string( $uploads['error'] ) && '' !== $uploads['error'] ) {
			return null;
		}

		return '' !== trim( $uploads['basedir'] )
			? $uploads['basedir']
			: null;
	}
}
