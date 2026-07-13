<?php
/**
 * WordPress transient store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Persists ephemeral state through WordPress transients.
 */
final class WordPressTransientStore implements TransientStoreInterface {

	/**
	 * Read one transient value.
	 *
	 * @param string $key Transient key.
	 * @return mixed
	 */
	public function get( string $key ) {
		return \get_transient( $key );
	}

	/**
	 * Persist one transient value.
	 *
	 * @param string $key Transient key.
	 * @param mixed  $value Transient value.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration ): bool {
		return \set_transient( $key, $value, max( 1, $expiration ) );
	}

	/**
	 * Delete one transient value.
	 *
	 * @param string $key Transient key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return \delete_transient( $key );
	}
}
