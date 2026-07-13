<?php
/**
 * Fake Media Library runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryRuntimeInterface;

/**
 * Records Media Library runtime checks for unit tests.
 */
final class FakeMediaLibraryRuntime implements MediaLibraryRuntimeInterface {

	/**
	 * Capability map.
	 *
	 * @var array<string,bool>
	 */
	public $capabilities = array(
		'upload_files' => true,
		'edit_post:123' => true,
	);

	/**
	 * Attachment existence map.
	 *
	 * @var array<int,bool>
	 */
	public $attachments = array(
		123 => true,
	);

	/**
	 * Attachment image map.
	 *
	 * @var array<int,bool>
	 */
	public $images = array(
		123 => true,
	);

	/**
	 * Current screen ID.
	 *
	 * @var string
	 */
	public $screen_id = 'upload';

	/**
	 * Current post type.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * Current post ID.
	 *
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool {
		$key = null === $object_id ? $capability : $capability . ':' . $object_id;

		if ( array_key_exists( $key, $this->capabilities ) ) {
			return $this->capabilities[ $key ];
		}

		return $this->capabilities[ $capability ] ?? false;
	}

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool {
		return $this->attachments[ $attachment_id ] ?? false;
	}

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return $this->images[ $attachment_id ] ?? false;
	}

	/**
	 * Get the current screen ID.
	 *
	 * @return string
	 */
	public function current_screen_id(): string {
		return $this->screen_id;
	}

	/**
	 * Get the current post type.
	 *
	 * @return string
	 */
	public function current_post_type(): string {
		return $this->post_type;
	}

	/**
	 * Get the current post ID.
	 *
	 * @return int
	 */
	public function current_post_id(): int {
		return $this->post_id;
	}
}
