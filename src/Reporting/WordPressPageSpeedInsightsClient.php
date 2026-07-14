<?php
/**
 * WordPress PageSpeed Insights client.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Fetches and normalizes PSI responses through the WordPress HTTP API.
 */
final class WordPressPageSpeedInsightsClient implements PageSpeedInsightsClientInterface {

	public const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	/**
	 * Supported strategies.
	 *
	 * @var string[]
	 */
	private const STRATEGIES = array( 'mobile', 'desktop' );

	/**
	 * HTTP runtime.
	 *
	 * @var PageSpeedHttpRuntimeInterface
	 */
	private $http;

	/**
	 * Clock.
	 *
	 * @var callable
	 */
	private $clock;

	/**
	 * Create the client.
	 *
	 * @param PageSpeedHttpRuntimeInterface $http HTTP runtime.
	 * @param callable|null                 $clock Optional timestamp provider.
	 */
	public function __construct( PageSpeedHttpRuntimeInterface $http, ?callable $clock = null ) {
		$this->http  = $http;
		$this->clock = $clock ?? static function (): string {
			return gmdate( 'Y-m-d H:i:s' );
		};
	}

	/**
	 * Fetch one PSI report for the given public URL and strategy.
	 *
	 * @param string $public_url Public URL.
	 * @param string $strategy Strategy.
	 * @param string $api_key Optional API key.
	 * @return PageSpeedClientResult
	 */
	public function fetch( string $public_url, string $strategy, string $api_key = '' ): PageSpeedClientResult {
		$public_url = trim( $public_url );
		$strategy   = strtolower( trim( $strategy ) );

		if ( '' === $public_url || ! in_array( $strategy, self::STRATEGIES, true ) ) {
			return PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
		}

		$query_args = array(
			'url'      => $public_url,
			'strategy' => $strategy,
			'category' => 'performance',
		);

		if ( '' !== trim( $api_key ) ) {
			$query_args['key'] = trim( $api_key );
		}

		$response = $this->http->get( self::ENDPOINT, $query_args );

		if ( ! $response->is_successful() ) {
			return PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
		}

		$payload = json_decode( $response->body(), true );

		if ( ! is_array( $payload ) ) {
			return PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
		}

		if ( $this->is_quota_response( $response, $payload ) ) {
			return PageSpeedClientResult::failure( 'pagespeed_quota_exceeded', $public_url );
		}

		if ( 200 !== $response->status_code() ) {
			return PageSpeedClientResult::failure( 'pagespeed_request_failed', $public_url );
		}

		$lighthouse = isset( $payload['lighthouseResult'] ) && is_array( $payload['lighthouseResult'] )
			? $payload['lighthouseResult']
			: array();
		$audits     = isset( $lighthouse['audits'] ) && is_array( $lighthouse['audits'] )
			? $lighthouse['audits']
			: array();
		$final_url  = isset( $lighthouse['finalDisplayedUrl'] ) && is_string( $lighthouse['finalDisplayedUrl'] )
			? trim( $lighthouse['finalDisplayedUrl'] )
			: ( isset( $payload['id'] ) && is_string( $payload['id'] ) ? trim( $payload['id'] ) : $public_url );
		$metrics    = new PageSpeedMetrics(
			array(
				'performance_score'           => $lighthouse['categories']['performance']['score'] ?? null,
				'largest_contentful_paint_ms' => $audits['largest-contentful-paint']['numericValue'] ?? null,
				'cumulative_layout_shift'     => $audits['cumulative-layout-shift']['numericValue'] ?? null,
				'speed_index_ms'              => $audits['speed-index']['numericValue'] ?? null,
				'total_blocking_time_ms'      => $audits['total-blocking-time']['numericValue'] ?? null,
			)
		);

		return new PageSpeedClientResult(
			true,
			'pagespeed_live_result',
			$public_url,
			$final_url,
			call_user_func( $this->clock ),
			$metrics,
			$this->image_audits( $audits )
		);
	}

	/**
	 * Extract normalized image audit summaries.
	 *
	 * @param array<string,mixed> $audits Raw audit payloads.
	 * @return PageSpeedAuditSummary[]
	 */
	private function image_audits( array $audits ): array {
		$rows = array();

		foreach ( array( 'modern-image-formats', 'uses-responsive-images', 'offscreen-images', 'uses-optimized-images' ) as $id ) {
			if ( ! isset( $audits[ $id ] ) || ! is_array( $audits[ $id ] ) ) {
				continue;
			}

			$rows[] = new PageSpeedAuditSummary(
				array(
					'id'            => $id,
					'title'         => $audits[ $id ]['title'] ?? '',
					'score'         => $audits[ $id ]['score'] ?? null,
					'numeric_value' => $audits[ $id ]['numericValue'] ?? null,
					'display_value' => $audits[ $id ]['displayValue'] ?? '',
				)
			);
		}

		return $rows;
	}

	/**
	 * Detect quota-style PSI responses conservatively.
	 *
	 * @param PageSpeedHttpResponse $response HTTP response.
	 * @param array<string,mixed>   $payload Decoded payload.
	 * @return bool
	 */
	private function is_quota_response( PageSpeedHttpResponse $response, array $payload ): bool {
		if ( 429 === $response->status_code() ) {
			return true;
		}

		if ( ! isset( $payload['error'] ) || ! is_array( $payload['error'] ) ) {
			return false;
		}

		$status  = isset( $payload['error']['status'] ) && is_string( $payload['error']['status'] ) ? strtoupper( trim( $payload['error']['status'] ) ) : '';
		$message = isset( $payload['error']['message'] ) && is_string( $payload['error']['message'] ) ? strtolower( trim( $payload['error']['message'] ) ) : '';

		return 'RESOURCE_EXHAUSTED' === $status
			|| false !== strpos( $message, 'quota' )
			|| false !== strpos( $message, 'resource exhausted' );
	}
}
