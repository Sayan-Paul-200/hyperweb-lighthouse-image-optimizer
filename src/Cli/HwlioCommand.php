<?php
/**
 * Root WP-CLI command.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentDetailsService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DiagnosticsServiceInterface;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusSummaryService;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;

/**
 * Exposes read-only status and diagnostics commands for WP-CLI.
 */
final class HwlioCommand {

	private const OUTPUT_TABLE = 'table';
	private const OUTPUT_JSON  = 'json';

	/**
	 * CLI runtime.
	 *
	 * @var CliRuntimeInterface
	 */
	private $runtime;

	/**
	 * Status service.
	 *
	 * @var StatusSummaryService
	 */
	private $status;

	/**
	 * Diagnostics service.
	 *
	 * @var DiagnosticsServiceInterface
	 */
	private $diagnostics;

	/**
	 * Attachment details service.
	 *
	 * @var AttachmentDetailsService
	 */
	private $attachments;

	/**
	 * Attachment lookup runtime.
	 *
	 * @var AttachmentLookupInterface
	 */
	private $lookup;

	/**
	 * Bulk scan/queue operation service.
	 *
	 * @var CliBulkOperationsService
	 */
	private $bulk;

	/**
	 * Stale reconciliation service.
	 *
	 * @var CliReconcileStaleService
	 */
	private $stale_reconcile;

	/**
	 * Log prune service.
	 *
	 * @var CliLogPruneService
	 */
	private $log_prune;

	/**
	 * Cleanup dry-run service.
	 *
	 * @var CliCleanupDryRunService
	 */
	private $cleanup;

	/**
	 * Create the root command.
	 *
	 * @param CliRuntimeInterface         $runtime CLI runtime.
	 * @param StatusSummaryService        $status Status service.
	 * @param DiagnosticsServiceInterface $diagnostics Diagnostics service.
	 * @param AttachmentDetailsService    $attachments Attachment details service.
	 * @param AttachmentLookupInterface   $lookup Attachment lookup runtime.
	 * @param CliBulkOperationsService    $bulk Bulk operations service.
	 * @param CliReconcileStaleService    $stale_reconcile Stale reconciliation service.
	 * @param CliLogPruneService          $log_prune Log prune service.
	 * @param CliCleanupDryRunService     $cleanup Cleanup dry-run service.
	 */
	public function __construct(
		CliRuntimeInterface $runtime,
		StatusSummaryService $status,
		DiagnosticsServiceInterface $diagnostics,
		AttachmentDetailsService $attachments,
		AttachmentLookupInterface $lookup,
		CliBulkOperationsService $bulk,
		CliReconcileStaleService $stale_reconcile,
		CliLogPruneService $log_prune,
		CliCleanupDryRunService $cleanup
	) {
		$this->runtime         = $runtime;
		$this->status          = $status;
		$this->diagnostics     = $diagnostics;
		$this->attachments     = $attachments;
		$this->lookup          = $lookup;
		$this->bulk            = $bulk;
		$this->stale_reconcile = $stale_reconcile;
		$this->log_prune       = $log_prune;
		$this->cleanup         = $cleanup;
	}

