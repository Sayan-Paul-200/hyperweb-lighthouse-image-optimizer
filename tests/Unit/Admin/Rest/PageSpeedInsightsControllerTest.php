<?php
/**
 * Tests for the PageSpeed Insights REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\PageSpeedInsightsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedClientResult;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsClientInterface;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedInsightsService;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedMetrics;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedReport;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedReportStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\FakeElementorDocumentDataStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting\FakeContentInventoryRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings\FakePageSpeedCredentialsStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies PageSpeed route registration and normalized cached/live responses.
 */
final class PageSpeedInsightsControllerTest extends TestCase {

	/**
	 * Test route registration adds only the cached/live PageSpeed route.
	 *
	 * @return void
	 */
	public function test_register_routes_adds_only_pagespeed_route(): void {
		$runtime     = new FakeRestRuntime();
		$controller  = $this->controller( $runtime );

		$controller->register_routes();

		self::assertCount( 1, $runtime->routes );
		self::assertSame( 'hwlio/v1', $runtime->routes[0]['namespace'] );
		self::assertSame( '/content/(?P<content_id>[\\d]+)/pagespeed', $runtime->routes[0]['route'] );
		self::assertSame( 'GET', $runtime->routes[0]['definitions'][0]['methods'] );
		self::assertSame( 'POST', $runtime->routes[0]['definitions'][1]['methods'] );
	}

	/**
	 * Test the permission callback requires manage_options.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_manage_options(): void {
		$runtime                                 = new FakeRestRuntime();
		$runtime->capabilities['manage_options'] = false;
		$controller                              = $this->controller( $runtime );

		$result = $controller->can_manage_options();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test GET returns cached PSI data without invoking the live client.
	 *
	 * @return void
	 */
	public function test_get_pagespeed_returns_cached_report_only(): void {
		$runtime     = new FakeRestRuntime();
		$client      = new ControllerFakePageSpeedInsightsClient();
		$store       = new ControllerFakePageSpeedReportStore();
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
			new PageSpeedMetrics( array( 'performance_score' => 90 ) )
		);
		$controller = $this->controller( $runtime, true, true, $client, $store );

