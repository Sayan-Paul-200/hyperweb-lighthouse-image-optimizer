<?php
/**
 * Settings API registration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers plugin settings with the WordPress Settings API.
 */
final class SettingsApiRegistrar implements HookProviderInterface {

	public const OPTION_GROUP = 'hwlio_settings';
	public const PAGE_SLUG    = 'hwlio-settings';
	public const PRIORITY     = 10;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $repository;

	/**
	 * Settings sanitizer.
	 *
	 * @var SettingsSanitizer
	 */
	private $sanitizer;

	/**
	 * Settings API adapter.
	 *
	 * @var SettingsApiInterface
	 */
	private $settings_api;

	/**
	 * Format support checker.
	 *
	 * @var FormatSupportProviderInterface
	 */
	private $format_support;

	/**
	 * Required capability for administrator-managed settings.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * PageSpeed credentials store.
	 *
	 * @var PageSpeedCredentialsStoreInterface|null
	 */
	private $pagespeed_credentials;

	/**
	 * Create a WordPress-backed registrar.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			SettingsRepository::for_wordpress(),
			SettingsSanitizer::for_schema(),
			new WordPressSettingsApi(),
			EnvironmentInspector::for_wordpress(),
			SettingsSchema::CAPABILITY_MANAGE_OPTIONS,
			WordPressPageSpeedCredentialsStore::for_wordpress()
		);
	}

	/**
	 * Create the registrar.
	 *
	 * @param SettingsRepositoryInterface             $repository Settings repository.
	 * @param SettingsSanitizer                       $sanitizer Settings sanitizer.
	 * @param SettingsApiInterface                    $settings_api Settings API adapter.
	 * @param FormatSupportProviderInterface          $format_support Format support checker.
	 * @param string                                  $capability Required capability.
	 * @param PageSpeedCredentialsStoreInterface|null $pagespeed_credentials PageSpeed credentials store.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		SettingsSanitizer $sanitizer,
		SettingsApiInterface $settings_api,
		FormatSupportProviderInterface $format_support,
		string $capability = SettingsSchema::CAPABILITY_MANAGE_OPTIONS,
		?PageSpeedCredentialsStoreInterface $pagespeed_credentials = null
	) {
		$this->repository            = $repository;
		$this->sanitizer             = $sanitizer;
		$this->settings_api          = $settings_api;
		$this->format_support        = $format_support;
		$this->capability            = $capability;
		$this->pagespeed_credentials = $pagespeed_credentials;
	}

	/**
	 * Register Settings API hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'admin_init', array( $this, 'register_settings' ), self::PRIORITY, 0 );
	}

	/**
	 * Register settings and sections with WordPress.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$this->settings_api->register_setting(
			self::OPTION_GROUP,
			SettingsRepository::OPTION_NAME,
			array(
				'type'              => 'array',
				'description'       => 'HyperWeb Lighthouse Image Optimizer settings.',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => SettingsSchema::defaults(),
				'show_in_rest'      => false,
			)
		);

		if ( $this->pagespeed_credentials instanceof PageSpeedCredentialsStoreInterface ) {
			$this->settings_api->register_setting(
				self::OPTION_GROUP,
				$this->pagespeed_credentials->option_name(),
				array(
					'type'              => 'array',
					'description'       => 'HyperWeb Lighthouse Image Optimizer PageSpeed Insights credentials.',
					'sanitize_callback' => array( $this, 'sanitize_pagespeed_credentials' ),
					'default'           => $this->pagespeed_credentials->all(),
					'show_in_rest'      => false,
				)
			);
		}

		foreach ( $this->sections() as $id => $title ) {
			$this->settings_api->add_settings_section(
				$id,
				$title,
				array( $this, 'render_section' ),
				self::PAGE_SLUG
			);
		}
	}

	/**
	 * Sanitize settings submitted through the Settings API.
	 *
	 * @param mixed $input Raw Settings API payload.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ): array {
		$current = $this->repository->all();

		if ( ! $this->settings_api->current_user_can( $this->capability ) ) {
			$this->add_error(
				'unauthorized_settings_save',
				'You do not have permission to change HyperWeb Lighthouse Image Optimizer settings.',
				'error'
			);

			return $current;
		}

		$payload_is_array = is_array( $input );
		$payload          = $payload_is_array ? $input : array();

		if ( ! $payload_is_array ) {
			$this->add_error(
				'invalid_settings_payload',
				'The submitted settings payload was invalid, so existing settings were preserved.',
				'error'
			);
		}

		$result   = $this->sanitizer->sanitize( array_replace( $current, $payload ) );
		$settings = $this->guard_enabled_formats( $result->settings(), $current );

		$this->record_sanitization_feedback( $result );

		return $settings;
	}

	/**
	 * Sanitize and persist the optional PageSpeed Insights credentials payload.
	 *
	 * @param mixed $input Raw payload.
	 * @return array<string,string>
	 */
	public function sanitize_pagespeed_credentials( $input ): array {
		if ( ! $this->pagespeed_credentials instanceof PageSpeedCredentialsStoreInterface ) {
			return array(
				'api_key' => '',
			);
		}

		if ( ! $this->settings_api->current_user_can( $this->capability ) ) {
			$this->add_error(
				'unauthorized_pagespeed_credentials_save',
				'You do not have permission to change HyperWeb Lighthouse Image Optimizer PageSpeed credentials.',
				'error'
			);

			return $this->pagespeed_credentials->all();
		}

		$payload_is_array = is_array( $input );
		$payload          = $payload_is_array ? $input : array();

		if ( ! $payload_is_array ) {
			$this->add_error(
				'invalid_pagespeed_credentials_payload',
				'The submitted PageSpeed Insights credentials payload was invalid, so the existing value was preserved.',
				'error'
			);
		}

		return $this->pagespeed_credentials->save_submission( $payload );
	}