	/**
	 * Output a plugin status summary.
	 *
	 * ## OPTIONS
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function status( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$summary = $this->normalize_status_summary( $this->status->summary() );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( self::OUTPUT_JSON === $output ) {
				$this->runtime->json( $summary );

				return $this->runtime->halt( CliExitCode::SUCCESS );
			}

			$this->runtime->format_items(
				self::OUTPUT_TABLE,
				$this->status_rows( $summary ),
				array( 'section', 'key', 'value' )
			);

			return $this->runtime->halt( CliExitCode::SUCCESS );
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to read plugin status.' );
		}
	}

	/**
	 * Output plugin diagnostics.
	 *
	 * ## OPTIONS
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function diagnostics( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output   = $this->output_mode( $assoc_args );
			$report   = $this->normalize_diagnostics_report( $this->diagnostics->report() );
			$degraded = $this->diagnostics_degraded( $report );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( self::OUTPUT_JSON === $output ) {
				$this->runtime->json( $report );

				return $this->runtime->halt( $degraded ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
			}

			$this->runtime->format_items(
				self::OUTPUT_TABLE,
				$this->diagnostic_rows( $report ),
				array( 'id', 'status', 'code', 'label', 'message' )
			);

			if ( $degraded ) {
				$this->runtime->warning( 'One or more diagnostics require attention.' );
			}

			return $this->runtime->halt( $degraded ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to read plugin diagnostics.' );
		}
	}

	/**
	 * Output one attachment snapshot.
	 *
	 * ## OPTIONS
	 *
	 * <attachment-id>
	 * : Attachment ID.
	 *
	 * [--target-format=<webp|avif>]
	 * : Limit derivative output to one ready modern format.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function attachment( array $args, array $assoc_args ): int {
		try {
			$attachment_id = $this->attachment_id_from_args( $args );
			$output        = $this->output_mode( $assoc_args );
			$target_format = $this->target_format( $assoc_args );

			if ( 0 === $attachment_id ) {
				return $this->fail( 'A valid positive attachment ID is required.' );
			}

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( array_key_exists( 'target-format', $assoc_args ) && null === $target_format ) {
				return $this->fail( 'Unsupported target format. Use webp or avif.' );
			}

			if ( ! $this->lookup->exists( $attachment_id ) ) {
				return $this->fail( 'The requested attachment does not exist.' );
			}

			if ( ! $this->lookup->is_image( $attachment_id ) ) {
				return $this->fail( 'The requested attachment is not an image.' );
			}

			$details  = $this->filter_attachment_details(
				$this->normalize_attachment_details( $this->attachments->details( $attachment_id ) ),
				$target_format
			);
			$degraded = ! empty( $details['warnings'] );

			if ( self::OUTPUT_JSON === $output ) {
				$this->runtime->json( $details );

				return $this->runtime->halt( $degraded ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
			}

			$this->runtime->format_items(
				self::OUTPUT_TABLE,
				$this->attachment_summary_rows( $details ),
				array( 'key', 'value' )
			);

			$derivative_rows = $this->attachment_derivative_rows( $details );

			if ( array() !== $derivative_rows ) {
				$this->runtime->line( '' );
				$this->runtime->format_items(
					self::OUTPUT_TABLE,
					$derivative_rows,
					array( 'size', 'format', 'source_file', 'source_bytes', 'derivative_file', 'derivative_bytes', 'savings_bytes', 'savings_percent' )
				);
			} else {
				$this->runtime->line( '' );
				$this->runtime->line( 'No ready derivatives matched the current attachment selection.' );
			}

			if ( $degraded ) {
				$this->runtime->warning( 'Stored attachment metadata includes warnings.' );
			}

			return $this->runtime->halt( $degraded ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to read attachment details.' );
		}
	}

	/**
	 * Run a dry-run bulk scan to completion.
	 *
	 * ## OPTIONS
	 *
	 * [--scan-scope=<all_eligible|missing_only|failed_only|stale_only>]
	 * : Scan scope.
	 *
	 * [--target-format=<all_enabled|webp|avif>]
	 * : Target format scope.
	 *
	 * [--date-from=<YYYY-MM-DD>]
	 * : Optional uploaded-after date.
	 *
	 * [--date-to=<YYYY-MM-DD>]
	 * : Optional uploaded-before date.
	 *
	 * [--attachment-ids=<csv>]
	 * : Optional comma-separated attachment IDs.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function scan( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$filters = $this->bulk_filters(
				$assoc_args,
				array(
					BulkScanFilters::SCOPE_ALL_ELIGIBLE,
					BulkScanFilters::SCOPE_MISSING_ONLY,
					BulkScanFilters::SCOPE_FAILED_ONLY,
					BulkScanFilters::SCOPE_STALE_ONLY,
				)
			);

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( ! ( $filters instanceof BulkScanFilters ) ) {
				return $this->fail( 'Invalid bulk scan filters.' );
			}

			return $this->render_operation_result(
				$this->bulk->scan( $filters, $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to complete the bulk scan.' );
		}
	}

	/**
	 * Run scan and queue continuation for eligible attachments.
	 *
	 * ## OPTIONS
	 *
	 * [--scan-scope=<all_eligible|missing_only>]
	 * : Queue candidate scope.
	 *
	 * [--target-format=<all_enabled|webp|avif>]
	 * : Target format scope.
	 *
	 * [--date-from=<YYYY-MM-DD>]
	 * : Optional uploaded-after date.
	 *
	 * [--date-to=<YYYY-MM-DD>]
	 * : Optional uploaded-before date.
	 *
	 * [--attachment-ids=<csv>]
	 * : Optional comma-separated attachment IDs.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function queue( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$filters = $this->bulk_filters(
				$assoc_args,
				array(
					BulkScanFilters::SCOPE_ALL_ELIGIBLE,
					BulkScanFilters::SCOPE_MISSING_ONLY,
				)
			);

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( ! ( $filters instanceof BulkScanFilters ) ) {
				return $this->fail( 'Invalid bulk queue filters.' );
			}

			return $this->render_operation_result(
				$this->bulk->queue( $filters, $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to queue bulk optimization work.' );
		}
	}

	/**
	 * Retry failed attachments in bounded pages.
	 *
	 * ## OPTIONS
	 *
	 * [--target-format=<all_enabled|webp|avif>]
	 * : Target format scope.
	 *
	 * [--date-from=<YYYY-MM-DD>]
	 * : Optional uploaded-after date.
	 *
	 * [--date-to=<YYYY-MM-DD>]
	 * : Optional uploaded-before date.
	 *
	 * [--attachment-ids=<csv>]
	 * : Optional comma-separated attachment IDs.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function retry_failures( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$filters = $this->fixed_scope_filters( $assoc_args, BulkScanFilters::SCOPE_FAILED_ONLY, true );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( ! ( $filters instanceof BulkScanFilters ) ) {
				return $this->fail( 'Invalid retry filters.' );
			}

			return $this->render_operation_result(
				$this->bulk->retry_failures( $filters, $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to retry failed attachments.' );
		}
	}

	/**
	 * Queue reconciliation jobs for stale attachments.
	 *
	 * ## OPTIONS
	 *
	 * [--date-from=<YYYY-MM-DD>]
	 * : Optional uploaded-after date.
	 *
	 * [--date-to=<YYYY-MM-DD>]
	 * : Optional uploaded-before date.
	 *
	 * [--attachment-ids=<csv>]
	 * : Optional comma-separated attachment IDs.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function reconcile_stale( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$filters = $this->fixed_scope_filters( $assoc_args, BulkScanFilters::SCOPE_STALE_ONLY, false );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( ! ( $filters instanceof BulkScanFilters ) ) {
				return $this->fail( 'Invalid stale reconciliation filters.' );
			}

			return $this->render_operation_result(
				$this->stale_reconcile->reconcile( $filters, $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to queue stale attachment reconciliation.' );
		}
	}

	/**
	 * Prune retained plugin logs.
	 *
	 * ## OPTIONS
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function prune_logs( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output = $this->output_mode( $assoc_args );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			return $this->render_operation_result(
				$this->log_prune->prune( $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to prune retained logs.' );
		}
	}

	/**
	 * Run cleanup orphan detection in dry-run mode.
	 *
	 * ## OPTIONS
	 *
	 * [--date-from=<YYYY-MM-DD>]
	 * : Optional uploaded-after date.
	 *
	 * [--date-to=<YYYY-MM-DD>]
	 * : Optional uploaded-before date.
	 *
	 * [--attachment-ids=<csv>]
	 * : Optional comma-separated attachment IDs.
	 *
	 * [--output=<table|json>]
	 * : Output mode.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return int
	 */
	public function cleanup_dry_run( array $args, array $assoc_args ): int {
		unset( $args );

		try {
			$output  = $this->output_mode( $assoc_args );
			$filters = $this->fixed_scope_filters( $assoc_args, BulkScanFilters::SCOPE_ALL_ELIGIBLE, false );

			if ( null === $output ) {
				return $this->fail( 'Unsupported output mode. Use table or json.' );
			}

			if ( ! ( $filters instanceof BulkScanFilters ) ) {
				return $this->fail( 'Invalid cleanup dry-run filters.' );
			}

			return $this->render_operation_result(
				$this->cleanup->dry_run( $filters, $this->progress_callback( $output ) ),
				$output
			);
		} catch ( \Throwable $error ) {
			unset( $error );

			return $this->fail( 'Unable to complete cleanup dry-run.' );
		}
	}

