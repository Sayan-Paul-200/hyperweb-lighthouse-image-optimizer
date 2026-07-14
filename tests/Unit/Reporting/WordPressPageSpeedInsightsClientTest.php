<?php
// phpcs:ignoreFile Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test-only fake HTTP runtime lives with its only consumer.
/**
 * Tests for the WordPress PageSpeed Insights client.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedHttpResponse;
use HyperWeb\LighthouseImageOptimizer\Reporting\PageSpeedHttpRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Reporting\WordPressPageSpeedInsightsClient;
use PHPUnit\Framework\TestCase;

/**
 * Verifies PSI request normalization and safe response shaping.
 */
final class WordPressPageSpeedInsightsClientTest extends TestCase {

	/**
	 * Test anonymous requests use only the conservative expected query arguments.
	 *
	 * @return void
	 */
	public function test_fetch_sends_only_public_url_strategy_category_without_key_when_anonymous(): void {
		$http          = new FakePageSpeedHttpRuntime();
		$http->response = new PageSpeedHttpResponse(
			true,
			200,
			(string) json_encode(
				array(
					'id' => 'https://example.test/page/',
					'lighthouseResult' => array(
						'finalDisplayedUrl' => 'https://example.test/page/',
						'categories' => array(
							'performance' => array(
								'score' => 0.91,
							),
						),
						'audits' => array(
							'largest-contentful-paint' => array( 'numericValue' => 1800 ),
							'cumulative-layout-shift' => array( 'numericValue' => 0.03 ),
							'speed-index' => array( 'numericValue' => 2500 ),
							'total-blocking-time' => array( 'numericValue' => 40 ),
							'modern-image-formats' => array(
								'title' => 'Serve images in next-gen formats',
								'score' => 0.4,
								'displayValue' => 'Potential savings of 128 KiB',
							),
						),
					),
				)
			)
		);
		$client        = new WordPressPageSpeedInsightsClient(
			$http,
			static function (): string {
				return '2026-07-14 12:00:00';
			}
		);

		$result = $client->fetch( 'https://example.test/page/', 'mobile' );

		self::assertTrue( $result->is_successful() );
		self::assertSame( WordPressPageSpeedInsightsClient::ENDPOINT, $http->url );
		self::assertSame(
			array(
				'url' => 'https://example.test/page/',
				'strategy' => 'mobile',
				'category' => 'performance',
			),
			$http->query_args
		);

		$metrics = $result->lab_data()->to_array();

		self::assertSame( 91, $metrics['performance_score'] );
		self::assertSame( 1800, $metrics['largest_contentful_paint_ms'] );
		self::assertSame( 0.03, $metrics['cumulative_layout_shift'] );
		self::assertSame( 2500, $metrics['speed_index_ms'] );
		self::assertSame( 40, $metrics['total_blocking_time_ms'] );
		self::assertCount( 1, $result->image_audits() );
		self::assertSame( 'modern-image-formats', $result->image_audits()[0]->to_array()['id'] );
	}

	/**
	 * Test keyed requests append the key safely.
	 *
	 * @return void
	 */
	public function test_fetch_appends_api_key_when_present(): void {
		$http           = new FakePageSpeedHttpRuntime();
		$http->response = new PageSpeedHttpResponse( true, 200, '{}' );
		$client         = new WordPressPageSpeedInsightsClient( $http );

		$client->fetch( 'https://example.test/page/', 'desktop', 'psi-key-123' );

		self::assertSame( 'psi-key-123', $http->query_args['key'] );
		self::assertSame( 'desktop', $http->query_args['strategy'] );
	}

	/**
	 * Test quota failures are normalized conservatively.
	 *
	 * @return void
	 */
	public function test_fetch_normalizes_quota_responses(): void {
		$http           = new FakePageSpeedHttpRuntime();
		$http->response = new PageSpeedHttpResponse(
			true,
			429,
			(string) json_encode(
				array(
					'error' => array(
						'status' => 'RESOURCE_EXHAUSTED',
						'message' => 'Quota exceeded.',
					),
				)
			)
		);
		$client         = new WordPressPageSpeedInsightsClient( $http );

		$result = $client->fetch( 'https://example.test/page/', 'mobile' );

		self::assertFalse( $result->is_successful() );
		self::assertSame( 'pagespeed_quota_exceeded', $result->code() );
	}
}

/**
 * Fake HTTP runtime for PSI client tests.
 */
final class FakePageSpeedHttpRuntime implements PageSpeedHttpRuntimeInterface {

	/**
	 * Last requested URL.
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Last query arguments.
	 *
	 * @var array<string,mixed>
	 */
	public $query_args = array();

	/**
	 * Prepared response.
	 *
	 * @var PageSpeedHttpResponse|null
	 */
	public $response;

	/**
	 * Execute one GET request.
	 *
	 * @param string $url Endpoint.
	 * @param array  $query_args Query arguments.
	 * @return PageSpeedHttpResponse
	 */
	public function get( string $url, array $query_args ): PageSpeedHttpResponse {
		$this->url        = $url;
		$this->query_args = $query_args;

		return $this->response ?? new PageSpeedHttpResponse( false, 0, '', 'http_unavailable', 'No response configured.' );
	}
}
