<?php
/**
 * Fake Settings API adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiInterface;

/**
 * Records Settings API calls for unit tests.
 */
final class FakeSettingsApi implements SettingsApiInterface {

	/**
	 * Registered settings.
	 *
	 * @var array<int,array{group:string,name:string,args:array<string,mixed>}>
	 */
	public $settings = array();

	/**
	 * Registered sections.
	 *
	 * @var array<int,array{id:string,title:string,callback:callable,page:string}>
	 */
	public $sections = array();

	/**
	 * Settings errors.
	 *
	 * @var array<int,array{setting:string,code:string,message:string,type:string}>
	 */
	public $errors = array();

	/**
	 * Whether the fake current user has the required capability.
	 *
	 * @var bool
	 */
	public $can = true;

	/**
	 * Capability checks performed.
	 *
	 * @var string[]
	 */
	public $capability_checks = array();

	/**
	 * Register a setting with WordPress.
	 *
	 * @param string              $option_group Option group.
	 * @param string              $option_name Option name.
	 * @param array<string,mixed> $args Registration arguments.
	 * @return void
	 */
	public function register_setting( string $option_group, string $option_name, array $args ): void {
		$this->settings[] = array(
			'group' => $option_group,
			'name'  => $option_name,
			'args'  => $args,
		);
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
		$this->sections[] = array(
			'id'       => $id,
			'title'    => $title,
			'callback' => $callback,
			'page'     => $page,
		);
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
		$this->errors[] = array(
			'setting' => $setting,
			'code'    => $code,
			'message' => $message,
			'type'    => $type,
		);
	}

	/**
	 * Determine whether the current user has a capability.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function current_user_can( string $capability ): bool {
		$this->capability_checks[] = $capability;

		return $this->can;
	}
}