	/**
	 * Normalize the output mode.
	 *
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return string|null
	 */
	private function output_mode( array $assoc_args ): ?string {
		$output = isset( $assoc_args['output'] )
			? strtolower( trim( (string) $assoc_args['output'] ) )
			: self::OUTPUT_TABLE;

		return in_array( $output, array( self::OUTPUT_TABLE, self::OUTPUT_JSON ), true ) ? $output : null;
	}

	/**
	 * Normalize a requested target format.
	 *
	 * @param array<string,string> $assoc_args Assoc args.
	 * @return string|null
	 */
	private function target_format( array $assoc_args ): ?string {
		if ( ! array_key_exists( 'target-format', $assoc_args ) ) {
			return null;
		}

		$formats = AttachmentStatus::normalize_formats( array( $assoc_args['target-format'] ) );

		return isset( $formats[0] ) ? $formats[0] : null;
	}

	/**
	 * Parse normalized bulk filters from CLI assoc args.
	 *
	 * @param array<string,string> $assoc_args Assoc args.
	 * @param string[]             $allowed_scopes Allowed scopes.
	 * @return BulkScanFilters|null
	 */
	private function bulk_filters( array $assoc_args, array $allowed_scopes ): ?BulkScanFilters {
		$raw_scope      = array_key_exists( 'scan-scope', $assoc_args )
			? strtolower( trim( (string) $assoc_args['scan-scope'] ) )
			: BulkScanFilters::SCOPE_ALL_ELIGIBLE;
		$scope          = array_key_exists( 'scan-scope', $assoc_args )
			? BulkScanFilters::normalize_scan_scope( $raw_scope )
			: BulkScanFilters::SCOPE_ALL_ELIGIBLE;
		$raw_target     = array_key_exists( 'target-format', $assoc_args )
			? strtolower( trim( (string) $assoc_args['target-format'] ) )
			: BulkScanFilters::TARGET_ALL_ENABLED;
		$target         = array_key_exists( 'target-format', $assoc_args )
			? BulkScanFilters::normalize_target_format( $raw_target )
			: BulkScanFilters::TARGET_ALL_ENABLED;
		$date_from      = array_key_exists( 'date-from', $assoc_args )
			? BulkScanFilters::normalize_date( $assoc_args['date-from'] )
			: null;
		$date_to        = array_key_exists( 'date-to', $assoc_args )
			? BulkScanFilters::normalize_date( $assoc_args['date-to'] )
			: null;
		$attachment_ids = array_key_exists( 'attachment-ids', $assoc_args )
			? BulkScanFilters::normalize_attachment_ids( $assoc_args['attachment-ids'] )
			: array();

		if (
			( array_key_exists( 'scan-scope', $assoc_args ) && ( ! in_array( $raw_scope, BulkScanFilters::scopes(), true ) || ! in_array( $scope, $allowed_scopes, true ) ) )
			|| ( array_key_exists( 'target-format', $assoc_args ) && ! in_array( $raw_target, BulkScanFilters::target_formats(), true ) )
			|| ( array_key_exists( 'date-from', $assoc_args ) && null === $date_from )
			|| ( array_key_exists( 'date-to', $assoc_args ) && null === $date_to )
			|| ( array_key_exists( 'attachment-ids', $assoc_args ) && '' !== trim( (string) $assoc_args['attachment-ids'] ) && array() === $attachment_ids )
		) {
			return null;
		}

		return new BulkScanFilters( $scope, $target, $date_from, $date_to, $attachment_ids );
	}

