<?php
/**
 * WordPress post editor runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\PostEditor;

/**
 * Calls WordPress post-editor APIs for the critical-image controls.
 */
final class WordPressPostEditorRuntime implements PostEditorRuntimeInterface {

	/**
	 * Register a meta box.
	 *
	 * @param string   $id Meta box ID.
	 * @param string   $title Meta box title.
	 * @param callable $callback Render callback.
	 * @param string   $screen Screen name.
	 * @param string   $context Screen context.
	 * @param string   $priority Meta box priority.
	 * @return void
	 */
	public function add_meta_box( string $id, string $title, callable $callback, string $screen, string $context, string $priority ): void {
		\add_meta_box( $id, $title, $callback, $screen, $context, $priority );
	}

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
	 * Create one nonce value.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string {
		return \wp_create_nonce( $action );
	}

	/**
	 * Verify one nonce value.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public function verify_nonce( string $nonce, string $action ): bool {
		return 1 === (int) \wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Get the current editor post type.
	 *
	 * @return string
	 */
	public function current_post_type(): string {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = \get_current_screen();

			if ( is_object( $screen ) && property_exists( $screen, 'post_type' ) ) {
				$post_type = $screen->post_type;

				if ( is_string( $post_type ) ) {
					return $post_type;
				}
			}
		}

		return '';
	}

	/**
	 * Whether one post ID is an autosave.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_autosave( int $post_id ): bool {
		return false !== \wp_is_post_autosave( max( 0, $post_id ) );
	}

	/**
	 * Whether one post ID is a revision.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_revision( int $post_id ): bool {
		return false !== \wp_is_post_revision( max( 0, $post_id ) );
	}

	/**
	 * Whether one attachment ID points to an image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return $attachment_id > 0 && function_exists( 'wp_attachment_is_image' ) && (bool) \wp_attachment_is_image( $attachment_id );
	}

	/**
	 * Get one attachment title.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_title( int $attachment_id ): string {
		return (string) \get_the_title( max( 0, $attachment_id ) );
	}

	/**
	 * Get one attachment preview URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_preview_url( int $attachment_id ): string {
		if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return '';
		}

		return (string) \wp_get_attachment_image_url( max( 0, $attachment_id ), 'thumbnail' );
	}

	/**
	 * Enqueue the WordPress media picker.
	 *
	 * @return void
	 */
	public function enqueue_media(): void {
		\wp_enqueue_media();
	}
}
