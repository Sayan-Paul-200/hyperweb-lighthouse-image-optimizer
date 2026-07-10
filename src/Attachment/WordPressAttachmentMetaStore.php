<?php
/**
 * WordPress attachment meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Persists attachment metadata through WordPress core APIs.
 */
final class WordPressAttachmentMetaStore implements AttachmentMetaStoreInterface {

	/**
	 * Get an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( int $attachment_id, string $key, $fallback = null ) {
		$attachment_id = max( 0, $attachment_id );

		if ( function_exists( 'metadata_exists' ) && ! \metadata_exists( 'post', $attachment_id, $key ) ) {
			return $fallback;
		}

		$value = \get_post_meta( $attachment_id, $key, true );

		return '' === $value ? $fallback : $value;
	}

	/**
	 * Update an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function update( int $attachment_id, string $key, $value ): bool {
		return false !== \update_post_meta( max( 0, $attachment_id ), $key, $value );
	}

	/**
	 * Add a unique attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function add_unique( int $attachment_id, string $key, $value ): bool {
		return false !== \add_post_meta( max( 0, $attachment_id ), $key, $value, true );
	}

	/**
	 * Delete an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @return bool
	 */
	public function delete( int $attachment_id, string $key ): bool {
		return \delete_post_meta( max( 0, $attachment_id ), $key );
	}

	/**
	 * Delete an attachment meta value only when the stored value matches.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function delete_value( int $attachment_id, string $key, $value ): bool {
		return \delete_post_meta( max( 0, $attachment_id ), $key, $value );
	}
}
