<?php
/**
 * Admin bootstrap configuration value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Carries the typed client bootstrap payload for plugin screens.
 */
final class AdminBootstrapConfig {

	/**
	 * REST namespace for admin UI requests.
	 *
	 * @var string
	 */
	public const REST_NAMESPACE = 'hwlio/v1/';

	/**
	 * Current screen context.
	 *
	 * @var AdminScreenContext
	 */
	private $context;

	/**
	 * REST root URL.
	 *
	 * @var string
	 */
	private $rest_root;

	/**
	 * REST nonce.
	 *
	 * @var string
	 */
	private $rest_nonce;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Notice manager.
	 *
	 * @var NoticeManager
	 */
	private $notice_manager;

	/**
	 * Create the bootstrap payload.
	 *
	 * @param AdminScreenContext $context Screen context.
	 * @param string             $rest_root REST root URL.
	 * @param string             $rest_nonce REST nonce.
	 * @param string             $version Plugin version.
	 * @param NoticeManager      $notice_manager Notice manager.
	 */
	public function __construct(
		AdminScreenContext $context,
		string $rest_root,
		string $rest_nonce,
		string $version,
		NoticeManager $notice_manager
	) {
		$this->context        = $context;
		$this->rest_root      = $rest_root;
		$this->rest_nonce     = $rest_nonce;
		$this->version        = $version;
		$this->notice_manager = $notice_manager;
	}