	/**
	 * Build filters for fixed-scope commands.
	 *
	 * @param array<string,string> $assoc_args Assoc args.
	 * @param string               $scope Fixed scope.
	 * @param bool                 $allow_target Whether target format is allowed.
	 * @return BulkScanFilters|null
	 */
	private function fixed_scope_filters( array $assoc_args, string $scope, bool $allow_target ): ?BulkScanFilters {
		if ( array_key_exists( 'scan-scope', $assoc_args ) ) {
			return null;
		}

		if ( ! $allow_target && array_key_exists( 'target-format', $assoc_args ) ) {
			return null;
		}

		$raw_target     = $allow_target && array_key_exists( 'target-format', $assoc_args )
			? strtolower( trim( (string) $assoc_args['target-format'] ) )
			: BulkScanFilters::TARGET_ALL_ENABLED;
		$target         = $allow_target && array_key_exists( 'target-format', $assoc_args )
			? BulkScanFilters::normalize_target_format( $raw_target )
			: BulkScanFilters::TARGET_ALL_ENABLED;
		$date_from      = array_key_exists( 'date-from', $assoc_args )
			? BulkScanFilters::normalize_date( $assoc_args['date-from'] )
			: null;
		$date_to        = array_key_exists( 'date-to', $assoc_args )
			? BulkScanFilters::normalize_date( $assoc_args['date-to'] )
			: null;
		$attachment_ids = array_key_exists( 'attachment-ids', $assoc_args )
			? BulkScanFilters::normalize_attachment_ids( $assoc_args['attachment-ids'] )
			: array();

		if (
			( $allow_target && array_key_exists( 'target-format', $assoc_args ) && ! in_array( $raw_target, BulkScanFilters::target_formats(), true ) )
			|| ( array_key_exists( 'date-from', $assoc_args ) && null === $date_from )
			|| ( array_key_exists( 'date-to', $assoc_args ) && null === $date_to )
			|| ( array_key_exists( 'attachment-ids', $assoc_args ) && '' !== trim( (string) $assoc_args['attachment-ids'] ) && array() === $attachment_ids )
		) {
			return null;
		}

		return new BulkScanFilters( $scope, $target, $date_from, $date_to, $attachment_ids );
	}

