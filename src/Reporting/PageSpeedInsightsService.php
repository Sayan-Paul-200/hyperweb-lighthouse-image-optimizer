<?php
/**
 * PageSpeed Insights reporting service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Settings\PageSpeedCredentialsStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Coordinates cached and live PSI reporting for one content record.
 */
final class PageSpeedInsightsService {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Credentials store.
	 *
	 * @var PageSpeedCredentialsStoreInterface
	 */
	private $credentials;

	/**
	 * Content runtime.
	 *
	 * @var ContentInventoryRuntimeInterface
	 */
	private $content;

	/**
	 * PSI client.
	 *
	 * @var PageSpeedInsightsClientInterface
	 */
	private $client;

	/**
	 * Report store.
	 *
	 * @var PageSpeedReportStoreInterface
	 */
	private $store;

	/**
	 * Create the service.
	 *
	 * @param SettingsRepositoryInterface       $settings Settings repository.
	 * @param PageSpeedCredentialsStoreInterface $credentials Credentials store.
	 * @param ContentInventoryRuntimeInterface  $content Content runtime.
	 * @param PageSpeedInsightsClientInterface  $client PSI client.
	 * @param PageSpeedReportStoreInterface     $store Report store.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		PageSpeedCredentialsStoreInterface $credentials,
		ContentInventoryRuntimeInterface $content,
		PageSpeedInsightsClientInterface $client,
		PageSpeedReportStoreInterface $store
	) {
		$this->settings    = $settings;
		$this->credentials = $credentials;
		$this->content     = $content;
		$this->client      = $client;
		$this->store       = $store;
	}

	/**
	 * Whether one strategy is supported.
	 *
	 * @param string $strategy Strategy.
	 * @return bool
	 */
	public function valid_strategy( string $strategy ): bool {
		return in_array( strtolower( trim( $strategy ) ), array( 'mobile', 'desktop' ), true );
	}

	/**
	 * Read one cached PSI report without making an external request.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @return PageSpeedReport
	 */
	public function cached_report( int $content_id, string $strategy = 'mobile' ): PageSpeedReport {
		$strategy            = $this->valid_strategy( $strategy ) ? strtolower( trim( $strategy ) ) : 'mobile';
		$integration_enabled = $this->settings->pagespeed_insights_enabled();
		$public_url          = $this->content->content_public_url( $content_id );

		if ( '' === $public_url ) {
			return PageSpeedReport::unavailable(
				$content_id,
				$strategy,
				'pagespeed_public_url_unavailable',
				$integration_enabled
			);
		}

		$stored = $this->store->read( $content_id, $strategy, $integration_enabled, $public_url );

		if ( $stored instanceof PageSpeedReport ) {
			return $stored;
		}

		return PageSpeedReport::unavailable(
			$content_id,
			$strategy,
			'pagespeed_no_cached_result',
			$integration_enabled,
			$public_url
		);
	}

	/**
	 * Execute one live PSI request and update the cached report on success.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @return PageSpeedExecutionResult
	 */
	public function run_report( int $content_id, string $strategy = 'mobile' ): PageSpeedExecutionResult {
		$strategy = strtolower( trim( $strategy ) );

		if ( ! $this->valid_strategy( $strategy ) ) {
			return PageSpeedExecutionResult::failure( 'invalid_pagespeed_strategy' );
		}

		if ( ! $this->settings->pagespeed_insights_enabled() ) {
			return PageSpeedExecutionResult::failure( 'pagespeed_disabled' );
		}

		$public_url = $this->content->content_public_url( $content_id );

		if ( '' === $public_url ) {
			return PageSpeedExecutionResult::failure( 'pagespeed_public_url_unavailable' );
		}

		$result = $this->client->fetch( $public_url, $strategy, $this->credentials->api_key() );

		if ( ! $result->is_successful() ) {
			return PageSpeedExecutionResult::failure( $result->code() );
		}

		$report = new PageSpeedReport(
			$content_id,
			$strategy,
			PageSpeedReport::SOURCE_LIVE,
			'pagespeed_live_result',
			true,
			$public_url,
			$result->requested_url(),
			$result->final_url(),
			$result->fetched_at_gmt(),
			$result->lab_data(),
			$result->image_audits()
		);

		$this->store->save( $report );

		return PageSpeedExecutionResult::success( $report );
	}
}
