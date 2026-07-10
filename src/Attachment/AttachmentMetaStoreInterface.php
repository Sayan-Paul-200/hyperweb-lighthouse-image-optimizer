<?php
/**
 * Attachment meta store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Wraps WordPress attachment meta persistence for testable repositories.
 */
interface AttachmentMetaStoreInterface {

	/**
	 * Get an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( int $attachment_id, string $key, $fallback = null );

	/**
	 * Update an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function update( int $attachment_id, string $key, $value ): bool;

	/**
	 * Add a unique attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function add_unique( int $attachment_id, string $key, $value ): bool;

	/**
	 * Delete an attachment meta value.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @return bool
	 */
	public function delete( int $attachment_id, string $key ): bool;

	/**
	 * Delete an attachment meta value only when the stored value matches.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function delete_value( int $attachment_id, string $key, $value ): bool;
}