	/**
	 * Parse the attachment ID from positional args.
	 *
	 * @param array<int,string> $args Positional args.
	 * @return int
	 */
	private function attachment_id_from_args( array $args ): int {
		if ( ! isset( $args[0] ) || ! is_numeric( $args[0] ) ) {
			return 0;
		}

		return max( 0, (int) $args[0] );
	}

	/**
	 * Build normalized status payload keys for CLI use.
	 *
	 * @param array<string,mixed> $summary Status summary.
	 * @return array<string,mixed>
	 */
	private function normalize_status_summary( array $summary ): array {
		$queue_control = isset( $summary['queueControl'] ) && is_array( $summary['queueControl'] ) ? $summary['queueControl'] : array();

		return array(
			'queue'           => isset( $summary['queue'] ) && is_array( $summary['queue'] ) ? $summary['queue'] : array(),
			'settings'        => isset( $summary['settings'] ) && is_array( $summary['settings'] ) ? $summary['settings'] : array(),
			'statistics'      => isset( $summary['statistics'] ) && is_array( $summary['statistics'] ) ? $summary['statistics'] : array(),
			'environment'     => isset( $summary['environment'] ) && is_array( $summary['environment'] ) ? $summary['environment'] : array(),
			'offload'         => isset( $summary['offload'] ) && is_array( $summary['offload'] ) ? $summary['offload'] : null,
			'conflicts'       => isset( $summary['conflicts'] ) && is_array( $summary['conflicts'] ) ? array_values( $summary['conflicts'] ) : array(),
			'recent_failures' => isset( $summary['recentFailures'] ) && is_array( $summary['recentFailures'] ) ? array_values( $summary['recentFailures'] ) : array(),
			'refresh'         => isset( $summary['refresh'] ) && is_array( $summary['refresh'] ) ? $summary['refresh'] : array(),
			'queue_control'   => array(
				'paused'             => ! empty( $queue_control['paused'] ),
				'updated_at_gmt'     => isset( $queue_control['updated_at_gmt'] ) ? (string) $queue_control['updated_at_gmt'] : '',
				'updated_by_user_id' => isset( $queue_control['updated_by_user_id'] ) ? (int) $queue_control['updated_by_user_id'] : 0,
				'pending'            => isset( $queue_control['pending'] ) ? (int) $queue_control['pending'] : 0,
				'in_progress'        => isset( $queue_control['inProgress'] ) ? (int) $queue_control['inProgress'] : 0,
			),
		);
	}

