<?php
/**
 * PageSpeed report store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Persists normalized PSI reports per content record and strategy.
 */
interface PageSpeedReportStoreInterface {

	/**
	 * Read one stored report.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @param bool   $integration_enabled Whether the live integration is enabled.
	 * @param string $public_url Safe public URL.
	 * @return PageSpeedReport|null
	 */
	public function read( int $content_id, string $strategy, bool $integration_enabled, string $public_url = '' ): ?PageSpeedReport;

	/**
	 * Persist one report.
	 *
	 * @param PageSpeedReport $report Report payload.
	 * @return bool
	 */
	public function save( PageSpeedReport $report ): bool;
}
