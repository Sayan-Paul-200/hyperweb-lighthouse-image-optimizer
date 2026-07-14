<?php
// phpcs:ignoreFile Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test-only fakes live with their only consumer.
/**
 * Tests for the PageSpeed Insights service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedAuditSummary;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedClientResult;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsClientInterface;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsService;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedMetrics;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedReport;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedReportStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings\FakePageSpeedCredentialsStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies cached/live PSI orchestration and cache preservation behavior.
 */
final class PageSpeedInsightsServiceTest extends TestCase {

	/**
	 * Test cached reads return a normalized unavailable payload when no public URL exists.
	 *
	 * @return void
	 */
	public function test_cached_report_returns_public_url_unavailable_when_url_is_missing(): void {
		$runtime = new FakeContentInventoryRuntime();
		$client  = new FakePageSpeedInsightsClient();
		$store   = new FakePageSpeedReportStore();
		$service = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => true ) ),
			new FakePageSpeedCredentialsStore(),
			$runtime,
			$client,
			$store
		);

		$report = $service->cached_report( 55, 'mobile' )->to_array();

		self::assertSame( 'pagespeed_public_url_unavailable', $report['result_code'] );
		self::assertFalse( $report['available'] );
		self::assertSame( 0, $client->calls );
	}

	/**
	 * Test cached reads never invoke the external client and return stored cache when present.
	 *
	 * @return void
	 */
	public function test_cached_report_reads_local_cache_only(): void {
		$runtime                  = new FakeContentInventoryRuntime();
		$runtime->public_urls[55] = 'https://example.test/landing-page/';
		$client                   = new FakePageSpeedInsightsClient();
		$store                    = new FakePageSpeedReportStore();
		$store->reports['mobile'] = new PageSpeedReport(
			55,
			'mobile',
			PageSpeedReport::SOURCE_CACHE,
			'pagespeed_cached_result',
			true,
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'2026-07-14 10:00:00',
			new PageSpeedMetrics(
				array(
					'performance_score' => 89,
				)
			)
		);
		$service                  = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => false ) ),
			new FakePageSpeedCredentialsStore(),
			$runtime,
			$client,
			$store
		);

		$report = $service->cached_report( 55, 'mobile' )->to_array();

		self::assertSame( 'cache', $report['source'] );
		self::assertSame( 'pagespeed_cached_result', $report['result_code'] );
		self::assertTrue( $report['available'] );
		self::assertFalse( $report['integration_enabled'] );
		self::assertSame( 0, $client->calls );
	}

	/**
	 * Test live requests may run anonymously when no API key is saved.
	 *
	 * @return void
	 */
	public function test_run_report_allows_anonymous_requests_without_api_key(): void {
		$runtime                  = new FakeContentInventoryRuntime();
		$runtime->public_urls[55] = 'https://example.test/landing-page/';
		$client                   = new FakePageSpeedInsightsClient();
		$client->result           = new PageSpeedClientResult(
			true,
			'pagespeed_live_result',
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'2026-07-14 12:00:00',
			new PageSpeedMetrics(
				array(
					'performance_score' => 91,
					'largest_contentful_paint_ms' => 1800,
				)
			),
			array(
				new PageSpeedAuditSummary(
					array(
						'id' => 'modern-image-formats',
						'title' => 'Serve images in next-gen formats',
						'score' => 0.9,
						'display_value' => 'Potential savings of 120 KiB',
					)
				),
			)
		);
		$store                    = new FakePageSpeedReportStore();
		$service                  = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => true ) ),
			new FakePageSpeedCredentialsStore(),
			$runtime,
			$client,
			$store
		);

		$result = $service->run_report( 55, 'mobile' );

		self::assertTrue( $result->is_successful() );
		self::assertSame( 1, $client->calls );
		self::assertSame( '', $client->api_key );
		self::assertSame( 'https://example.test/landing-page/', $client->public_url );
		self::assertSame( 'mobile', $client->strategy );
		self::assertNotNull( $store->saved_report );
		self::assertSame( 'live', $store->saved_report->to_array()['source'] );
	}

	/**
	 * Test a saved API key is forwarded to the client.
	 *
	 * @return void
	 */
	public function test_run_report_passes_saved_api_key_to_client(): void {
		$runtime                  = new FakeContentInventoryRuntime();
		$runtime->public_urls[55] = 'https://example.test/landing-page/';
		$client                   = new FakePageSpeedInsightsClient();
		$client->result           = new PageSpeedClientResult(
			true,
			'pagespeed_live_result',
			'https://example.test/landing-page/'
		);
		$service                  = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => true ) ),
			new FakePageSpeedCredentialsStore( 'api-key-123' ),
			$runtime,
			$client,
			new FakePageSpeedReportStore()
		);

		$service->run_report( 55, 'desktop' );

		self::assertSame( 'api-key-123', $client->api_key );
		self::assertSame( 'desktop', $client->strategy );
	}

	/**
	 * Test failed live requests do not overwrite a previously cached success.
	 *
	 * @return void
	 */
	public function test_failed_live_request_preserves_previous_cached_success(): void {
		$runtime                  = new FakeContentInventoryRuntime();
		$runtime->public_urls[55] = 'https://example.test/landing-page/';
		$client                   = new FakePageSpeedInsightsClient();
		$client->result           = PageSpeedClientResult::failure( 'pagespeed_quota_exceeded', 'https://example.test/landing-page/' );
		$store                    = new FakePageSpeedReportStore();
		$store->reports['mobile'] = new PageSpeedReport(
			55,
			'mobile',
			PageSpeedReport::SOURCE_CACHE,
			'pagespeed_cached_result',
			true,
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'2026-07-14 09:00:00',
			new PageSpeedMetrics( array( 'performance_score' => 88 ) )
		);
		$service                  = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => true ) ),
			new FakePageSpeedCredentialsStore(),
			$runtime,
			$client,
			$store
		);

		$result = $service->run_report( 55, 'mobile' );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'pagespeed_quota_exceeded', $result->code() );
		self::assertNull( $store->saved_report );
		self::assertSame( 'pagespeed_cached_result', $store->reports['mobile']->to_array()['result_code'] );
	}
}