	/**
	 * Build table rows for the status summary.
	 *
	 * @param array<string,mixed> $summary Status summary.
	 * @return array<int,array<string,string>>
	 */
	private function status_rows( array $summary ): array {
		$statistics    = isset( $summary['statistics'] ) && is_array( $summary['statistics'] ) ? $summary['statistics'] : array();
		$states        = isset( $statistics['attachment_states'] ) && is_array( $statistics['attachment_states'] ) ? $statistics['attachment_states'] : array();
		$totals        = isset( $statistics['totals'] ) && is_array( $statistics['totals'] ) ? $statistics['totals'] : array();
		$settings      = isset( $summary['settings'] ) && is_array( $summary['settings'] ) ? $summary['settings'] : array();
		$environment   = isset( $summary['environment'] ) && is_array( $summary['environment'] ) ? $summary['environment'] : array();
		$queue_control = isset( $summary['queue_control'] ) && is_array( $summary['queue_control'] ) ? $summary['queue_control'] : array();

		$rows = array(
			$this->row( 'queue', 'available', $this->bool_text( ! empty( $summary['queue']['available'] ) ) ),
			$this->row( 'settings', 'enabled_formats', implode( ', ', isset( $settings['enabled_formats'] ) && is_array( $settings['enabled_formats'] ) ? $settings['enabled_formats'] : array() ) ),
			$this->row( 'settings', 'automatic_optimization', $this->bool_text( ! empty( $settings['automatic_optimization'] ) ) ),
			$this->row( 'settings', 'delivery_enabled', $this->bool_text( ! empty( $settings['delivery_enabled'] ) ) ),
			$this->row( 'statistics', 'generated_at_gmt', isset( $statistics['generated_at_gmt'] ) ? (string) $statistics['generated_at_gmt'] : '' ),
			$this->row( 'statistics', 'attachments_considered', (string) (int) ( $totals['attachments_considered'] ?? 0 ) ),
			$this->row( 'statistics', 'attachments_with_ready_derivatives', (string) (int) ( $totals['attachments_with_ready_derivatives'] ?? 0 ) ),
			$this->row( 'statistics', 'savings_bytes', (string) (int) ( $totals['savings_bytes'] ?? 0 ) ),
			$this->row( 'queue_control', 'paused', $this->bool_text( ! empty( $queue_control['paused'] ) ) ),
			$this->row( 'queue_control', 'pending', (string) (int) ( $queue_control['pending'] ?? 0 ) ),
			$this->row( 'queue_control', 'in_progress', (string) (int) ( $queue_control['in_progress'] ?? 0 ) ),
			$this->row( 'environment', 'uploads_status', isset( $environment['uploads']['status'] ) ? (string) $environment['uploads']['status'] : '' ),
			$this->row( 'environment', 'webp_support', isset( $environment['formats']['webp']['status'] ) ? (string) $environment['formats']['webp']['status'] : '' ),
			$this->row( 'environment', 'avif_support', isset( $environment['formats']['avif']['status'] ) ? (string) $environment['formats']['avif']['status'] : '' ),
			$this->row( 'offload', 'status', isset( $summary['offload']['code'] ) ? (string) $summary['offload']['code'] : 'none' ),
			$this->row( 'observability', 'conflicts', (string) count( $summary['conflicts'] ) ),
			$this->row( 'observability', 'recent_failures', (string) count( $summary['recent_failures'] ) ),
		);

		foreach ( AttachmentStatus::states() as $state ) {
			$rows[] = $this->row( 'attachment_states', $state, (string) (int) ( $states[ $state ] ?? 0 ) );
		}

		return $rows;
	}

	/**
	 * Normalize diagnostics report.
	 *
	 * @param array<string,mixed> $report Diagnostics report.
	 * @return array<string,mixed>
	 */
	private function normalize_diagnostics_report( array $report ): array {
		return array(
			'summary' => isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array(),
			'results' => isset( $report['results'] ) && is_array( $report['results'] ) ? array_values( $report['results'] ) : array(),
		);
	}

	/**
	 * Whether diagnostics contain warning or fail results.
	 *
	 * @param array<string,mixed> $report Diagnostics report.
	 * @return bool
	 */
	private function diagnostics_degraded( array $report ): bool {
		$summary = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();

		return (int) ( $summary['warning'] ?? 0 ) > 0 || (int) ( $summary['fail'] ?? 0 ) > 0;
	}

	/**
	 * Build table rows for diagnostics.
	 *
	 * @param array<string,mixed> $report Diagnostics report.
	 * @return array<int,array<string,string>>
	 */
	private function diagnostic_rows( array $report ): array {
		$rows = array();

		foreach ( $report['results'] as $result ) {
			if ( ! is_array( $result ) ) {
				continue;
			}

			$rows[] = array(
				'id'      => isset( $result['id'] ) ? (string) $result['id'] : '',
				'status'  => isset( $result['status'] ) ? (string) $result['status'] : '',
				'code'    => isset( $result['code'] ) ? (string) $result['code'] : '',
				'label'   => isset( $result['label'] ) ? (string) $result['label'] : '',
				'message' => isset( $result['message'] ) ? (string) $result['message'] : '',
			);
		}

		return $rows;
	}

	/**
	 * Normalize attachment details.
	 *
	 * @param array<string,mixed> $details Attachment details.
	 * @return array<string,mixed>
	 */
	private function normalize_attachment_details( array $details ): array {
		return array(
			'attachment_id' => isset( $details['attachment_id'] ) ? max( 0, (int) $details['attachment_id'] ) : 0,
			'warnings'      => ! empty( $details['warnings'] ),
			'codes'         => isset( $details['codes'] ) && is_array( $details['codes'] ) ? array_values( $details['codes'] ) : array(),
			'messages'      => isset( $details['messages'] ) && is_array( $details['messages'] ) ? array_values( $details['messages'] ) : array(),
			'status'        => isset( $details['status'] ) && is_array( $details['status'] ) ? $details['status'] : array(),
			'manifest'      => isset( $details['manifest'] ) && is_array( $details['manifest'] ) ? $details['manifest'] : array(),
		);
	}

