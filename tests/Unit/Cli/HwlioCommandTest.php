<?php
/**
 * Tests for the root WP-CLI command.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentDetailsService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DiagnosticsServiceInterface;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatisticsCacheReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusRefreshService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusSummaryService;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Cli\CliBulkOperationsService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliCleanupDryRunService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliExitCode;
use HyperWeb\LighthouseImageOptimizer\Cli\CliLogPruneService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliReconcileStaleService;
use HyperWeb\LighthouseImageOptimizer\Cli\HwlioCommand;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Logging\LogPruner;
use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentJobCleaner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeFilesystem;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogPruner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogReadDatabase;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the root command behavior and exit codes.
 */
final class HwlioCommandTest extends TestCase {

	/**
	 * Test the root command exposes planned read and bulk subcommands only.
	 *
	 * @return void
	 */
	public function test_command_exposes_expected_subcommands(): void {
		$methods = get_class_methods( HwlioCommand::class );

		self::assertContains( 'status', $methods );
		self::assertContains( 'diagnostics', $methods );
		self::assertContains( 'attachment', $methods );
		self::assertContains( 'scan', $methods );
		self::assertContains( 'queue', $methods );
		self::assertContains( 'retry_failures', $methods );
		self::assertContains( 'reconcile_stale', $methods );
		self::assertContains( 'prune_logs', $methods );
		self::assertContains( 'cleanup_dry_run', $methods );
		self::assertNotContains( 'optimize', $methods );
	}

	/**
	 * Test scan emits a normalized JSON payload.
	 *
	 * @return void
	 */
	public function test_scan_returns_normalized_json_payload(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->scan( array(), array( 'output' => 'json' ) );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertSame( 'scan', $runtime->json_payloads[0]['operation'] );
		self::assertArrayHasKey( 'summary', $runtime->json_payloads[0] );
	}

	/**
	 * Test queue streams progress lines in table mode.
	 *
	 * @return void
	 */
	public function test_queue_streams_progress_lines_in_table_mode(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->queue( array(), array() );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertNotSame( array(), $runtime->lines );
		self::assertCount( 1, $runtime->tables );
	}

	/**
	 * Test status renders a human-readable table by default.
	 *
	 * @return void
	 */
	public function test_status_renders_table_output_by_default(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->status( array(), array() );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertCount( 1, $runtime->tables );
		self::assertSame( 'table', $runtime->tables[0]['format'] );
		self::assertContains( 'attachment_states', array_column( $runtime->tables[0]['items'], 'section' ) );
	}

	/**
	 * Test status emits a normalized JSON payload.
	 *
	 * @return void
	 */
	public function test_status_returns_normalized_json_payload(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->status( array(), array( 'output' => 'json' ) );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertArrayHasKey( 'recent_failures', $runtime->json_payloads[0] );
		self::assertArrayHasKey( 'queue_control', $runtime->json_payloads[0] );
		self::assertArrayNotHasKey( 'recentFailures', $runtime->json_payloads[0] );
	}

	/**
	 * Test invalid output modes are rejected cleanly.
	 *
	 * @return void
	 */
	public function test_status_rejects_invalid_output_mode(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->status( array(), array( 'output' => 'yaml' ) );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertStringContainsString( 'Unsupported output mode', $runtime->errors[0] );
	}

	/**
	 * Test status returns a failure exit code when output rendering throws.
	 *
	 * @return void
	 */
	public function test_status_returns_failure_on_runtime_error(): void {
		$runtime        = new FakeCliRuntime();
		$runtime->throw = true;
		$command        = $this->command( $runtime );

		$exit = $command->status( array(), array() );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertSame( 'Unable to read plugin status.', $runtime->errors[0] );
	}

	/**
	 * Test diagnostics returns JSON and degrades when warnings exist.
	 *
	 * @return void
	 */
	public function test_diagnostics_returns_degraded_exit_when_warnings_exist(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command(
			$runtime,
			new class() implements DiagnosticsServiceInterface {
				public function report(): array {
					return array(
						'summary' => array(
							'total'   => 1,
							'pass'    => 0,
							'warning' => 1,
							'fail'    => 0,
							'info'    => 0,
						),
						'results' => array(
							array(
								'id'      => 'uploads_directory',
								'status'  => 'warning',
								'code'    => 'uploads_not_writable',
								'label'   => 'Uploads',
								'message' => 'Uploads are not writable.',
							),
						),
					);
				}
			}
		);

		$exit = $command->diagnostics( array(), array( 'output' => 'json' ) );

		self::assertSame( CliExitCode::DEGRADED, $exit );
		self::assertSame( 'warning', $runtime->json_payloads[0]['results'][0]['status'] );
	}

