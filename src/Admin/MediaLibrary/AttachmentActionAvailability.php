<?php
/**
 * Attachment action-availability policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;

/**
 * Resolves valid Media Library actions from the lightweight attachment state.
 */
final class AttachmentActionAvailability {

	public const ACTION_OPTIMIZE     = 'optimize';
	public const ACTION_RETRY        = 'retry';
	public const ACTION_REOPTIMIZE   = 'reoptimize';
	public const ACTION_RECONCILE    = 'reconcile';
	public const ACTION_EXCLUDE      = 'exclude';
	public const ACTION_INCLUDE      = 'include';
	public const ACTION_VIEW_DETAILS = 'view-details';

	/**
	 * Resolve the visible actions for one attachment.
	 *
	 * @param AttachmentStatus $status Attachment status.
	 * @param bool             $can_manage Whether the current user can act on the attachment.
	 * @param bool             $exclusion_allowed Whether per-attachment exclusion is allowed.
	 * @return string[]
	 */
	public function actions_for( AttachmentStatus $status, bool $can_manage, bool $exclusion_allowed ): array {
		if ( ! $can_manage ) {
			return array();
		}

		$actions = array();
		$state   = $status->state();

		if ( AttachmentStatus::STATE_EXCLUDED === $state ) {
			if ( $exclusion_allowed ) {
				$actions[] = self::ACTION_INCLUDE;
			}

			$actions[] = self::ACTION_VIEW_DETAILS;

			return $actions;
		}

		if ( AttachmentStatus::STATE_UNPROCESSED === $state ) {
			$actions[] = self::ACTION_OPTIMIZE;
		}

		if ( in_array( $state, array( AttachmentStatus::STATE_FAILED, AttachmentStatus::STATE_PARTIAL, AttachmentStatus::STATE_STALE ), true ) ) {
			$actions[] = self::ACTION_RETRY;
			$actions[] = self::ACTION_REOPTIMIZE;
			$actions[] = self::ACTION_RECONCILE;
		}

		if ( in_array( $state, array( AttachmentStatus::STATE_OPTIMIZED, AttachmentStatus::STATE_SKIPPED ), true ) ) {
			$actions[] = self::ACTION_REOPTIMIZE;
			$actions[] = self::ACTION_RECONCILE;
		}

		if ( $exclusion_allowed ) {
			$actions[] = self::ACTION_EXCLUDE;
		}

		$actions[] = self::ACTION_VIEW_DETAILS;

		return array_values( array_unique( $actions ) );
	}
}