	/**
	 * Filter one attachment payload to a requested target format.
	 *
	 * @param array<string,mixed> $details Attachment details.
	 * @param string|null         $target_format Target format.
	 * @return array<string,mixed>
	 */
	private function filter_attachment_details( array $details, ?string $target_format ): array {
		if ( null === $target_format ) {
			return $details;
		}

		$formats = AttachmentStatus::normalize_formats( array( $target_format ) );

		if ( ! isset( $formats[0] ) ) {
			return $details;
		}

		if ( isset( $details['status']['formats'] ) && is_array( $details['status']['formats'] ) ) {
			$details['status']['formats'] = array_values(
				array_intersect( $details['status']['formats'], array( $formats[0] ) )
			);
		}

		if ( isset( $details['manifest']['sizes'] ) && is_array( $details['manifest']['sizes'] ) ) {
			$sizes = array();

			foreach ( $details['manifest']['sizes'] as $size_name => $size ) {
				if ( ! is_string( $size_name ) || ! is_array( $size ) || ! isset( $size['formats'] ) || ! is_array( $size['formats'] ) ) {
					continue;
				}

				if ( ! isset( $size['formats'][ $formats[0] ] ) || ! is_array( $size['formats'][ $formats[0] ] ) ) {
					continue;
				}

				$size['formats']     = array( $formats[0] => $size['formats'][ $formats[0] ] );
				$sizes[ $size_name ] = $size;
			}

			$details['manifest']['sizes'] = $sizes;
		}

		$details['requested_target_format'] = $formats[0];

		return $details;
	}

	/**
	 * Build attachment summary table rows.
	 *
	 * @param array<string,mixed> $details Attachment details.
	 * @return array<int,array<string,string>>
	 */
	private function attachment_summary_rows( array $details ): array {
		$status = isset( $details['status'] ) && is_array( $details['status'] ) ? $details['status'] : array();

		return array(
			array(
				'key'   => 'attachment_id',
				'value' => (string) (int) $details['attachment_id'],
			),
			array(
				'key'   => 'state',
				'value' => isset( $status['state'] ) ? (string) $status['state'] : '',
			),
			array(
				'key'   => 'excluded',
				'value' => $this->bool_text( ! empty( $status['excluded'] ) ),
			),
			array(
				'key'   => 'ready_formats',
				'value' => implode( ', ', isset( $status['formats'] ) && is_array( $status['formats'] ) ? $status['formats'] : array() ),
			),
			array(
				'key'   => 'warnings',
				'value' => $this->bool_text( ! empty( $details['warnings'] ) ),
			),
			array(
				'key'   => 'codes',
				'value' => implode( ', ', isset( $details['codes'] ) && is_array( $details['codes'] ) ? $details['codes'] : array() ),
			),
		);
	}

	/**
	 * Build derivative table rows for an attachment payload.
	 *
	 * @param array<string,mixed> $details Attachment details.
	 * @return array<int,array<string,string>>
	 */
	private function attachment_derivative_rows( array $details ): array {
		$manifest = isset( $details['manifest'] ) && is_array( $details['manifest'] ) ? $details['manifest'] : array();
		$sizes    = isset( $manifest['sizes'] ) && is_array( $manifest['sizes'] ) ? $manifest['sizes'] : array();
		$rows     = array();

		foreach ( $sizes as $size_name => $size ) {
			if ( ! is_string( $size_name ) || ! is_array( $size ) ) {
				continue;
			}

			$source  = isset( $size['source'] ) && is_array( $size['source'] ) ? $size['source'] : array();
			$formats = isset( $size['formats'] ) && is_array( $size['formats'] ) ? $size['formats'] : array();

			foreach ( $formats as $format => $entry ) {
				if ( ! is_string( $format ) || ! is_array( $entry ) ) {
					continue;
				}

				$rows[] = array(
					'size'             => $size_name,
					'format'           => $format,
					'source_file'      => isset( $source['file'] ) ? (string) $source['file'] : '',
					'source_bytes'     => (string) (int) ( $source['bytes'] ?? 0 ),
					'derivative_file'  => isset( $entry['file'] ) ? (string) $entry['file'] : '',
					'derivative_bytes' => (string) (int) ( $entry['bytes'] ?? 0 ),
					'savings_bytes'    => (string) (int) ( $entry['savings_bytes'] ?? 0 ),
					'savings_percent'  => isset( $entry['savings_percent'] ) && is_numeric( $entry['savings_percent'] )
						? (string) $entry['savings_percent']
						: '',
				);
			}
		}

		return $rows;
	}

