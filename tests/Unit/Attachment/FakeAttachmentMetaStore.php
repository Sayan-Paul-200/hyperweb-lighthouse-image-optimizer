<?php
/**
 * Fake attachment meta store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;

/**
 * In-memory attachment meta store for repository tests.
 */
final class FakeAttachmentMetaStore implements AttachmentMetaStoreInterface {

	/**
	 * Stored metadata.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $meta = array();

	/**
	 * Update operations.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $updates = array();

	/**
	 * Delete operations.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $deletes = array();

	/**
	 * Whether writes should fail.
	 *
	 * @var bool
	 */
	public $fail_writes = false;

	/**
	 * Get meta.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public function get( int $attachment_id, string $key, $fallback = null ) {
		return array_key_exists( $attachment_id, $this->meta ) && array_key_exists( $key, $this->meta[ $attachment_id ] )
			? $this->meta[ $attachment_id ][ $key ]
			: $fallback;
	}

	/**
	 * Update meta.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	public function update( int $attachment_id, string $key, $value ): bool {
		$this->updates[] = array(
			'attachment_id' => $attachment_id,
			'key'           => $key,
			'value'         => $value,
		);

		if ( $this->fail_writes ) {
			return false;
		}

		if ( ! isset( $this->meta[ $attachment_id ] ) ) {
			$this->meta[ $attachment_id ] = array();
		}

		$this->meta[ $attachment_id ][ $key ] = $value;

		return true;
	}

	/**
	 * Delete meta.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $key Meta key.
	 * @return bool
	 */
	public function delete( int $attachment_id, string $key ): bool {
		$this->deletes[] = array(
			'attachment_id' => $attachment_id,
			'key'           => $key,
		);

		unset( $this->meta[ $attachment_id ][ $key ] );

		return true;
	}

	/**
	 * Whether the fake received core attachment metadata writes.
	 *
	 * @return bool
	 */
	public function wrote_core_metadata(): bool {
		foreach ( $this->updates as $update ) {
			if ( '_wp_attachment_metadata' === $update['key'] ) {
				return true;
			}
		}

		return false;
	}
}
