<?php
/**
 * Media attachment presenter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;

/**
 * Builds lightweight Media Library summaries from `_hwlio_status`.
 */
final class MediaAttachmentPresenter {

	/**
	 * Action-availability policy.
	 *
	 * @var AttachmentActionAvailability
	 */
	private $availability;

	/**
	 * Create the presenter.
	 *
	 * @param AttachmentActionAvailability $availability Action-availability policy.
	 */
	public function __construct( AttachmentActionAvailability $availability ) {
		$this->availability = $availability;
	}

	/**
	 * Present one attachment summary.
	 *
	 * @param int              $attachment_id Attachment ID.
	 * @param AttachmentStatus $status Status summary.
	 * @param bool             $can_manage Whether the current user can act on the attachment.
	 * @param bool             $exclusion_allowed Whether per-attachment exclusion is allowed.
	 * @return MediaAttachmentSummary
	 */
	public function present(
		int $attachment_id,
		AttachmentStatus $status,
		bool $can_manage,
		bool $exclusion_allowed
	): MediaAttachmentSummary {
		return new MediaAttachmentSummary(
			$attachment_id,
			$status->state(),
			$this->status_label( $status->state() ),
			$status->formats_ready(),
			$status->excluded(),
			$this->availability->actions_for( $status, $can_manage, $exclusion_allowed ),
			in_array( $status->state(), array( AttachmentStatus::STATE_QUEUED, AttachmentStatus::STATE_PROCESSING, AttachmentStatus::STATE_STALE ), true )
		);
	}

	/**
	 * Translate the small status label.
	 *
	 * @param string $state State slug.
	 * @return string
	 */
	private function status_label( string $state ): string {
		$labels = array(
			AttachmentStatus::STATE_UNPROCESSED => 'Unprocessed',
			AttachmentStatus::STATE_QUEUED      => 'Queued',
			AttachmentStatus::STATE_PROCESSING  => 'Processing',
			AttachmentStatus::STATE_PARTIAL     => 'Partially optimized',
			AttachmentStatus::STATE_OPTIMIZED   => 'Optimized',
			AttachmentStatus::STATE_FAILED      => 'Failed',
			AttachmentStatus::STATE_STALE       => 'Stale',
			AttachmentStatus::STATE_EXCLUDED    => 'Excluded',
			AttachmentStatus::STATE_SKIPPED     => 'Skipped',
		);

		$text = $labels[ $state ] ?? $labels[ AttachmentStatus::STATE_UNPROCESSED ];

		if ( function_exists( '__' ) ) {
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}
}