/**
 * Fake PSI client for service tests.
 */
final class FakePageSpeedInsightsClient implements PageSpeedInsightsClientInterface {

	/**
	 * Call count.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Last requested public URL.
	 *
	 * @var string
	 */
	public $public_url = '';

	/**
	 * Last requested strategy.
	 *
	 * @var string
	 */
	public $strategy = '';

	/**
	 * Last requested API key.
	 *
	 * @var string
	 */
	public $api_key = '';

	/**
	 * Result to return.
	 *
	 * @var PageSpeedClientResult|null
	 */
	public $result;

	/**
	 * Fetch one PSI result.
	 *
	 * @param string $public_url Public URL.
	 * @param string $strategy Strategy.
	 * @param string $api_key Optional API key.
	 * @return PageSpeedClientResult
	 */
	public function fetch( string $public_url, string $strategy, string $api_key = '' ): PageSpeedClientResult {
		++$this->calls;
		$this->public_url = $public_url;
		$this->strategy   = $strategy;
		$this->api_key    = $api_key;

		return $this->result ?? PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
	}
}

/**
 * Fake PSI report store for service tests.
 */
final class FakePageSpeedReportStore implements PageSpeedReportStoreInterface {

	/**
	 * Stored reports by strategy.
	 *
	 * @var array<string,PageSpeedReport>
	 */
	public $reports = array();

	/**
	 * Last saved report.
	 *
	 * @var PageSpeedReport|null
	 */
	public $saved_report;

	/**
	 * Read one report.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @param bool   $integration_enabled Whether integration is enabled.
	 * @param string $public_url Public URL.
	 * @return PageSpeedReport|null
	 */
	public function read( int $content_id, string $strategy, bool $integration_enabled, string $public_url = '' ): ?PageSpeedReport {
		if ( ! isset( $this->reports[ $strategy ] ) || ! $this->reports[ $strategy ] instanceof PageSpeedReport ) {
			return null;
		}

		return PageSpeedReport::from_storage(
			$content_id,
			$strategy,
			$this->reports[ $strategy ]->to_storage_array(),
			$integration_enabled,
			$public_url
		);
	}

	/**
	 * Save one report.
	 *
	 * @param PageSpeedReport $report Report payload.
	 * @return bool
	 */
	public function save( PageSpeedReport $report ): bool {
		$this->saved_report                = $report;
		$this->reports[ $report->strategy() ] = $report;

		return true;
	}
}
