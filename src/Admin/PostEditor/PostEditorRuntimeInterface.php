<?php
/**
 * Post editor runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\PostEditor;

/**
 * Describes the WordPress post-editor APIs needed for the critical-image controls.
 */
interface PostEditorRuntimeInterface {

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
	public function add_meta_box( string $id, string $title, callable $callback, string $screen, string $context, string $priority ): void;

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool;

	/**
	 * Create one nonce value.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string;

	/**
	 * Verify one nonce value.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public function verify_nonce( string $nonce, string $action ): bool;

	/**
	 * Get the current editor post type.
	 *
	 * @return string
	 */
	public function current_post_type(): string;

	/**
	 * Whether one post ID is an autosave.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_autosave( int $post_id ): bool;

	/**
	 * Whether one post ID is a revision.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_revision( int $post_id ): bool;

	/**
	 * Whether one attachment ID points to an image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool;

	/**
	 * Get one attachment title.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_title( int $attachment_id ): string;

	/**
	 * Get one attachment preview URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_preview_url( int $attachment_id ): string;

	/**
	 * Enqueue the WordPress media picker.
	 *
	 * @return void
	 */
	public function enqueue_media(): void;
}
