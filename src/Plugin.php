<?php
/**
 * Application composition root.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminController;
use HyperWeb\LighthouseImageOptimizer\Admin\AdminScreenContextResolver;
use HyperWeb\LighthouseImageOptimizer\Admin\Assets as AdminAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\BulkPage;
use HyperWeb\LighthouseImageOptimizer\Admin\DashboardPage;
use HyperWeb\LighthouseImageOptimizer\Admin\DiagnosticsPage;
use HyperWeb\LighthouseImageOptimizer\Admin\LogsPage;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkPreviewService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentActionAvailability;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentPresenter;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentRenderer;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryIntegration;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\WordPressMediaLibraryRuntime;
use HyperWeb\LighthouseImageOptimizer\Admin\Menu;
use HyperWeb\LighthouseImageOptimizer\Admin\NoticeManager;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageAssets;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\CriticalImageMetaBox;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\ElementorHeroBackgroundMetaBox;
use HyperWeb\LighthouseImageOptimizer\Admin\PostEditor\WordPressPostEditorRuntime;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentActionService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentDetailsService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\CompositeDiagnosticsService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\ContentInventoryController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DiagnosticsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\JobsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\LogsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\PageSpeedInsightsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestApi;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatisticsCacheReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusRefreshService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusSummaryService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\WordPressRestRuntime;
use HyperWeb\LighthouseImageOptimizer\Admin\SettingsPage;
use HyperWeb\LighthouseImageOptimizer\Admin\WordPressAdminAssetRuntime;
use HyperWeb\LighthouseImageOptimizer\Admin\WordPressAdminRuntime;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanup;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Attachment\WordPressAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Cli\CliCommands;
use HyperWeb\LighthouseImageOptimizer\Cli\CliBulkOperationsService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliCleanupDryRunService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliLogPruneService;
use HyperWeb\LighthouseImageOptimizer\Cli\CliReconcileStaleService;
use HyperWeb\LighthouseImageOptimizer\Cli\HwlioCommand;
use HyperWeb\LighthouseImageOptimizer\Cli\WordPressAttachmentLookup;
use HyperWeb\LighthouseImageOptimizer\Cli\WordPressCliRuntime;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\EnvironmentDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\ConflictDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\LateDiscoveredCriticalImageLocator;
use HyperWeb\LighthouseImageOptimizer\Delivery\LoadingAttributeManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\MarkupEligibility;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderer;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\ResponsivePreloadRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuilder;
use HyperWeb\LighthouseImageOptimizer\Delivery\TransformedMarkupRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressUploadsRuntime;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressAttachmentImageRuntime;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\FileAnimationDetector;
use HyperWeb\LighthouseImageOptimizer\Image\WordPressImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressCacheInvalidationDispatcher;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressTransientStore;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\LocalAttachmentSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadAwareSourceCollector;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadDeliveryAdapter;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WordPressWpOffloadMediaRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDeliveryPlanBuilder;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorCriticalBackgroundPreloadManager;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetGenerator;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetManager;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorWidgetMatcher;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\MultisiteIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\WordPressSiteContextRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorHeroBackgroundPostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorBackgroundStylesheetRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorBackgroundStylesheetStore;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorDocumentDataStore;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommerceIntegration;
use HyperWeb\LighthouseImageOptimizer\Integration\WooCommercePrimaryImageMatcher;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressWooCommerceRuntime;
use HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance;
use HyperWeb\LighthouseImageOptimizer\Logging\LogPruner;
use HyperWeb\LighthouseImageOptimizer\Logging\LogBrowserService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogDeletionService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogRetentionService;
use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;
use HyperWeb\LighthouseImageOptimizer\Queue\ActionSchedulerQueue;
use HyperWeb\LighthouseImageOptimizer\Queue\ActionSchedulerAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\NewUploadIntegration;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationWorker;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueMaintenance;
use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationWorker;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentCriticalImageSelector;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentByteReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentIssueReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\OccurrenceAssetMapper;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsService;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Reporting\WordPressContentInventoryRuntime;
use HyperWeb\LighthouseImageOptimizer\Reporting\WordPressPageSpeedHttpRuntime;
use HyperWeb\LighthouseImageOptimizer\Reporting\WordPressPageSpeedInsightsClient;
use HyperWeb\LighthouseImageOptimizer\Reporting\WordPressPageSpeedReportStore;
use HyperWeb\LighthouseImageOptimizer\Settings\WordPressPageSpeedCredentialsStore;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiRegistrar;

/**
 * Builds shared services and registers plugin hooks.
 */
