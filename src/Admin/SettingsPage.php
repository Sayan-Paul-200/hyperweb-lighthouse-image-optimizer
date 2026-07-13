<?php
/**
 * Settings admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use HyperWeb\LighthouseImageOptimizer\Settings\StaticSettingsRepository;

/**
 * Placeholder settings page for the admin shell.
 */
final class SettingsPage extends AbstractAdminPage {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Create the page.
	 *
	 * @param SettingsRepositoryInterface|null $settings Settings repository.
	 */
	public function __construct( ?SettingsRepositoryInterface $settings = null ) {
		parent::__construct(
			'Settings',
			'Plugin settings'
		);

		$this->settings = $settings ?? $this->default_repository();
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'settings';
	}

	/**
	 * Render the currently available settings controls.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through local helpers.
		echo '<form method="post" action="options.php" class="hwlio-settings-form">';

		if ( function_exists( 'settings_fields' ) ) {
			settings_fields( SettingsApiRegistrar::OPTION_GROUP );
		}

		echo '<div class="card">';
		echo '<h2>' . $this->escape_html( $this->translate( 'Critical images' ) ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->translate( 'Configure the initial critical-image controls used by the frontend delivery pipeline.' ) ) . '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[critical_logo_enabled]" value="1"' . ( $this->settings->critical_logo_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Treat the site custom logo as a critical image' ) );
		echo '</label>';
		echo '</p>';
		echo '</div>';

		echo '<div class="card">';
		echo '<h2>' . $this->escape_html( $this->translate( 'Compatibility' ) ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->translate( 'Disable only the plugin-owned modules that overlap with other image optimization, delivery, lazy-loading, CDN, or media offload plugins on this site.' ) ) . '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[automatic_optimization]" value="1"' . ( $this->settings->automatic_optimization_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable automatic optimization' ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[delivery_enabled]" value="1"' . ( $this->settings->delivery_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable frontend modern-format delivery' ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[loading_attribute_overrides_enabled]" value="1"' . ( $this->settings->loading_attribute_overrides_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable loading attribute overrides' ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[responsive_preload_enabled]" value="1"' . ( $this->settings->responsive_preload_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable responsive image preload' ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[elementor_background_delivery_enabled]" value="1"' . ( $this->settings->elementor_background_delivery_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable Elementor background delivery' ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="hwlio_settings[critical_background_preload_enabled]" value="1"' . ( $this->settings->critical_background_preload_enabled() ? ' checked' : '' ) . '>';
		echo ' ' . $this->escape_html( $this->translate( 'Enable Elementor hero background preload' ) );
		echo '</label>';
		echo '</p>';

		if ( function_exists( 'submit_button' ) ) {
			submit_button( $this->translate( 'Save Settings' ) );
		} else {
			echo '<p><button type="submit" class="button button-primary">' . $this->escape_html( $this->translate( 'Save Settings' ) ) . '</button></p>';
		}

		echo '</div>';
		echo '</form>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build the default repository for the current runtime.
	 *
	 * @return SettingsRepositoryInterface
	 */
	private function default_repository(): SettingsRepositoryInterface {
		if ( function_exists( 'get_option' ) ) {
			return SettingsRepository::for_wordpress();
		}

		return new StaticSettingsRepository( SettingsSchema::defaults() );
	}
}
