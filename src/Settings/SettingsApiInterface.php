<?php
/**
 * WordPress Settings API boundary.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Wraps Settings API functions so registration logic remains unit-testable.
 */
interface SettingsApiInterface {

	/**
	 * Register a setting with WordPress.
	 *
	 * @param string              $option_group Option group.
	 * @param string              $option_name Option name.
	 * @param array<string,mixed> $args Registration arguments.
	 * @return void
	 */
	public function register_setting( string $option_group, string $option_name, array $args ): void;

	/**
	 * Register a settings section.
	 *
	 * @param string   $id Section ID.
	 * @param string   $title Section title.
	 * @param callable $callback Section callback.
	 * @param string   $page Settings page slug.
	 * @return void
	 */
	public function add_settings_section( string $id, string $title, callable $callback, string $page ): void;

	/**
	 * Add validation feedback for the current settings save.
	 *
	 * @param string $setting Setting name.
	 * @param string $code Stable error code.
	 * @param string $message Human-readable message.
	 * @param string $type Message type.
	 * @return void
	 */
	public function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void;

	/**
	 * Determine whether the current user has a capability.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool;
}
