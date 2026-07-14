<?php
/**
 * PageSpeed Insights credentials store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Persists the optional PageSpeed Insights API key separately from general settings.
 */
interface PageSpeedCredentialsStoreInterface {

	/**
	 * Get the stable option name.
	 *
	 * @return string
	 */
	public function option_name(): string;

	/**
	 * Read the normalized credentials payload.
	 *
	 * @return array<string,string>
	 */
	public function all(): array;

	/**
	 * Get the saved API key.
	 *
	 * @return string
	 */
	public function api_key(): string;

	/**
	 * Whether a non-empty API key is stored.
	 *
	 * @return bool
	 */
	public function has_api_key(): bool;

	/**
	 * Normalize and persist one submitted credentials payload.
	 *
	 * @param mixed $input Raw settings payload.
	 * @return array<string,string>
	 */
	public function save_submission( $input ): array;
}
