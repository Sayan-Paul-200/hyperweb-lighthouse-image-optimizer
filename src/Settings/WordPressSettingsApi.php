<?php
/**
 * WordPress Settings API adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Calls WordPress Settings API functions.
 */
final class WordPressSettingsApi implements SettingsApiInterface {

	/**
	 * Register a setting with WordPress.
	 *
	 * @param string              $option_group Option group.
	 * @param string              $option_name Option name.
	 * @param array<string,mixed> $args Registration arguments.
	 * @return void
	 */
	public function register_setting( string $option_group, string $option_name, array $args ): void {
		\register_setting( $option_group, $option_name, $args );
	}

	/**
	 * Register a settings section.
	 *
	 * @param string   $id Section ID.
	 * @param string   $title Section title.
	 * @param callable $callback Section callback.
	 * @param string   $page Settings page slug.
	 * @return void
	 */
	public function add_settings_section( string $id, string $title, callable $callback, string $page ): void {
		\add_settings_section( $id, $title, $callback, $page );
	}

	/**
	 * Add validation feedback for the current settings save.
	 *
	 * @param string $setting Setting name.
	 * @param string $code Stable error code.
	 * @param string $message Human-readable message.
	 * @param string $type Message type.
	 * @return void
	 */
	public function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void {
		\add_settings_error( $setting, $code, $message, $type );
	}

	/**
	 * Determine whether the current user has a capability.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool {
		return \current_user_can( $capability );
	}
}