	/**
	 * Section callback reserved for the later visible settings screen.
	 *
	 * @return void
	 */
	public function render_section(): void {
	}

	/**
	 * Get Settings API sections.
	 *
	 * @return array<string,string>
	 */
	public function sections(): array {
		return array(
			SettingsSchema::GROUP_GENERAL  => 'General',
			SettingsSchema::GROUP_FORMATS  => 'Formats and quality',
			SettingsSchema::GROUP_PROCESS  => 'Processing',
			SettingsSchema::GROUP_DELIVERY => 'Delivery',
			SettingsSchema::GROUP_LOGGING  => 'Logging and cleanup',
			SettingsSchema::GROUP_ADVANCED => 'Advanced exclusions',
		);
	}

	/**
	 * Prevent unsupported formats from being newly enabled.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param array<string,mixed> $current Current persisted settings.
	 * @return array<string,mixed>
	 */
	private function guard_enabled_formats( array $settings, array $current ): array {
		$enabled     = $this->format_list( $settings['enabled_formats'] ?? array() );
		$allowed     = array();
		$unsupported = array();

		foreach ( $enabled as $format ) {
			if ( $this->format_support->support_for( $format )->blocks_enablement() ) {
				$unsupported[] = $format;
				continue;
			}

			$allowed[] = $format;
		}

		if ( array() === $unsupported ) {
			$settings['enabled_formats'] = $allowed;

			return $settings;
		}

		$this->add_error(
			'unsupported_enabled_formats',
			sprintf(
				'Unsupported image formats were not enabled: %s.',
				implode( ', ', array_map( 'strtoupper', $unsupported ) )
			),
			'error'
		);

		if ( array() === $allowed ) {
			$settings['enabled_formats'] = $this->format_list( $current['enabled_formats'] ?? array() );
			$this->add_error(
				'enabled_formats_preserved',
				'The previous enabled formats were preserved because no submitted format is currently supported.',
				'error'
			);

			return $settings;
		}

		$settings['enabled_formats'] = $allowed;

		return $settings;
	}

	/**
	 * Record generic settings normalization feedback.
	 *
	 * @param SettingsResult $result Settings result.
	 * @return void
	 */
	private function record_sanitization_feedback( SettingsResult $result ): void {
		if ( $result->has_code( SettingsResult::CODE_UNKNOWN_KEYS_DROPPED ) ) {
			$this->add_error(
				'unknown_settings_dropped',
				'Unknown settings were ignored.',
				'warning'
			);
		}

		if ( $result->has_changes() ) {
			$this->add_error(
				'settings_normalized',
				'Some settings were normalized before saving.',
				'warning'
			);
		}
	}

	/**
	 * Add settings feedback.
	 *
	 * @param string $code Stable error code.
	 * @param string $message Message.
	 * @param string $type Message type.
	 * @return void
	 */
	private function add_error( string $code, string $message, string $type ): void {
		$this->settings_api->add_settings_error(
			SettingsRepository::OPTION_NAME,
			$code,
			$message,
			$type
		);
	}

	/**
	 * Normalize a value to a string list.
	 *
	 * @param mixed $value Value.
	 * @return string[]
	 */
	private function format_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$formats = array();

		foreach ( $value as $format ) {
			if ( is_string( $format ) ) {
				$formats[] = $format;
			}
		}

		return array_values( $formats );
	}
}
