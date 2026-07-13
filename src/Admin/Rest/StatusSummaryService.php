<?php
/**
 * Status summary service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Queue\QueueInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;
use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;

/**
 * Builds the internal status payload used by the admin screen.
 */
final class StatusSummaryService {

	/**
	 * Queue adapter.
	 *
	 * @var QueueInterface
	 */
	private $queue;

	/**
	 * Statistics cache reader.
	 *
	 * @var StatisticsCacheReader
	 */
	private $statistics;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Dashboard environment summary service.
	 *
	 * @var DashboardEnvironmentSummaryService
	 */
	private $environment;

	/**
	 * Recent failure log reader.
	 *
	 * @var RecentFailureLogReader
	 */
	private $recent_failures;

	/**
	 * Refresh service.
	 *
	 * @var StatusRefreshService
	 */
	private $refresh;

	/**
	 * Queue control service.
	 *
	 * @var QueueControlService
	 */
	private $queue_control;

	/**
	 * Create the service.
	 *
	 * @param QueueInterface              $queue Queue adapter.
	 * @param StatisticsCacheReader       $statistics Statistics cache reader.
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 * @param DashboardEnvironmentSummaryService $environment Dashboard environment summary service.
	 * @param RecentFailureLogReader      $recent_failures Recent failure log reader.
	 * @param StatusRefreshService        $refresh Refresh service.
	 * @param QueueControlService         $queue_control Queue control service.
	 */
	public function __construct(
		QueueInterface $queue,
		StatisticsCacheReader $statistics,
		SettingsRepositoryInterface $settings,
		DashboardEnvironmentSummaryService $environment,
		RecentFailureLogReader $recent_failures,
		StatusRefreshService $refresh,
		QueueControlService $queue_control
	) {
		$this->queue           = $queue;
		$this->statistics      = $statistics;
		$this->settings        = $settings;
		$this->environment     = $environment;
		$this->recent_failures = $recent_failures;
		$this->refresh         = $refresh;
		$this->queue_control   = $queue_control;
	}

	/**
	 * Build the status payload.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$statistics  = $this->statistics->read();
		$environment = $this->environment->build();

		return array(
			'queue'          => array(
				'available' => $this->queue->available(),
			),
			'statistics'     => $statistics->to_array(),
			'settings'       => array(
				'automatic_optimization' => $this->settings->automatic_optimization_enabled(),
				'enabled_formats'        => $this->settings->enabled_formats(),
				'delivery_enabled'       => $this->settings->delivery_enabled(),
			),
			'environment'    => $environment['environment'],
			'recentFailures' => $this->recent_failures->read(),
			'conflicts'      => $environment['conflicts'],
			'refresh'        => $this->refresh->summary( $statistics ),
			'queueControl'   => $this->queue_control->summary(),
		);
	}
}