	/**
	 * Test diagnostics renders stable table rows.
	 *
	 * @return void
	 */
	public function test_diagnostics_renders_table_rows(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->diagnostics( array(), array() );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertSame(
			array( 'id', 'status', 'code', 'label', 'message' ),
			$runtime->tables[0]['fields']
		);
	}

	/**
	 * Test invalid attachment IDs are rejected.
	 *
	 * @return void
	 */
	public function test_attachment_rejects_invalid_ids(): void {
		$runtime = new FakeCliRuntime();
		$command = $this->command( $runtime );

		$exit = $command->attachment( array( 'abc' ), array() );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertStringContainsString( 'positive attachment ID', $runtime->errors[0] );
	}

	/**
	 * Test missing attachments are rejected.
	 *
	 * @return void
	 */
	public function test_attachment_rejects_missing_attachments(): void {
		$runtime = new FakeCliRuntime();
		$lookup  = new FakeAttachmentLookup();
		$command = $this->command( $runtime, null, null, $lookup );

		$exit = $command->attachment( array( '123' ), array() );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertStringContainsString( 'does not exist', $runtime->errors[0] );
	}

	/**
	 * Test non-image attachments are rejected.
	 *
	 * @return void
	 */
	public function test_attachment_rejects_non_image_attachments(): void {
		$runtime           = new FakeCliRuntime();
		$lookup            = new FakeAttachmentLookup();
		$lookup->existing  = array( 123 => true );
		$lookup->images    = array( 123 => false );
		$command           = $this->command( $runtime, null, null, $lookup );

		$exit = $command->attachment( array( '123' ), array() );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertStringContainsString( 'not an image', $runtime->errors[0] );
	}

	/**
	 * Test attachment renders a summary and derivative table.
	 *
	 * @return void
	 */
	public function test_attachment_renders_summary_and_derivative_table(): void {
		$runtime = new FakeCliRuntime();
		$lookup  = new FakeAttachmentLookup();
		$lookup->existing = array( 123 => true );
		$lookup->images   = array( 123 => true );
		$command = $this->command( $runtime, null, $this->attachment_details_service( false ), $lookup );

		$exit = $command->attachment( array( '123' ), array() );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertCount( 2, $runtime->tables );
		self::assertSame( array( 'key', 'value' ), $runtime->tables[0]['fields'] );
		self::assertSame( 'webp', $runtime->tables[1]['items'][0]['format'] );
	}

	/**
	 * Test attachment target-format filtering limits ready derivative output.
	 *
	 * @return void
	 */
	public function test_attachment_filters_output_to_requested_target_format(): void {
		$runtime = new FakeCliRuntime();
		$lookup  = new FakeAttachmentLookup();
		$lookup->existing = array( 123 => true );
		$lookup->images   = array( 123 => true );
		$command = $this->command( $runtime, null, $this->attachment_details_service( false ), $lookup );

		$exit = $command->attachment( array( '123' ), array( 'output' => 'json', 'target-format' => 'avif' ) );

		self::assertSame( CliExitCode::SUCCESS, $exit );
		self::assertSame( 'avif', $runtime->json_payloads[0]['requested_target_format'] );
		self::assertSame( array( 'avif' ), $runtime->json_payloads[0]['status']['formats'] );
		self::assertArrayNotHasKey( 'webp', $runtime->json_payloads[0]['manifest']['sizes']['full']['formats'] );
	}

	/**
	 * Test invalid target formats are rejected.
	 *
	 * @return void
	 */
	public function test_attachment_rejects_invalid_target_format(): void {
		$runtime = new FakeCliRuntime();
		$lookup  = new FakeAttachmentLookup();
		$lookup->existing = array( 123 => true );
		$lookup->images   = array( 123 => true );
		$command = $this->command( $runtime, null, $this->attachment_details_service( false ), $lookup );

		$exit = $command->attachment( array( '123' ), array( 'target-format' => 'jpeg' ) );

		self::assertSame( CliExitCode::FAILURE, $exit );
		self::assertStringContainsString( 'Unsupported target format', $runtime->errors[0] );
	}

