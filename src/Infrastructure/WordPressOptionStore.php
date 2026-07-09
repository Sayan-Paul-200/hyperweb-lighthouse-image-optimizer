<?php
/**
 * WordPress option store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Persists options through WordPress core APIs.
 */
final class WordPressOptionStore implements OptionStoreInterface {

	/**
	 * Get an option value.
	 *
	 * @param string $option Option name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $option, $fallback = false ) {
		return \get_option( $option, $fallback );
	}

	/**
	 * Add an option value.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 * @param bool   $autoload Whether WordPress should autoload the option.
	 * @return bool
	 */
	public function add( string $option, $value, bool $autoload = true ): bool {
		return \add_option( $option, $value, '', $autoload );
	}

	/**
	 * Update an option value.
	 *
	 * @param string    $option Option name.
	 * @param mixed     $value Option value.
	 * @param bool|null $autoload Optional autoload flag.
	 * @return bool
	 */
	public function update( string $option, $value, ?bool $autoload = null ): bool {
		return \update_option( $option, $value, $autoload );
	}
}