	/**
	 * Build one generic section row.
	 *
	 * @param string $section Section.
	 * @param string $key Key.
	 * @param string $value Value.
	 * @return array<string,string>
	 */
	private function row( string $section, string $key, string $value ): array {
		return array(
			'section' => $section,
			'key'     => $key,
			'value'   => $value,
		);
	}

	/**
	 * Normalize a boolean label.
	 *
	 * @param bool $value Bool value.
	 * @return string
	 */
	private function bool_text( bool $value ): string {
		return $value ? 'yes' : 'no';
	}

	/**
	 * Get a progress callback for table output only.
	 *
	 * @param string $output Output mode.
	 * @return callable|null
	 */
	private function progress_callback( string $output ): ?callable {
		return self::OUTPUT_TABLE === $output ? array( $this->runtime, 'line' ) : null;
	}

	/**
	 * Render one normalized CLI operation result.
	 *
	 * @param CliOperationResult $result Operation result.
	 * @param string             $output Output mode.
	 * @return int
	 */
	private function render_operation_result( CliOperationResult $result, string $output ): int {
		if ( self::OUTPUT_JSON === $output ) {
			$this->runtime->json( $result->to_array() );

			return $this->runtime->halt( $result->is_degraded() ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
		}

		$this->runtime->format_items(
			self::OUTPUT_TABLE,
			$this->operation_rows( $result ),
			array( 'section', 'key', 'value' )
		);

		foreach ( $result->messages() as $message ) {
			$this->runtime->warning( $message );
		}

		return $this->runtime->halt( $result->is_degraded() ? CliExitCode::DEGRADED : CliExitCode::SUCCESS );
	}

	/**
	 * Build table rows for a normalized CLI operation result.
	 *
	 * @param CliOperationResult $result Operation result.
	 * @return array<int,array<string,string>>
	 */
	private function operation_rows( CliOperationResult $result ): array {
		$rows = array(
			$this->row( 'operation', 'name', $result->operation() ),
			$this->row( 'operation', 'degraded', $this->bool_text( $result->is_degraded() ) ),
		);

		if ( array() !== $result->codes() ) {
			$rows[] = $this->row( 'operation', 'codes', implode( ', ', $result->codes() ) );
		}

		foreach ( $result->payload() as $section => $value ) {
			if ( ! is_string( $section ) ) {
				continue;
			}

			$rows = array_merge( $rows, $this->section_rows( $section, $value ) );
		}

		return $rows;
	}

	/**
	 * Flatten one section payload into section/key/value rows.
	 *
	 * @param string $section Section name.
	 * @param mixed  $value Section value.
	 * @return array<int,array<string,string>>
	 */
	private function section_rows( string $section, $value ): array {
		if ( is_scalar( $value ) || null === $value ) {
			return array( $this->row( $section, 'value', $this->string_value( $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );

		if ( $is_list ) {
			$all_scalars = true;

			foreach ( $value as $item ) {
				if ( is_array( $item ) || is_object( $item ) ) {
					$all_scalars = false;
					break;
				}
			}

			if ( $all_scalars ) {
				return array(
					$this->row(
						$section,
						'items',
						implode(
							', ',
							array_map(
								function ( $item ): string {
									return $this->string_value( $item );
								},
								$value
							)
						)
					),
				);
			}

			$rows = array();

			foreach ( $value as $index => $item ) {
				$rows = array_merge( $rows, $this->section_rows( $section . '[' . $index . ']', $item ) );
			}

			return $rows;
		}

		$rows = array();

		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) ) {
				continue;
			}

			if ( is_scalar( $item ) || null === $item ) {
				$rows[] = $this->row( $section, $key, $this->string_value( $item ) );
				continue;
			}

			$rows = array_merge( $rows, $this->section_rows( $section . '.' . $key, $item ) );
		}

		return $rows;
	}

	/**
	 * Convert a value into a safe string for table output.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function string_value( $value ): string {
		if ( null === $value ) {
			return '';
		}

		if ( is_bool( $value ) ) {
			return $this->bool_text( $value );
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		return '';
	}

	/**
	 * Output an error and halt with failure.
	 *
	 * @param string $message Message.
	 * @return int
	 */
	private function fail( string $message ): int {
		$this->runtime->error( $message );

		return $this->runtime->halt( CliExitCode::FAILURE );
	}
}