final class Plugin {

	/**
	 * Stable plugin slug.
	 *
	 * @var string
	 */
	public const SLUG = 'hyperweb-lighthouse-image-optimizer';

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Shared hook registrar.
	 *
	 * @var HookRegistrar
	 */
	private $hooks;

	/**
	 * Hook providers owned by this composition root.
	 *
	 * @var HookProviderInterface[]
	 */
	private $providers;

	/**
	 * Whether providers have already registered their hooks.
	 *
	 * @var bool
	 */
	private $hooks_registered = false;

	/**
	 * Create the production plugin instance.
	 *
	 * @return self
	 */
	public static function create(): self {
		$version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			: '0.1.0-alpha.4';

		$basename = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME' )
			: self::SLUG . '/hyperweb-lighthouse-image-optimizer.php';

		$db_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_DB_VERSION' )
			: '1';

		$schema_version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			? (int) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_SCHEMA_VERSION' )
			: 1;

		$hooks                        = new HookRegistrar();
		$admin_runtime                = new WordPressAdminRuntime();
		$menu                         = new Menu( $admin_runtime );
		$query_provider               = static function (): array {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only tab routing for the current screen shell and bootstrap.
			$query = $_GET;

			return function_exists( 'wp_unslash' ) ? wp_unslash( $query ) : $query;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		};
		$notice_manager               = new NoticeManager();
		$context_resolver             = new AdminScreenContextResolver( $menu, $query_provider );
		$plugin_url                   = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_URL' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_URL' )
			: '';
		$rest_runtime                 = new WordPressRestRuntime();
		$rest_errors                  = new RestErrorFactory( $rest_runtime );
		$settings                     = SettingsRepository::for_wordpress();
		$pagespeed_credentials        = WordPressPageSpeedCredentialsStore::for_wordpress();
		$queue                        = ActionSchedulerQueue::for_wordpress();
		$single_actions               = new ActionSchedulerSingleActionScheduler();
		$queue_control_store          = QueueControlStateStore::for_wordpress();
		$attachment_jobs              = ActionSchedulerAttachmentJobControl::for_wordpress();
		$queue_control                = new QueueControlService( $queue_control_store, $attachment_jobs );
		$site_context                 = new WordPressSiteContextRuntime();
		$image_files                  = new WordPressImageFileProbe();
		$path_sanitizer               = new DerivativeManifestSanitizer();
		$local_source_collector       = SourceCollector::for_wordpress();
		$wp_offload_runtime           = new WordPressWpOffloadMediaRuntime();
		$wp_offload_adapter           = new WpOffloadMediaAdapter(
			$wp_offload_runtime,
			$image_files,
			$path_sanitizer
		);
		$offload_support              = new OffloadSupportService( $wp_offload_adapter, $site_context );
		$source_collector             = new OffloadAwareSourceCollector(
			new LocalAttachmentSourceCollector( $local_source_collector ),
			$wp_offload_runtime,
			$wp_offload_adapter,
			$offload_support,
			$path_sanitizer
		);
		$meta                         = new WordPressAttachmentMetaStore();
		$clock                        = new SystemAttachmentClock();
		$cache_invalidation           = new WordPressCacheInvalidationDispatcher();
		$repository                   = new DerivativeRepository(
			$meta,
			$path_sanitizer,
			$clock,
			$cache_invalidation
		);
		$details                      = new AttachmentDetailsService( $repository );
		$attachment_queue             = new AttachmentQueueService(
			$queue,
			$meta,
			$repository,
			$source_collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$queue_control_store,
			$offload_support
		);
		$attachment_reconciliation    = new AttachmentReconciliationService(
			$queue,
			$meta,
			$repository,
			$source_collector,
			new AttachmentFingerprintBuilder(),
			$clock,
			$queue_control_store,
			$offload_support
		);
		$attachment_cleanup           = AttachmentCleanup::for_wordpress();
		$media_runtime                = new WordPressMediaLibraryRuntime();
		$media_renderer               = new MediaAttachmentRenderer();
		$media_presenter              = new MediaAttachmentPresenter( new AttachmentActionAvailability() );
		$media_reader                 = new AttachmentStatusReader( $meta );
		$content_inventory_runtime    = new WordPressContentInventoryRuntime();
		$trusted_markers              = new TrustedAttachmentMarkerParser();
		$bulk_sessions                = new WordPressTransientBulkScanSessionStore( new WordPressTransientStore() );
		$bulk_runtime                 = new WordPressBulkScannerRuntime();
		$bulk_scans                   = new BulkScanService(
			$bulk_runtime,
			$bulk_sessions,
			$media_reader,
			$settings,
			null,
			null,
			$source_collector,
			new AttachmentFingerprintBuilder()
		);
		$bulk_preview                 = new BulkPreviewService(
			$bulk_sessions,
			$bulk_runtime,
			$media_reader,
			$media_presenter,
			$settings
		);
		$bulk_queue                   = new BulkQueueService(
			$bulk_sessions,
			$bulk_scans,
			$media_reader,
			$attachment_queue,
			$settings,
			$queue_control_store,
			null,
			$offload_support
		);
		$status_refresh               = new StatusRefreshService( $single_actions );
		$status_summary               = new StatusSummaryService(
			$queue,
			new StatisticsCacheReader( new WordPressOptionStore() ),
			$settings,
			new DashboardEnvironmentSummaryService(
				EnvironmentInspector::for_wordpress(),
				$settings,
				new ConflictDetector( new \HyperWeb\LighthouseImageOptimizer\Integration\Conflict\WordPressConflictRuntime(), $offload_support ),
				$offload_support
			),
			RecentFailureLogReader::for_wordpress(),
			$status_refresh,
			$queue_control
		);
		$cli_runtime                  = new WordPressCliRuntime();
		$cli_diagnostics              = new CompositeDiagnosticsService(
			EnvironmentDiagnostics::for_wordpress(),
			DerivativeHealthDiagnostics::for_wordpress(),
			ConflictDiagnostics::for_wordpress(),
			new OffloadSupportDiagnostics( $offload_support )
		);
		$admin_pages                  = array(
			new DashboardPage(),
			new BulkPage(),
			new SettingsPage( $settings, $pagespeed_credentials ),
			new DiagnosticsPage(),
			new LogsPage(),
		);
		$delivery_runtime             = new WordPressAttachmentImageRuntime();
		$delivery_analyzer            = new WordPressImageMarkupAnalyzer();
		$delivery_sanitizer           = $path_sanitizer;
		$size_resolver                = new AttachmentSizeResolver( $delivery_sanitizer );
		$critical_post_meta           = new WordPressCriticalImagePostMetaStore();
		$elementor_document_store     = new WordPressElementorDocumentDataStore();
		$content_inventory            = new ContentInventoryService(
			$content_inventory_runtime,
			$media_reader,
			$elementor_document_store,
			new ElementorBackgroundDiscovery( $elementor_document_store ),
			$trusted_markers
		);
		$pagespeed_reports            = new PageSpeedInsightsService(
			$settings,
			$pagespeed_credentials,
			$content_inventory_runtime,
			new WordPressPageSpeedInsightsClient( new WordPressPageSpeedHttpRuntime() ),
			new WordPressPageSpeedReportStore()
		);
		$elementor_hero_backgrounds   = new WordPressElementorHeroBackgroundPostMetaStore();
		$critical_registry            = new CriticalImageRegistry( $delivery_runtime, $settings, $critical_post_meta, $site_context );
		$woocommerce_runtime          = new WordPressWooCommerceRuntime();
		$woocommerce_matcher          = new WooCommercePrimaryImageMatcher( $woocommerce_runtime, $delivery_analyzer );
		$elementor_runtime            = new WordPressElementorRuntime();
		$elementor_matcher            = new ElementorWidgetMatcher( $elementor_runtime, $delivery_analyzer );
		$elementor_css_runtime        = new WordPressElementorBackgroundStylesheetRuntime();
		$loading_manager              = new LoadingAttributeManager( $settings, $critical_registry, $delivery_runtime, $delivery_analyzer );
		$uploads_runtime              = new WordPressUploadsRuntime();
		$delivery_files               = $image_files;
		$source_extractor             = new AttachmentImageSourceExtractor( $delivery_analyzer );
		$source_set_builder           = new SourceSetBuilder(
			DerivativeRepository::for_wordpress(),
			new \HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver(
				$uploads_runtime,
				new DerivativeManifestSanitizer()
			),
			$uploads_runtime,
			$delivery_files,
			new DerivativeManifestSanitizer(),
			$size_resolver
		);
		$dimension_repair             = new IntrinsicDimensionRepair( $size_resolver, $delivery_analyzer );
		$content_issue_reports        = new ContentIssueReportService(
			$settings,
			$delivery_runtime,
			$uploads_runtime,
			$delivery_files,
			new FileAnimationDetector(),
			$delivery_analyzer,
			$source_extractor,
			$size_resolver,
			$dimension_repair,
			new ContentCriticalImageSelector( $critical_post_meta ),
			$path_sanitizer
		);
		$content_byte_reports         = new ContentByteReportService(
			$settings,
			$repository,
			$delivery_files,
			new OccurrenceAssetMapper(
				$delivery_runtime,
				$uploads_runtime,
				$delivery_analyzer,
				$source_extractor,
				$size_resolver,
				$path_sanitizer
			)
		);
		$preload_registry             = new ResponsivePreloadRegistry();
		$elementor_background_plans   = new ElementorBackgroundDeliveryPlanBuilder(
			new ElementorBackgroundDiscovery( $elementor_document_store ),
			$repository,
			new \HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver(
				$uploads_runtime,
				new DerivativeManifestSanitizer()
			),
			$settings,
			new DerivativeManifestSanitizer()
		);
		$preload_manager              = new ResponsivePreloadManager(
			$settings,
			$delivery_runtime,
			$critical_registry,
			new LateDiscoveredCriticalImageLocator( $delivery_runtime, $delivery_analyzer, $trusted_markers ),
			$dimension_repair,
			$source_extractor,
			$source_set_builder,
			$delivery_analyzer,
			$preload_registry
		);
		$elementor_background_preload = new ElementorCriticalBackgroundPreloadManager(
			$settings,
			$elementor_runtime,
			$elementor_css_runtime,
			$elementor_hero_backgrounds,
			$elementor_background_plans,
			$preload_registry
		);
		$elementor_background_styles  = new ElementorBackgroundStylesheetManager(
			$settings,
			$elementor_runtime,
			$elementor_css_runtime,
			new ElementorBackgroundStylesheetGenerator(
				new ElementorBackgroundDiscovery( $elementor_document_store ),
				$repository,
				new \HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver(
					$uploads_runtime,
					new DerivativeManifestSanitizer()
				),
				$settings,
				new DerivativeManifestSanitizer()
			),
			new WordPressElementorBackgroundStylesheetStore()
		);
		$post_editor_runtime          = new WordPressPostEditorRuntime();

