<?php
/**
 * Option store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Wraps WordPress option persistence for testable installers.
 */
interface OptionStoreInterface {

	/**
	 * Get an option value.
	 *
	 * @param string $option Option name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $option, $fallback = false );

	/**
	 * Add an option value.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 * @param bool   $autoload Whether WordPress should autoload the option.
	 * @return bool
	 */
	public function add( string $option, $value, bool $autoload = true ): bool;

	/**
	 * Update an option value.
	 *
	 * @param string    $option Option name.
	 * @param mixed     $value Option value.
	 * @param bool|null $autoload Optional autoload flag.
	 * @return bool
	 */
	public function update( string $option, $value, ?bool $autoload = null ): bool;
}