	/**
	 * Convert the payload to an array for JSON bootstrap.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'pageSlug'   => Menu::MENU_SLUG,
			'screenId'   => $this->context->screen_id(),
			'currentTab' => $this->context->current_tab(),
			'version'    => $this->version,
			'rest'       => array(
				'root'  => $this->rest_root,
				'nonce' => $this->rest_nonce,
			),
			'polling'    => array(
				'statusMs'   => 15000,
				'progressMs' => 5000,
			),
			'bulk'       => array(
				'jobsScanRoute'    => '/jobs/scan',
				'jobsQueueRoute'   => '/jobs/queue',
				'jobsRetryRoute'   => '/jobs/retry',
				'jobsPauseRoute'   => '/jobs/pause',
				'jobsResumeRoute'  => '/jobs/resume',
				'jobsPendingRoute' => '/jobs/pending',
				'attachmentsRoute' => '/attachments',
				'scanIntervalMs'   => 350,
				'queueIntervalMs'  => 500,
				'previewPageSize' => 20,
				'storageKey'      => 'hwlioBulkScanToken',
				'queueModeKey'    => 'hwlioBulkQueueMode',
			),
			'diagnostics' => array(
				'route' => '/diagnostics',
			),
			'logs'       => array(
				'route'              => '/logs',
				'retentionRoute'     => '/logs/retention',
				'defaultPerPage'     => 20,
				'maxPerPage'         => 100,
				'deleteIntervalMs'   => 250,
			),
			'selectors'  => $this->notice_manager->selectors(),
			'strings'    => array(
				'bootstrapError'       => $this->translate( 'The admin client could not initialize on this screen.' ),
				'missingMount'         => $this->translate( 'The admin client mount point is missing from this screen.' ),
				'missingApiFetch'      => $this->translate( 'The WordPress REST client is unavailable on this screen.' ),
				'requestError'         => $this->translate( 'A plugin request failed before it could complete.' ),
				'loading'              => $this->translate( 'Loading...' ),
				'noticeError'          => $this->translate( 'An unexpected admin error occurred.' ),
				'dashboardLoadError'   => $this->translate( 'The dashboard data could not be loaded.' ),
				'dashboardUpdated'     => $this->translate( 'Dashboard statistics have been refreshed.' ),
				'recalculateQueued'    => $this->translate( 'Statistics recalculation was queued successfully.' ),
				'recalculatePending'   => $this->translate( 'A statistics recalculation request is already pending.' ),
				'recalculateBusy'      => $this->translate( 'Statistics recalculation is currently running in the background.' ),
				'recalculateAction'    => $this->translate( 'Recalculate Statistics' ),
				'recalculateWorking'   => $this->translate( 'Recalculating...' ),
				'cachePending'         => $this->translate( 'Statistics recalculation is pending.' ),
				'cacheReady'           => $this->translate( 'Statistics cache last updated:' ),
				'cacheUnknown'         => $this->translate( 'Statistics cache has not been generated yet.' ),
				'queueAvailable'       => $this->translate( 'Available' ),
				'queueUnavailable'     => $this->translate( 'Unavailable' ),
				'noneDetected'         => $this->translate( 'None detected.' ),
				'noFailures'           => $this->translate( 'No recent warning or error entries were found.' ),
				'noConflicts'          => $this->translate( 'No conservative conflict warnings are active right now.' ),
				'unsupported'          => $this->translate( 'Unsupported' ),
				'notReady'             => $this->translate( 'Not ready' ),
				'bulkStart'            => $this->translate( 'Run Dry-Run Scan' ),
				'bulkScanning'         => $this->translate( 'Dry-run scan in progress.' ),
				'bulkCompleted'        => $this->translate( 'Dry-run scan completed.' ),
				'bulkResumed'          => $this->translate( 'Resumed the latest bulk dry-run session for this browser tab.' ),
				'bulkEmpty'            => $this->translate( 'No eligible candidates were found for the current dry-run filters.' ),
				'bulkPreviewEmpty'     => $this->translate( 'No preview items are available for this page.' ),
				'bulkPreviewError'     => $this->translate( 'The bulk candidate preview could not be loaded.' ),
				'bulkScanError'        => $this->translate( 'The dry-run scan could not be completed.' ),
				'bulkExcludedSkipped'  => $this->translate( 'Excluded attachments are skipped during bulk dry-run scans in this subphase.' ),
				'bulkDeferredQueue'    => $this->translate( 'Bulk queue controls operate only on completed dry-run scan sessions.' ),
				'bulkPageLabel'        => $this->translate( 'Preview page' ),
				'bulkPrevious'         => $this->translate( 'Previous' ),
				'bulkNext'             => $this->translate( 'Next' ),
				'bulkQueueAction'      => $this->translate( 'Queue Current Scan Results' ),
				'bulkRetryAction'      => $this->translate( 'Retry Failed Current Scan Results' ),
				'bulkPauseAction'      => $this->translate( 'Pause Queue' ),
				'bulkResumeAction'     => $this->translate( 'Resume Queue' ),
				'bulkCancelAction'     => $this->translate( 'Cancel Pending Jobs' ),
				'bulkQueueRunning'     => $this->translate( 'Bulk queueing is in progress.' ),
				'bulkRetryRunning'     => $this->translate( 'Bulk retry queueing is in progress.' ),
				'bulkQueueComplete'    => $this->translate( 'Bulk queueing is complete.' ),
				'bulkRetryComplete'    => $this->translate( 'Bulk retry queueing is complete.' ),
				'bulkQueuePaused'      => $this->translate( 'Attachment processing is paused. Queue continuation will wait until resumed.' ),
				'bulkPauseBeforeCancel' => $this->translate( 'Pause processing before canceling pending jobs so currently running work can finish safely.' ),
				'bulkPauseSuccess'     => $this->translate( 'Attachment processing is now paused.' ),
				'bulkResumeSuccess'    => $this->translate( 'Attachment processing has resumed.' ),
				'bulkCancelSuccess'    => $this->translate( 'Pending plugin-owned attachment jobs were canceled.' ),
				'bulkCancelError'      => $this->translate( 'Pending attachment jobs could not be canceled cleanly.' ),
				'bulkQueueError'       => $this->translate( 'The bulk queue request could not be completed.' ),
				'bulkRetryError'       => $this->translate( 'The bulk retry request could not be completed.' ),
				'bulkControlPending'   => $this->translate( 'Pending jobs' ),
				'bulkControlRunning'   => $this->translate( 'Running jobs' ),
				'diagnosticsLoadError' => $this->translate( 'Diagnostics could not be loaded right now.' ),
				'diagnosticsRefreshAction' => $this->translate( 'Refresh Diagnostics' ),
				'diagnosticsRefreshing' => $this->translate( 'Refreshing diagnostics...' ),
				'diagnosticsCopied'    => $this->translate( 'Diagnostic code copied.' ),
				'diagnosticsNoResults' => $this->translate( 'No structured diagnostic results are available right now.' ),
				'diagnosticsGroupPass' => $this->translate( 'Passing Checks' ),
				'diagnosticsGroupWarning' => $this->translate( 'Warnings' ),
				'diagnosticsGroupFail' => $this->translate( 'Failures' ),
				'diagnosticsGroupInfo' => $this->translate( 'Informational Checks' ),
				'detailsLabel'         => $this->translate( 'Details' ),
				'copyCodeAction'       => $this->translate( 'Copy Code' ),
				'copyCodeFallback'     => $this->translate( 'The code could not be copied automatically. You can still copy it manually.' ),
				'logsLoadError'        => $this->translate( 'Logs could not be loaded right now.' ),
				'logsEmpty'            => $this->translate( 'No log rows match the current filters.' ),
				'logsPageLabel'        => $this->translate( 'Log page' ),
				'logsRefreshAction'    => $this->translate( 'Refresh Logs' ),
				'logsSaveRetentionAction' => $this->translate( 'Save Retention' ),
				'logsClearAction'      => $this->translate( 'Clear All Logs' ),
				'logsRetentionSaved'   => $this->translate( 'Log retention days were saved.' ),
				'logsRetentionSaving'  => $this->translate( 'Saving retention...' ),
				'logsClearConfirm'     => $this->translate( 'Clear all plugin-owned log rows now? This runs in bounded batches and cannot be undone.' ),
				'logsDeleting'         => $this->translate( 'Deleting logs in bounded batches...' ),
				'logsDeleted'          => $this->translate( 'Plugin logs were cleared.' ),
				'logsDeleteProgress'   => $this->translate( 'Deleted log rows so far:' ),
				'logsDeleteError'      => $this->translate( 'Plugin logs could not be deleted right now.' ),
			),
		);
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}
}
