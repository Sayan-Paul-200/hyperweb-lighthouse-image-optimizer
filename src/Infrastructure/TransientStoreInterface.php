<?php
/**
 * Transient store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Wraps WordPress transient persistence for testable ephemeral state.
 */
interface TransientStoreInterface {

	/**
	 * Read one transient value.
	 *
	 * @param string $key Transient key.
	 * @return mixed
	 */
	public function get( string $key );

	/**
	 * Persist one transient value.
	 *
	 * @param string $key Transient key.
	 * @param mixed  $value Transient value.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration ): bool;

	/**
	 * Delete one transient value.
	 *
	 * @param string $key Transient key.
	 * @return bool
	 */
	public function delete( string $key ): bool;
}
