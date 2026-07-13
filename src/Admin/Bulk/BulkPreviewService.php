<?php
/**
 * Bulk preview service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentPresenter;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds lightweight paged preview payloads for persisted bulk-scan sessions.
 */
final class BulkPreviewService {

	/**
	 * Session store.
	 *
	 * @var BulkScanSessionStoreInterface
	 */
	private $sessions;

	/**
	 * Scanner runtime.
	 *
	 * @var BulkScannerRuntimeInterface
	 */
	private $runtime;

	/**
	 * Lightweight status reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $statuses;

	/**
	 * Media summary presenter.
	 *
	 * @var MediaAttachmentPresenter
	 */
	private $presenter;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Create the service.
	 *
	 * @param BulkScanSessionStoreInterface $sessions Session store.
	 * @param BulkScannerRuntimeInterface   $runtime Scanner runtime.
	 * @param AttachmentStatusReader        $statuses Lightweight status reader.
	 * @param MediaAttachmentPresenter      $presenter Media summary presenter.
	 * @param SettingsRepositoryInterface   $settings Settings repository.
	 */
	public function __construct(
		BulkScanSessionStoreInterface $sessions,
		BulkScannerRuntimeInterface $runtime,
		AttachmentStatusReader $statuses,
		MediaAttachmentPresenter $presenter,
		SettingsRepositoryInterface $settings
	) {
		$this->sessions  = $sessions;
		$this->runtime   = $runtime;
		$this->statuses  = $statuses;
		$this->presenter = $presenter;
		$this->settings  = $settings;
	}

	/**
	 * Read one owned preview page.
	 *
	 * @param string $token Scan token.
	 * @param int    $owner_user_id Owning user ID.
	 * @param int    $page Page number.
	 * @param int    $per_page Page size.
	 * @return BulkScanResultPage
	 */
	public function preview( string $token, int $owner_user_id, int $page, int $per_page ): BulkScanResultPage {
		$session = $this->sessions->load( $token );

		if ( ! $session instanceof BulkScanSession ) {
			throw new BulkScanSessionNotFoundException( 'Bulk scan session not found.' );
		}

		if ( $session->owner_user_id() !== max( 0, $owner_user_id ) ) {
			throw new BulkScanSessionAccessDeniedException( 'Bulk scan session access denied.' );
		}

		$page     = max( 1, $page );
		$per_page = max( 1, min( 50, $per_page ) );
		$ids      = $this->sessions->read_candidate_page( $session, $page, $per_page );
		$records  = $this->runtime->preview_records( $ids );
		$items    = array();

		foreach ( $ids as $attachment_id ) {
			$summary = $this->presenter->present(
				$attachment_id,
				$this->statuses->read( $attachment_id ),
				false,
				$this->settings->attachment_exclusion_allowed()
			);
			$record  = $records[ $attachment_id ] ?? array();

			$items[] = array(
				'attachmentId'  => $attachment_id,
				'title'         => isset( $record['title'] ) && is_string( $record['title'] ) ? $record['title'] : '',
				'filename'      => isset( $record['filename'] ) && is_string( $record['filename'] ) ? $record['filename'] : '',
				'uploadedAtGmt' => isset( $record['uploaded_at_gmt'] ) && is_string( $record['uploaded_at_gmt'] ) ? $record['uploaded_at_gmt'] : '',
				'state'         => $summary->state(),
				'statusLabel'   => $summary->status_label(),
				'readyFormats'  => $summary->ready_formats(),
				'excluded'      => $summary->excluded(),
			);
		}

		return new BulkScanResultPage(
			$session->token(),
			$page,
			$per_page,
			$session->progress()->candidate_total(),
			$items
		);
	}
}
