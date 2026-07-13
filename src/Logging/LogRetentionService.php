<?php
/**
 * Log retention update service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Persists the normalized log retention setting.
 */
final class LogRetentionService {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Create a WordPress-backed retention service.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( SettingsRepository::for_wordpress() );
	}

	/**
	 * Create the service.
	 *
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 */
	public function __construct( SettingsRepositoryInterface $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Save one normalized retention value.
	 *
	 * @param int $retention_days Requested retention days.
	 * @return LogRetentionUpdateResult
	 */
	public function update( int $retention_days ): LogRetentionUpdateResult {
		$result   = $this->settings->save(
			array(
				'log_retention_days' => $retention_days,
			)
		);
		$settings = $result->settings();

		return new LogRetentionUpdateResult(
			isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : $this->settings->log_retention_days()
		);
	}
}