	/**
	 * Test repository warnings become a degraded attachment exit code.
	 *
	 * @return void
	 */
	public function test_attachment_returns_degraded_exit_when_repository_has_warnings(): void {
		$runtime = new FakeCliRuntime();
		$lookup  = new FakeAttachmentLookup();
		$lookup->existing = array( 123 => true );
		$lookup->images   = array( 123 => true );
		$command = $this->command( $runtime, null, $this->attachment_details_service( true ), $lookup );

		$exit = $command->attachment( array( '123' ), array( 'output' => 'json' ) );

		self::assertSame( CliExitCode::DEGRADED, $exit );
		self::assertTrue( $runtime->json_payloads[0]['warnings'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $runtime->json_payloads[0] );
		self::assertStringNotContainsString( 'C:/secret', is_string( $json ) ? $json : '' );
	}

	/**
	 * Build the command under test.
	 *
	 * @param FakeCliRuntime                    $runtime Fake runtime.
	 * @param DiagnosticsServiceInterface|null  $diagnostics Optional diagnostics service.
	 * @param AttachmentDetailsService|null     $attachments Optional attachment service.
	 * @param FakeAttachmentLookup|null         $lookup Optional lookup.
	 * @return HwlioCommand
	 */
	private function command(
		FakeCliRuntime $runtime,
		?DiagnosticsServiceInterface $diagnostics = null,
		?AttachmentDetailsService $attachments = null,
		?FakeAttachmentLookup $lookup = null
	): HwlioCommand {
		$diagnostics = $diagnostics ?? new class() implements DiagnosticsServiceInterface {
			public function report(): array {
				return array(
					'summary' => array(
						'total'   => 1,
						'pass'    => 1,
						'warning' => 0,
						'fail'    => 0,
						'info'    => 0,
					),
					'results' => array(
						array(
							'id'      => 'delivery_derivative_files',
							'status'  => 'pass',
							'code'    => 'delivery_derivatives_ok',
							'label'   => 'Delivery derivative files',
							'message' => 'Ready derivative files referenced by metadata are present.',
						),
					),
				);
			}
		};
		$attachments = $attachments ?? $this->attachment_details_service( false );
		$lookup      = $lookup ?? new FakeAttachmentLookup();

		return new HwlioCommand(
			$runtime,
			$this->status_service(),
			$diagnostics,
			$attachments,
			$lookup,
			$this->bulk_operations_service(),
			$this->stale_reconcile_service(),
			$this->log_prune_service(),
			$this->cleanup_dry_run_service()
		);
	}

	/**
	 * Build a real CLI bulk-operations service backed by fakes.
	 *
	 * @return CliBulkOperationsService
	 */
	private function bulk_operations_service(): CliBulkOperationsService {
		$bulk       = new FakeBulkScannerRuntime();
		$bulk->pages[0] = range( 1, 3 );
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( 1783612800 );
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$transients = new FakeTransientStore();
		$sessions   = new WordPressTransientBulkScanSessionStore( $transients );
		$statuses   = new AttachmentStatusReader( $store );
		$settings   = new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) );
		$scans      = new BulkScanService(
			$bulk,
			$sessions,
			$statuses,
			$settings,
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				return 'feedfacefeedfacefeedfacefeedface';
			}
		);
		$probe      = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new SourceCollector(
			new FakeAttachmentSourceProvider(
				'/uploads/2026/07/hero.jpg',
				array(
					'file'   => '2026/07/hero.jpg',
					'width'  => 2400,
					'height' => 1600,
					'sizes'  => array(),
				),
				'/uploads'
			),
			$probe
		);
		$controls = new QueueControlStateStore(
			new FakeOptionStore(),
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);
		$queue = new FakeQueue();

		return new CliBulkOperationsService(
			$scans,
			new BulkQueueService(
				$sessions,
				$scans,
				$statuses,
				new AttachmentQueueService(
					$queue,
					$store,
					$repository,
					$collector,
					new AttachmentFingerprintBuilder(),
					$clock,
					$controls
				),
				$settings,
				$controls
			),
			$sessions
		);
	}

	/**
	 * Build a real stale reconciliation service backed by fakes.
	 *
	 * @return CliReconcileStaleService
	 */
	private function stale_reconcile_service(): CliReconcileStaleService {
		$bulk                    = new FakeBulkScannerRuntime();
		$bulk->pages[0]          = array( 10 );
		$store                   = new FakeAttachmentMetaStore();
		$store->meta[10][ LifecyclePolicy::META_STATUS ] = array( 'state' => 'stale' );
		$clock                   = new FixedAttachmentClock( 1783612800 );
		$repository              = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$probe                   = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new SourceCollector(
			new FakeAttachmentSourceProvider(
				'/uploads/2026/07/hero.jpg',
				array(
					'file'   => '2026/07/hero.jpg',
					'width'  => 2400,
					'height' => 1600,
					'sizes'  => array(),
				),
				'/uploads'
			),
			$probe
		);
		$controls = new QueueControlStateStore(
			new FakeOptionStore(),
			LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);

		return new CliReconcileStaleService(
			$bulk,
			new AttachmentStatusReader( $store ),
			new AttachmentReconciliationService(
				new FakeQueue(),
				$store,
				$repository,
				$collector,
				new AttachmentFingerprintBuilder(),
				$clock,
				$controls
			)
		);
	}

	/**
	 * Build a log prune service backed by a fake pruner.
	 *
	 * @return CliLogPruneService
	 */
	private function log_prune_service(): CliLogPruneService {
		$pruner          = new FakeLogPruner();
		$pruner->results = array( LogPruner::BATCH_SIZE, 10 );

		return new CliLogPruneService( $pruner );
	}

	/**
	 * Build a cleanup dry-run service backed by fakes.
	 *
	 * @return CliCleanupDryRunService
	 */
	private function cleanup_dry_run_service(): CliCleanupDryRunService {
		$bulk            = new FakeBulkScannerRuntime();
		$bulk->pages[0]  = array( 15 );
		$store           = new FakeAttachmentMetaStore();
		$store->meta[15][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 1,
			'fingerprint'    => array(
				'relative_file' => '2026/07/hero.jpg',
				'file_size'     => 920000,
				'modified_time' => 1783526400,
				'metadata_hash' => str_repeat( 'a', 64 ),
				'signature'     => 'aaaaaaaaaaaaaaaaaaaa',
			),
			'updated_at'     => 1783526510,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 920000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 310000,
							'quality'         => 82,
							'savings_bytes'   => 610000,
							'savings_percent' => 66.3,
							'status'          => 'ready',
							'generated_at'    => 1783526500,
						),
					),
				),
			),
		);
		$store->meta[15][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => 'optimized',
			'formats'    => array( 'webp' ),
			'updated_at' => 1783526510,
			'error_code' => null,
			'excluded'   => false,
		);
		$filesystem = new FakeFilesystem(
			array(
				'C:/site/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
				'C:/site/wp-content/uploads/2026/07/hero.jpg.hwlio.avif',
			),
			array( 'C:/site/wp-content/uploads' )
		);
		$probe = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );
		$probe->add_file(
			'C:/site/wp-content/uploads/2026/07/hero.jpg',
			920000,
			1783526400,
			'image/jpeg',
			2400,
			1600
		);

		return new CliCleanupDryRunService(
			$bulk,
			new AttachmentCleanup(
				new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
				$store,
				new DerivativeFileCleaner( 'C:/site/wp-content/uploads', $filesystem ),
				new FakeAttachmentJobCleaner(),
				new SourceCollector(
					new FakeAttachmentSourceProvider(
						'C:/site/wp-content/uploads/2026/07/hero.jpg',
						array(
							'file'   => '2026/07/hero.jpg',
							'width'  => 2400,
							'height' => 1600,
							'sizes'  => array(),
						),
						'C:/site/wp-content/uploads'
					),
					$probe
				)
			)
		);
	}

	/**
	 * Build a real status service backed by fake dependencies.
	 *
	 * @return StatusSummaryService
	 */
	private function status_service(): StatusSummaryService {
		$options  = new FakeOptionStore();
		$options->options[ LifecyclePolicy::OPTION_STATISTICS_CACHE ] = array(
			'schema_version'    => 1,
			'generated_at_gmt'  => '2026-07-14 00:00:00',
			'attachment_states' => array(
				'optimized' => 3,
				'failed'    => 1,
			),
			'totals'            => array(
				'attachments_considered'             => 4,
				'attachments_with_ready_derivatives' => 3,
				'savings_bytes'                      => 900,
			),
			'formats'           => array(
				'webp' => array(
					'sources_ready' => 2,
				),
				'avif' => array(
					'sources_ready' => 1,
				),
			),
		);
		$settings = new FakeSettingsRepository(
			array(
				'enabled_formats'        => array( 'webp', 'avif' ),
				'automatic_optimization' => true,
				'delivery_enabled'       => true,
			)
		);

		return new StatusSummaryService(
			new FakeQueue(),
			new StatisticsCacheReader( $options ),
			$settings,
			new DashboardEnvironmentSummaryService(
				new EnvironmentInspector( new FakeEnvironmentProbe(), '7.4', '6.5' ),
				$settings,
				new ConflictDetector(
					new class() implements ConflictRuntimeInterface {
						public function active_plugin_basenames(): array {
							return array();
						}

						public function network_active_plugin_basenames(): array {
							return array();
						}
					}
				)
			),
			new RecentFailureLogReader( new FakeLogReadDatabase(), 'wp_hwlio_logs' ),
			new StatusRefreshService( new FakeSingleActionScheduler() ),
			new QueueControlService(
				new QueueControlStateStore(
					new FakeOptionStore(),
					'hwlio_queue_control_state',
					static function (): string {
						return '2026-07-14 00:00:00';
					}
				),
				new FakeAttachmentJobControl()
			)
		);
	}

	/**
	 * Build a real attachment-details service backed by fake meta.
	 *
	 * @param bool $warnings Whether to seed invalid metadata.
	 * @return AttachmentDetailsService
	 */
	private function attachment_details_service( bool $warnings ): AttachmentDetailsService {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'    => 'optimized',
			'formats'  => array( 'webp', 'avif' ),
			'excluded' => false,
		);
		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $warnings
			? array(
				'schema_version' => 1,
				'fingerprint'    => null,
				'updated_at'     => 1783987200,
				'sizes'          => array(
					'full' => array(
						'source'  => array(
							'file'   => 'C:/secret/hero.jpg',
							'mime'   => 'image/jpeg',
							'width'  => 2400,
							'height' => 1600,
							'bytes'  => 5000,
						),
						'formats' => array(
							'webp' => array(
								'file'            => 'C:/secret/hero.webp',
								'mime'            => 'image/webp',
								'bytes'           => 3200,
								'quality'         => 82,
								'savings_bytes'   => 1800,
								'savings_percent' => 36,
								'status'          => 'ready',
								'generated_at'    => 1783987200,
							),
						),
					),
				),
			)
			: array(
				'schema_version' => 1,
				'fingerprint'    => null,
				'updated_at'     => 1783987200,
				'sizes'          => array(
					'full' => array(
						'source'  => array(
							'file'   => '2026/07/hero.jpg',
							'mime'   => 'image/jpeg',
							'width'  => 2400,
							'height' => 1600,
							'bytes'  => 5000,
						),
						'formats' => array(
							'webp' => array(
								'file'            => '2026/07/hero.jpg.hwlio.webp',
								'mime'            => 'image/webp',
								'bytes'           => 3200,
								'quality'         => 82,
								'savings_bytes'   => 1800,
								'savings_percent' => 36,
								'status'          => 'ready',
								'generated_at'    => 1783987200,
							),
							'avif' => array(
								'file'            => '2026/07/hero.jpg.hwlio.avif',
								'mime'            => 'image/avif',
								'bytes'           => 2400,
								'quality'         => 60,
								'savings_bytes'   => 2600,
								'savings_percent' => 52,
								'status'          => 'ready',
								'generated_at'    => 1783987200,
							),
						),
					),
				),
			);

		return new AttachmentDetailsService(
			new DerivativeRepository(
				$store,
				new DerivativeManifestSanitizer(),
				new FixedAttachmentClock( 1783987200 )
			)
		);
	}
}
