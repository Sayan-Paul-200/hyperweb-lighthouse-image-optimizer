<?php
/**
 * WordPress HTTP runtime for PageSpeed Insights requests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Executes PSI requests through the WordPress HTTP API.
 */
final class WordPressPageSpeedHttpRuntime implements PageSpeedHttpRuntimeInterface {

	/**
	 * Execute one GET request with query args.
	 *
	 * @param string              $url Base endpoint.
	 * @param array<string,mixed> $query_args Query args.
	 * @return PageSpeedHttpResponse
	 */
	public function get( string $url, array $query_args ): PageSpeedHttpResponse {
		$request_url = function_exists( 'add_query_arg' )
			? (string) \add_query_arg( $query_args, $url )
			: $url;

		$response = function_exists( 'wp_safe_remote_get' )
			? \wp_safe_remote_get(
				$request_url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
				)
			)
			: null;

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $response ) ) {
			return new PageSpeedHttpResponse(
				false,
				0,
				'',
				method_exists( $response, 'get_error_code' ) ? (string) $response->get_error_code() : '',
				method_exists( $response, 'get_error_message' ) ? (string) $response->get_error_message() : ''
			);
		}

		if ( ! is_array( $response ) ) {
			return new PageSpeedHttpResponse( false, 0, '', 'http_unavailable', 'The WordPress HTTP API response was invalid.' );
		}

		$status = function_exists( 'wp_remote_retrieve_response_code' ) ? (int) \wp_remote_retrieve_response_code( $response ) : 0;
		$body   = function_exists( 'wp_remote_retrieve_body' ) ? (string) \wp_remote_retrieve_body( $response ) : '';

		return new PageSpeedHttpResponse( true, $status, $body );
	}
}
