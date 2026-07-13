<?php
/**
 * Settings repository.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;

/**
 * Reads and writes sanitized plugin settings.
 */
final class SettingsRepository implements SettingsRepositoryInterface {

	public const OPTION_NAME = 'hwlio_settings';

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Settings sanitizer.
	 *
	 * @var SettingsSanitizer
	 */
	private $sanitizer;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Build a WordPress-backed repository.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return self::for_options( new WordPressOptionStore() );
	}

	/**
	 * Build a repository around an existing option store.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @return self
	 */
	public static function for_options( OptionStoreInterface $options ): self {
		return new self( $options, SettingsSanitizer::for_schema() );
	}

	/**
	 * Create the repository.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @param SettingsSanitizer    $sanitizer Settings sanitizer.
	 * @param string               $option_name Option name.
	 */
	public function __construct(
		OptionStoreInterface $options,
		SettingsSanitizer $sanitizer,
		string $option_name = self::OPTION_NAME
	) {
		$this->options     = $options;
		$this->sanitizer   = $sanitizer;
		$this->option_name = $option_name;
	}

	/**
	 * Read settings without forcing persistence.
	 *
	 * @return SettingsResult
	 */
	public function read(): SettingsResult {
		$raw = $this->options->get( $this->option_name, null );

		if ( null === $raw ) {
			return new SettingsResult(
				SettingsSchema::defaults(),
				true,
				true,
				array( SettingsResult::CODE_INITIALIZED )
			);
		}

		if ( ! is_array( $raw ) ) {
			return new SettingsResult(
				SettingsSchema::defaults(),
				false,
				true,
				array( SettingsResult::CODE_INVALID_REPAIRED ),
				array( 'Stored settings were invalid and require repair.' )
			);
		}

		$result = $this->sanitizer->sanitize( $raw );

		if ( $result->has_changes() ) {
			return $result->with_metadata(
				true,
				true,
				array( SettingsResult::CODE_MERGED )
			);
		}

		return $result->with_metadata(
			true,
			false,
			array( SettingsResult::CODE_LOADED, SettingsResult::CODE_ALREADY_CURRENT )
		);
	}

	/**
	 * Ensure stored settings are initialized and sanitized.
	 *
	 * @return SettingsResult
	 */
	public function ensure(): SettingsResult {
		$read = $this->read();

		if ( ! $read->has_changes() ) {
			return $read;
		}

		$current = $this->options->get( $this->option_name, null );

		if ( null === $current ) {
			$this->persist( $read->settings() );

			return $read->with_metadata(
				$read->is_valid(),
				true,
				array( SettingsResult::CODE_INITIALIZED )
			);
		}

		$this->persist( $read->settings() );

		return $read->with_metadata(
			$read->is_valid(),
			true,
			array( $read->is_valid() ? SettingsResult::CODE_MERGED : SettingsResult::CODE_REPAIRED )
		);
	}

	/**
	 * Save sanitized settings.
	 *
	 * @param array<mixed> $input Raw settings.
	 * @return SettingsResult
	 */
	public function save( array $input ): SettingsResult {
		$result = $this->sanitizer->sanitize( array_replace( $this->all(), $input ) );

		$this->persist( $result->settings() );

		return $result->with_metadata(
			true,
			true,
			array( SettingsResult::CODE_SAVED )
		);
	}

	/**
	 * Get all sanitized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		return $this->read()->settings();
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		$settings = $this->all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
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
	 * Whether the emergency frontend-delivery rollback switch is active.
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
		return $this->format_array( $this->get( 'enabled_formats', array( SettingsSchema::FORMAT_WEBP ) ) );
	}

	/**
	 * Get target format preference order.
	 *
	 * @return string[]
	 */
	public function format_preference(): array {
		return $this->format_array(
			$this->get( 'format_preference', array( SettingsSchema::FORMAT_AVIF, SettingsSchema::FORMAT_WEBP ) )
		);
	}

	/**
	 * Get conversion quality for a format.
	 *
	 * @param string $format Target format.
	 * @return int
	 */
	public function quality_for( string $format ): int {
		$format = strtolower( trim( $format ) );

		if ( SettingsSchema::FORMAT_AVIF === $format ) {
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

	/**
	 * Normalize a setting value to a string array.
	 *
	 * @param mixed $value Setting value.
	 * @return string[]
	 */
	private function format_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$value,
				static function ( $format ): bool {
					return is_string( $format );
				}
			)
		);
	}

	/**
	 * Persist settings with explicit autoload behavior.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return void
	 */
	private function persist( array $settings ): void {
		if ( null === $this->options->get( $this->option_name, null ) ) {
			if ( $this->options->add( $this->option_name, $settings, true ) ) {
				return;
			}
		}

		$this->options->update( $this->option_name, $settings, true );
	}
}
