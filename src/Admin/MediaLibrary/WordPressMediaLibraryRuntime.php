<?php
/**
 * WordPress Media Library runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

/**
 * Calls the WordPress APIs needed by the Media Library integration.
 */
final class WordPressMediaLibraryRuntime implements MediaLibraryRuntimeInterface {

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool {
		if ( null === $object_id ) {
			return \current_user_can( $capability );
		}

		return \current_user_can( $capability, $object_id );
	}

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool {
		return null !== \get_post( max( 0, $attachment_id ) );
	}

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return function_exists( 'wp_attachment_is_image' ) && \wp_attachment_is_image( $attachment_id );
	}

	/**
	 * Get the current screen ID when available.
	 *
	 * @return string
	 */
	public function current_screen_id(): string {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return '';
		}

		$screen = \get_current_screen();

		if ( is_object( $screen ) && isset( $screen->id ) && is_string( $screen->id ) ) {
			return $screen->id;
		}

		return '';
	}

	/**
	 * Get the current post type when available.
	 *
	 * @return string
	 */
	public function current_post_type(): string {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = \get_current_screen();

			if ( is_object( $screen ) && isset( $screen->post_type ) && is_string( $screen->post_type ) ) {
				return $screen->post_type;
			}
		}

		$post_id = $this->current_post_id();

		if ( 0 < $post_id ) {
			$type = \get_post_type( $post_id );

			return is_string( $type ) ? $type : '';
		}

		if ( isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen scoping.
			return trim( wp_unslash( $_REQUEST['post_type'] ) );
		}

		return '';
	}

	/**
	 * Get the current post ID from request state when available.
	 *
	 * @return int
	 */
	public function current_post_id(): int {
		if ( ! isset( $_REQUEST['post'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen scoping.
		$value = function_exists( 'wp_unslash' ) ? wp_unslash( $_REQUEST['post'] ) : $_REQUEST['post'];

		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}
}
