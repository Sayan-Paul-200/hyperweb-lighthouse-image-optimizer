<?php
/**
 * CLI cleanup dry-run runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScannerRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;

/**
 * Runs bounded attachment cleanup dry-run scans for WP-CLI.
 */
final class CliCleanupDryRunService {

	/**
	 * Bulk scanner runtime.
	 *
	 * @var BulkScannerRuntimeInterface
	 */
	private $runtime;

	/**
	 * Attachment cleanup service.
	 *
	 * @var AttachmentCleanup
	 */
	private $cleanup;

	/**
	 * Create the service.
	 *
	 * @param BulkScannerRuntimeInterface $runtime Scanner runtime.
	 * @param AttachmentCleanup           $cleanup Cleanup service.
	 */
	public function __construct( BulkScannerRuntimeInterface $runtime, AttachmentCleanup $cleanup ) {
		$this->runtime = $runtime;
		$this->cleanup = $cleanup;
	}

	/**
	 * Run cleanup dry-run across bounded attachment pages.
	 *
	 * @param BulkScanFilters $filters Filters.
	 * @param callable|null   $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function dry_run( BulkScanFilters $filters, ?callable $progress = null ): CliOperationResult {
		$cursor           = 0;
		$pages            = 0;
		$processed_images = 0;
		$scanned          = 0;
		$aggregate        = AttachmentCleanupResult::success();

		while ( true ) {
			$ids = $this->runtime->scan_page( $filters, $cursor, BulkScanService::SCAN_PAGE_SIZE );

			if ( array() === $ids ) {
				break;
			}

			++$pages;
			$cursor  = max( $ids );
			$scanned += count( $ids );

			foreach ( $ids as $attachment_id ) {
				if ( ! $this->runtime->attachment_is_image( $attachment_id ) ) {
					continue;
				}

				++$processed_images;
				$aggregate = AttachmentCleanupResult::combine(
					$aggregate,
					$this->cleanup->dry_run_orphan_reconciliation( $attachment_id )
				);
			}

			if ( null !== $progress ) {
				call_user_func(
					$progress,
					sprintf(
						'Cleanup dry-run progress: pages=%d scanned=%d images=%d orphan_files=%d',
						$pages,
						$scanned,
						$processed_images,
						$aggregate->orphan_files()
					)
				);
			}
		}

		$payload = array(
			'filters'  => $filters->to_array(),
			'progress' => array(
				'pages'            => $pages,
				'cursor'           => $cursor,
				'scanned'          => $scanned,
				'processed_images' => $processed_images,
				'complete'         => true,
			),
			'summary'  => $aggregate->to_array(),
		);

		return ( ! $aggregate->is_successful() || $aggregate->has_warnings() )
			? CliOperationResult::degraded( 'cleanup-dry-run', $payload, $aggregate->codes(), $aggregate->messages() )
			: CliOperationResult::success( 'cleanup-dry-run', $payload, $aggregate->codes(), $aggregate->messages() );
	}
}
