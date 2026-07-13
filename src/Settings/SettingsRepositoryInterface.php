<?php
/**
 * Settings repository contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Provides sanitized access to plugin settings.
 */
interface SettingsRepositoryInterface {

	/**
	 * Read settings without forcing persistence.
	 *
	 * @return SettingsResult
	 */
	public function read(): SettingsResult;

	/**
	 * Ensure stored settings are initialized and sanitized.
	 *
	 * @return SettingsResult
	 */
	public function ensure(): SettingsResult;

	/**
	 * Save sanitized settings.
	 *
	 * @param array<mixed> $input Raw settings.
	 * @return SettingsResult
	 */
	public function save( array $input ): SettingsResult;

	/**
	 * Get all sanitized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array;

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null );

	/**
	 * Whether automatic optimization is enabled.
	 *
	 * @return bool
	 */
	public function automatic_optimization_enabled(): bool;

	/**
	 * Whether Media Library controls are enabled.
	 *
	 * @return bool
	 */
	public function media_library_controls_enabled(): bool;

	/**
	 * Whether per-attachment exclusion is allowed.
	 *
	 * @return bool
	 */
	public function attachment_exclusion_allowed(): bool;

	/**
	 * Whether frontend delivery is enabled.
	 *
	 * @return bool
	 */
	public function delivery_enabled(): bool;

	/**
	 * Whether explicit loading-attribute overrides are enabled.
	 *
	 * @return bool
	 */
	public function loading_attribute_overrides_enabled(): bool;

	/**
	 * Whether the site custom logo should be treated as a critical image.
	 *
	 * @return bool
	 */
	public function critical_logo_enabled(): bool;

	/**
	 * Whether responsive preload is enabled for explicit late-discovered critical images.
	 *
	 * @return bool
	 */
	public function responsive_preload_enabled(): bool;

	/**
	 * Whether critical background preload is enabled for an explicitly selected Elementor hero background.
	 *
	 * @return bool
	 */
	public function critical_background_preload_enabled(): bool;

	/**
	 * Whether Elementor background delivery is enabled.
	 *
	 * @return bool
	 */
	public function elementor_background_delivery_enabled(): bool;

	/**
	 * Whether the emergency frontend-delivery rollback switch is active.
	 *
	 * @return bool
	 */
	public function delivery_emergency_disabled(): bool;

	/**
	 * Get enabled target formats.
	 *
	 * @return string[]
	 */
	public function enabled_formats(): array;

	/**
	 * Get target format preference order.
	 *
	 * @return string[]
	 */
	public function format_preference(): array;

	/**
	 * Get conversion quality for a format.
	 *
	 * @param string $format Target format.
	 * @return int
	 */
	public function quality_for( string $format ): int;

	/**
	 * Get minimum savings percent.
	 *
	 * @return int
	 */
	public function minimum_savings_percent(): int;

	/**
	 * Get max retry count.
	 *
	 * @return int
	 */
	public function max_retries(): int;

	/**
	 * Get worker time budget.
	 *
	 * @return int
	 */
	public function worker_time_budget(): int;

	/**
	 * Get queue concurrency.
	 *
	 * @return int
	 */
	public function queue_concurrency(): int;

	/**
	 * Get log retention days.
	 *
	 * @return int
	 */
	public function log_retention_days(): int;

	/**
	 * Whether uninstall should delete data.
	 *
	 * @return bool
	 */
	public function delete_data_on_uninstall(): bool;

	/**
	 * Whether uninstall should delete derivatives.
	 *
	 * @return bool
	 */
	public function delete_derivatives_on_uninstall(): bool;
}
