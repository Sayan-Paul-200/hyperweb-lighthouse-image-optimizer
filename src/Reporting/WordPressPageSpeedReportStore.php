<?php
/**
 * WordPress-backed PageSpeed report store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Persists normalized PSI reports in plugin-owned post meta.
 */
final class WordPressPageSpeedReportStore implements PageSpeedReportStoreInterface {

	public const META_KEY = '_hwlio_pagespeed_reports';

	/**
	 * Read one stored report.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @param bool   $integration_enabled Whether the live integration is enabled.
	 * @param string $public_url Safe public URL.
	 * @return PageSpeedReport|null
	 */
	public function read( int $content_id, string $strategy, bool $integration_enabled, string $public_url = '' ): ?PageSpeedReport {
		if ( $content_id < 1 || ! function_exists( 'get_post_meta' ) ) {
			return null;
		}

		$stored = \get_post_meta( $content_id, self::META_KEY, true );

		if ( ! is_array( $stored ) || ! isset( $stored[ $strategy ] ) || ! is_array( $stored[ $strategy ] ) ) {
			return null;
		}

		return PageSpeedReport::from_storage( $content_id, $strategy, $stored[ $strategy ], $integration_enabled, $public_url );
	}

	/**
	 * Persist one report.
	 *
	 * @param PageSpeedReport $report Report payload.
	 * @return bool
	 */
	public function save( PageSpeedReport $report ): bool {
		if ( $report->content_id() < 1 || ! function_exists( 'get_post_meta' ) || ! function_exists( 'update_post_meta' ) ) {
			return false;
		}

		$stored = \get_post_meta( $report->content_id(), self::META_KEY, true );
		$stored = is_array( $stored ) ? $stored : array();
		$stored[ $report->strategy() ] = $report->to_storage_array();

		return false !== \update_post_meta( $report->content_id(), self::META_KEY, $stored );
	}
}
