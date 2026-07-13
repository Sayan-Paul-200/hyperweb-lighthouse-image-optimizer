<?php
/**
 * Fake post editor runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\PostEditorRuntimeInterface;

/**
 * Records post-editor runtime calls for unit tests.
 */
final class FakePostEditorRuntime implements PostEditorRuntimeInterface {

	/**
	 * Recorded meta box calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $meta_boxes = array();

	/**
	 * Recorded capability checks.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $capability_checks = array();

	/**
	 * Whether the current user may edit the post.
	 *
	 * @var bool
	 */
	public $can = true;

	/**
	 * Nonce value produced by the runtime.
	 *
	 * @var string
	 */
	public $nonce = 'critical-image-nonce';

	/**
	 * Whether the submitted nonce is valid.
	 *
	 * @var bool
	 */
	public $nonce_valid = true;

	/**
	 * Current post type.
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * Whether the current save is an autosave.
	 *
	 * @var bool
	 */
	public $autosave = false;

	/**
	 * Whether the current save is a revision.
	 *
	 * @var bool
	 */
	public $revision = false;

	/**
	 * Valid image attachments.
	 *
	 * @var array<int,bool>
	 */
	public $images = array( 123 => true );

	/**
	 * Attachment titles.
	 *
	 * @var array<int,string>
	 */
	public $titles = array( 123 => 'Hero image' );

	/**
	 * Attachment preview URLs.
	 *
	 * @var array<int,string>
	 */
	public $previews = array( 123 => 'https://example.test/hero-thumb.jpg' );

	/**
	 * Whether the media picker was enqueued.
	 *
	 * @var bool
	 */
	public $media_enqueued = false;

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
		$this->meta_boxes[] = array(
			'id'       => $id,
			'title'    => $title,
			'callback' => $callback,
			'screen'   => $screen,
			'context'  => $context,
			'priority' => $priority,
		);
	}

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool {
		$this->capability_checks[] = array(
			'capability' => $capability,
			'object_id'  => $object_id,
		);

		return $this->can;
	}

	/**
	 * Create one nonce value.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string {
		unset( $action );

		return $this->nonce;
	}

	/**
	 * Verify one nonce value.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public function verify_nonce( string $nonce, string $action ): bool {
		unset( $action );

		return $this->nonce_valid && $nonce === $this->nonce;
	}

	/**
	 * Get the current editor post type.
	 *
	 * @return string
	 */
	public function current_post_type(): string {
		return $this->post_type;
	}

	/**
	 * Whether one post ID is an autosave.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_autosave( int $post_id ): bool {
		unset( $post_id );

		return $this->autosave;
	}

	/**
	 * Whether one post ID is a revision.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_revision( int $post_id ): bool {
		unset( $post_id );

		return $this->revision;
	}

	/**
	 * Whether one attachment ID points to an image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return ! empty( $this->images[ $attachment_id ] );
	}

	/**
	 * Get one attachment title.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_title( int $attachment_id ): string {
		return $this->titles[ $attachment_id ] ?? '';
	}

	/**
	 * Get one attachment preview URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function attachment_preview_url( int $attachment_id ): string {
		return $this->previews[ $attachment_id ] ?? '';
	}

	/**
	 * Enqueue the WordPress media picker.
	 *
	 * @return void
	 */
	public function enqueue_media(): void {
		$this->media_enqueued = true;
	}
}