		$response = $controller->get_pagespeed(
			array(
				'content_id' => 55,
				'strategy'   => 'mobile',
			)
		);

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertSame( 'pagespeed_cached_result', $response['data']['result_code'] );
		self::assertSame( 0, $client->calls );
	}

	/**
	 * Test POST returns a disabled error when the integration setting is off.
	 *
	 * @return void
	 */
	public function test_run_pagespeed_returns_disabled_error_when_feature_is_off(): void {
		$runtime     = new FakeRestRuntime();
		$controller  = $this->controller( $runtime, true, false );

		$response = $controller->run_pagespeed(
			array(
				'content_id' => 55,
				'strategy'   => 'mobile',
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'pagespeed_disabled', $response['code'] );
	}

	/**
	 * Test POST returns a missing-public-URL error safely.
	 *
	 * @return void
	 */
	public function test_run_pagespeed_returns_public_url_error_when_missing(): void {
		$runtime     = new FakeRestRuntime();
		$controller  = $this->controller( $runtime, true, true, null, null, '' );

		$response = $controller->run_pagespeed(
			array(
				'content_id' => 55,
				'strategy'   => 'mobile',
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'pagespeed_public_url_unavailable', $response['code'] );
	}

	/**
	 * Test POST normalizes quota failures from the PSI client.
	 *
	 * @return void
	 */
	public function test_run_pagespeed_returns_quota_error_when_client_reports_quota_failure(): void {
		$runtime         = new FakeRestRuntime();
		$client          = new ControllerFakePageSpeedInsightsClient();
		$client->result  = PageSpeedClientResult::failure( 'pagespeed_quota_exceeded', 'https://example.test/landing-page/' );
		$controller      = $this->controller( $runtime, true, true, $client );

		$response = $controller->run_pagespeed(
			array(
				'content_id' => 55,
				'strategy'   => 'mobile',
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'pagespeed_quota_exceeded', $response['code'] );
	}

	/**
	 * Test POST returns one normalized live report on success.
	 *
	 * @return void
	 */
	public function test_run_pagespeed_returns_live_report_on_success(): void {
		$runtime        = new FakeRestRuntime();
		$client         = new ControllerFakePageSpeedInsightsClient();
		$client->result = new PageSpeedClientResult(
			true,
			'pagespeed_live_result',
			'https://example.test/landing-page/',
			'https://example.test/landing-page/',
			'2026-07-14 12:00:00',
			new PageSpeedMetrics( array( 'performance_score' => 92 ) )
		);
		$store          = new ControllerFakePageSpeedReportStore();
		$controller     = $this->controller( $runtime, true, true, $client, $store );

		$response = $controller->run_pagespeed(
			array(
				'content_id' => 55,
				'strategy'   => 'desktop',
			)
		);

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertSame( 'live', $response['data']['source'] );
		self::assertSame( 'pagespeed_live_result', $response['data']['result_code'] );
		self::assertSame( 'desktop', $response['data']['strategy'] );
		self::assertNotNull( $store->saved_report );
	}

	/**
	 * Build the controller under test.
	 *
	 * @param FakeRestRuntime $runtime Runtime.
	 * @param bool            $content_exists Whether content exists.
	 * @param bool            $enabled Whether PSI integration is enabled.
	 * @param ControllerFakePageSpeedInsightsClient|null $client PSI client.
	 * @param ControllerFakePageSpeedReportStore|null    $store Report store.
	 * @param string          $public_url Safe public URL.
	 * @return PageSpeedInsightsController
	 */
	private function controller(
		FakeRestRuntime $runtime,
		bool $content_exists = true,
		bool $enabled = true,
		?ControllerFakePageSpeedInsightsClient $client = null,
		?ControllerFakePageSpeedReportStore $store = null,
		string $public_url = 'https://example.test/landing-page/'
	): PageSpeedInsightsController {
		$client   = $client ?? new ControllerFakePageSpeedInsightsClient();
		$store    = $store ?? new ControllerFakePageSpeedReportStore();
		$service  = new PageSpeedInsightsService(
			new FakeSettingsRepository( array( 'pagespeed_insights_enabled' => $enabled ) ),
			new FakePageSpeedCredentialsStore( 'saved-key' ),
			$this->content_runtime( $content_exists, $public_url ),
			$client,
			$store
		);

		return new PageSpeedInsightsController(
			$runtime,
			new RestErrorFactory( $runtime ),
			$this->inventory_service( $content_exists, $public_url ),
			$service
		);
	}

	/**
	 * Build the content runtime.
	 *
	 * @param bool   $exists Whether the content exists.
	 * @param string $public_url Public URL.
	 * @return FakeContentInventoryRuntime
	 */
	private function content_runtime( bool $exists, string $public_url ): FakeContentInventoryRuntime {
		$runtime = new FakeContentInventoryRuntime();

		if ( $exists ) {
			$runtime->content[55] = array(
				'type'   => 'page',
				'status' => 'publish',
				'title'  => 'Landing page',
				'body'   => '<img class="wp-image-123" src="https://example.test/wp-content/uploads/hero.jpg">',
			);
			$runtime->public_urls[55] = $public_url;
		}

		return $runtime;
	}

	/**
	 * Build the inventory service dependency.
	 *
	 * @param bool   $exists Whether content exists.
	 * @param string $public_url Public URL.
	 * @return ContentInventoryService
	 */
	private function inventory_service( bool $exists, string $public_url ): ContentInventoryService {
		return new ContentInventoryService(
			$this->content_runtime( $exists, $public_url ),
			new AttachmentStatusReader( new FakeAttachmentMetaStore() ),
			new FakeElementorDocumentDataStore(),
			new ElementorBackgroundDiscovery( new FakeElementorDocumentDataStore() ),
			new TrustedAttachmentMarkerParser()
		);
	}
}

/**
 * Fake PSI client for controller tests.
 */
final class ControllerFakePageSpeedInsightsClient implements PageSpeedInsightsClientInterface {

	/**
	 * Call count.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Prepared result.
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

		return $this->result ?? PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
	}
}

/**
 * Fake PSI report store for controller tests.
 */
final class ControllerFakePageSpeedReportStore implements PageSpeedReportStoreInterface {

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
		return $this->reports[ $strategy ] ?? null;
	}

	/**
	 * Save one report.
	 *
	 * @param PageSpeedReport $report Report payload.
	 * @return bool
	 */
	public function save( PageSpeedReport $report ): bool {
		$this->saved_report                   = $report;
		$this->reports[ $report->strategy() ] = $report;

		return true;
	}
}
