<?php
/**
 * Fake settings repository for testing.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;

/**
 * Deterministic in-memory settings repository for policy tests.
 */
final class FakeSettingsRepository implements SettingsRepositoryInterface {

	/**
	 * Settings data.
	 *
	 * @var array<string,mixed>
	 */
	public $settings;

	/**
	 * Create the repository.
	 *
	 * @param array<string,mixed> $overrides Setting overrides.
	 */
	public function __construct( array $overrides = array() ) {
		$this->settings = array_replace( SettingsSchema::defaults(), $overrides );
	}

	/**
	 * Read settings without forcing persistence.
	 *
	 * @return SettingsResult
	 */
	public function read(): SettingsResult {
		return new SettingsResult( $this->settings, true, false );
	}

	/**
	 * Ensure stored settings are initialized and sanitized.
	 *
	 * @return SettingsResult
	 */
	public function ensure(): SettingsResult {
		return $this->read();
	}

	/**
	 * Save sanitized settings.
	 *
	 * @param array<mixed> $input Raw settings.
	 * @return SettingsResult
	 */
	public function save( array $input ): SettingsResult {
		$this->settings = array_replace( $this->settings, $input );

		return new SettingsResult( $this->settings, true, true );
	}

	/**
	 * Get all sanitized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		return $this->settings;
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		return array_key_exists( $key, $this->settings ) ? $this->settings[ $key ] : $fallback;
	}

	/**
	 * Whether automatic optimization is enabled.
	 *
	 * @return bool
	 */
	public function automatic_optimization_enabled(): bool {
		return (bool) $this->get( 'automatic_optimization', false );
	}

	/**
	 * Whether Media Library controls are enabled.
	 *
	 * @return bool
	 */
	public function media_library_controls_enabled(): bool {
		return (bool) $this->get( 'media_library_controls', true );
	}

	/**
	 * Whether per-attachment exclusion is allowed.
	 *
	 * @return bool
	 */
	public function attachment_exclusion_allowed(): bool {
		return (bool) $this->get( 'allow_attachment_exclusion', true );
	}

	/**
	 * Whether frontend delivery is enabled.
	 *
	 * @return bool
	 */
	public function delivery_enabled(): bool {
		return (bool) $this->get( 'delivery_enabled', false );
	}

	/**
	 * Whether the site custom logo should be treated as a critical image.
	 *
	 * @return bool
	 */
	public function critical_logo_enabled(): bool {
		return (bool) $this->get( 'critical_logo_enabled', false );
	}

	/**
	 * Whether responsive preload is enabled for explicit late-discovered critical images.
	 *
	 * @return bool
	 */
	public function responsive_preload_enabled(): bool {
		return (bool) $this->get( 'responsive_preload_enabled', false );
	}

	/**
	 * Whether critical background preload is enabled for an explicitly selected Elementor hero background.
	 *
	 * @return bool
	 */
	public function critical_background_preload_enabled(): bool {
		return (bool) $this->get( 'critical_background_preload_enabled', false );
	}

	/**
	 * Whether the emergency delivery rollback switch is active.
	 *
	 * @return bool
	 */
	public function delivery_emergency_disabled(): bool {
		return (bool) $this->get( 'delivery_emergency_disabled', false );
	}

	/**
	 * Get enabled target formats.
	 *
	 * @return string[]
	 */
	public function enabled_formats(): array {
		$value = $this->get( 'enabled_formats', array( 'webp' ) );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Get target format preference order.
	 *
	 * @return string[]
	 */
	public function format_preference(): array {
		$value = $this->get( 'format_preference', array( 'avif', 'webp' ) );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Get conversion quality for a format.
	 *
	 * @param string $format Target format.
	 * @return int
	 */
	public function quality_for( string $format ): int {
		if ( 'avif' === strtolower( $format ) ) {
			return (int) $this->get( 'avif_quality', 60 );
		}

		return (int) $this->get( 'webp_quality', 82 );
	}

	/**
	 * Get minimum savings percent.
	 *
	 * @return int
	 */
	public function minimum_savings_percent(): int {
		return (int) $this->get( 'minimum_savings_percent', 5 );
	}

	/**
	 * Get max retry count.
	 *
	 * @return int
	 */
	public function max_retries(): int {
		return (int) $this->get( 'max_retries', 3 );
	}

	/**
	 * Get worker time budget.
	 *
	 * @return int
	 */
	public function worker_time_budget(): int {
		return (int) $this->get( 'worker_time_budget', 20 );
	}

	/**
	 * Get queue concurrency.
	 *
	 * @return int
	 */
	public function queue_concurrency(): int {
		return (int) $this->get( 'queue_concurrency', 1 );
	}

	/**
	 * Get log retention days.
	 *
	 * @return int
	 */
	public function log_retention_days(): int {
		return (int) $this->get( 'log_retention_days', 30 );
	}

	/**
	 * Whether uninstall should delete data.
	 *
	 * @return bool
	 */
	public function delete_data_on_uninstall(): bool {
		return (bool) $this->get( 'delete_data_on_uninstall', false );
	}

	/**
	 * Whether uninstall should delete derivatives.
	 *
	 * @return bool
	 */
	public function delete_derivatives_on_uninstall(): bool {
		return (bool) $this->get( 'delete_derivatives_on_uninstall', false );
	}
}