		return new self(
			$version,
			$hooks,
			array(
				new UpgradeRunner( Installer::for_wordpress( $version, $db_version, $schema_version ) ),
				new MultisiteIntegration(
					$site_context,
					$basename,
					$version,
					$db_version,
					$schema_version
				),
				SettingsApiRegistrar::for_wordpress(),
				new AdminController(
					$menu,
					$admin_pages,
					$admin_runtime,
					$context_resolver,
					$notice_manager
				),
				new AdminAssets(
					$menu,
					$context_resolver,
					new WordPressAdminAssetRuntime(),
					$notice_manager,
					$plugin_url,
					$version
				),
				new MediaLibraryIntegration(
					$settings,
					$media_runtime,
					$media_reader,
					$media_presenter,
					$media_renderer
				),
				new MediaLibraryAssets(
					$media_runtime,
					new WordPressAdminAssetRuntime(),
					$settings,
					$plugin_url,
					$version
				),
				new RestApi(
					array(
						new StatusController(
							$rest_runtime,
							$rest_errors,
							$status_summary,
							$status_refresh
						),
						new DiagnosticsController(
							$rest_runtime,
							$rest_errors,
							$cli_diagnostics
						),
						new ContentInventoryController(
							$rest_runtime,
							$rest_errors,
							$content_inventory,
							$content_issue_reports,
							$content_byte_reports
						),
						new PageSpeedInsightsController(
							$rest_runtime,
							$rest_errors,
							$content_inventory,
							$pagespeed_reports
						),
						new LogsController(
							$rest_runtime,
							$rest_errors,
							LogBrowserService::for_wordpress(),
							LogDeletionService::for_wordpress(),
							new LogRetentionService( $settings )
						),
						new JobsController(
							$rest_runtime,
							$rest_errors,
							$bulk_scans,
							$bulk_queue,
							$queue_control
						),
						new AttachmentsController(
							$rest_runtime,
							$rest_errors,
							$details,
							new AttachmentActionService(
								$queue,
								$settings,
								$meta,
								$repository,
								$source_collector,
								new AttachmentFingerprintBuilder(),
								$clock,
								$details,
								$attachment_queue,
								$queue_control_store,
								$offload_support,
								$attachment_reconciliation
							),
							$bulk_preview
						),
					)
				),
				new CliCommands(
					$cli_runtime,
					new HwlioCommand(
						$cli_runtime,
						$status_summary,
						$cli_diagnostics,
						$details,
						new WordPressAttachmentLookup(),
						new CliBulkOperationsService(
							$bulk_scans,
							$bulk_queue,
							$bulk_sessions
						),
						new CliReconcileStaleService(
							$bulk_runtime,
							$media_reader,
							$attachment_reconciliation
						),
						new CliLogPruneService( LogPruner::for_wordpress() ),
						new CliCleanupDryRunService(
							$bulk_runtime,
							$attachment_cleanup
						)
					)
				),
				LogMaintenance::for_wordpress(),
				QueueMaintenance::for_wordpress(),
				$attachment_cleanup,
				new NewUploadIntegration(
					$queue,
					$settings,
					\HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentExclusionRepository::for_wordpress(),
					$source_collector,
					new AttachmentFingerprintBuilder(),
					$repository,
					\HyperWeb\LighthouseImageOptimizer\Logging\Logger::for_wordpress(),
					$clock,
					static function ( int $attachment_id ): bool {
						return function_exists( 'wp_attachment_is_image' ) && \wp_attachment_is_image( $attachment_id );
					},
					static function ( int $attachment_id, string $context, array $status, array $payload ): void {
						if ( function_exists( 'do_action' ) ) {
							\do_action(
								\HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy::HOOK_ATTACHMENT_STATUS_REFRESH,
								$attachment_id,
								$context,
								$status,
								$payload
							);
						}
					},
					$queue_control_store,
					$offload_support
				),
				new CriticalImageMetaBox( $post_editor_runtime, $critical_post_meta ),
				new CriticalImageAssets(
					$post_editor_runtime,
					new WordPressAdminAssetRuntime(),
					$plugin_url,
					$version
				),
				new ElementorHeroBackgroundMetaBox(
					$post_editor_runtime,
					$elementor_hero_backgrounds,
					new ElementorBackgroundDiscovery( $elementor_document_store )
				),
				new WooCommerceIntegration(
					$woocommerce_runtime,
					$woocommerce_matcher
				),
				new ElementorIntegration( $elementor_matcher ),
				new ReconciliationWorker(
					$queue,
					\HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager::for_wordpress(),
					$source_collector,
					new AttachmentFingerprintBuilder(),
					$repository,
					\HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor::for_wordpress(),
					$settings,
					new \HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner(
						( function (): string {
							if ( function_exists( 'wp_get_upload_dir' ) ) {
								$uploads = wp_get_upload_dir();
								if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) && is_string( $uploads['basedir'] ) ) {
									return $uploads['basedir'];
								}
							}

							return '';
						} )(),
						new \HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressFilesystem()
					),
					\HyperWeb\LighthouseImageOptimizer\Logging\Logger::for_wordpress(),
					$clock,
					$queue_control_store,
					$single_actions,
					$cache_invalidation,
					$offload_support,
					$wp_offload_adapter
				),
				new OptimizationWorker(
					$queue,
					\HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager::for_wordpress(),
					$source_collector,
					new AttachmentFingerprintBuilder(),
					$repository,
					\HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor::for_wordpress(),
					$settings,
					\HyperWeb\LighthouseImageOptimizer\Logging\Logger::for_wordpress(),
					new \HyperWeb\LighthouseImageOptimizer\Queue\OptimizationRetryPolicy(),
					$clock,
					$queue_control_store,
					$single_actions,
					$offload_support
				),
				$loading_manager,
				$preload_manager,
				$elementor_background_preload,
				$elementor_background_styles,
				new OffloadDeliveryAdapter( $offload_support ),
				new DeliveryManager(
					$settings,
					$delivery_runtime,
					new MarkupEligibility(
						$settings,
						$delivery_runtime,
						$delivery_analyzer,
						$offload_support
					),
					$source_extractor,
					$source_set_builder,
					new PictureRenderer( $delivery_analyzer ),
					new TransformedMarkupRegistry(),
					$dimension_repair,
					$loading_manager
				),
				new I18n( self::SLUG, dirname( $basename ) . '/languages/' ),
			)
		);
	}

	/**
	 * Create the composition root.
	 *
	 * @param string                  $version Plugin version.
	 * @param HookRegistrar           $hooks Shared hook registrar.
	 * @param HookProviderInterface[] $providers Hook providers.
	 */
	public function __construct( string $version, HookRegistrar $hooks, array $providers ) {
		$this->version   = $version;
		$this->hooks     = $hooks;
		$this->providers = $providers;
	}

	/**
	 * Register provider hooks and pass them to WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->register_hooks();
		$this->hooks->register();
	}

	/**
	 * Register provider hooks with the shared registrar.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( $this->hooks_registered ) {
			return;
		}

		foreach ( $this->providers as $provider ) {
			$provider->register_hooks( $this->hooks );
		}

		$this->hooks_registered = true;
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function version(): string {
		return $this->version;
	}

	/**
	 * Get the shared hook registrar.
	 *
	 * @return HookRegistrar
	 */
	public function hooks(): HookRegistrar {
		return $this->hooks;
	}

	/**
	 * Get configured hook providers.
	 *
	 * @return HookProviderInterface[]
	 */
	public function providers(): array {
		return $this->providers;
	}
}
