<?php
/**
 * Attachment exclusion repository.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Reads exclusion state from plugin-owned attachment meta.
 */
final class AttachmentExclusionRepository implements AttachmentExclusionRepositoryInterface {

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Build the WordPress-backed repository.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( new WordPressAttachmentMetaStore() );
	}

	/**
	 * Create repository.
	 *
	 * @param AttachmentMetaStoreInterface $meta Meta store.
	 */
	public function __construct( AttachmentMetaStoreInterface $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Determine whether an attachment is excluded from automation.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_excluded( int $attachment_id ): bool {
		$attachment_id = max( 0, $attachment_id );
		$raw_excluded  = $this->meta->get( $attachment_id, LifecyclePolicy::META_EXCLUDED, null );

		if ( null !== $raw_excluded ) {
			return $this->truthy( $raw_excluded );
		}

		$raw_status = $this->meta->get( $attachment_id, LifecyclePolicy::META_STATUS, null );
		$status     = AttachmentStatus::from_stored( $raw_status );

		return $status->excluded();
	}

	/**
	 * Determine whether a raw stored value is truthy.
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	private function truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return 1 === (int) $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return false;
	}
}
