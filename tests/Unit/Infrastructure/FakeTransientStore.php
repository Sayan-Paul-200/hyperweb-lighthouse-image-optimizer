<?php
/**
 * In-memory transient store for tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\TransientStoreInterface;

/**
 * Stores expiring transient values in memory for unit tests.
 */
final class FakeTransientStore implements TransientStoreInterface {

	/**
	 * Stored values.
	 *
	 * @var array<string,mixed>
	 */
	public $values = array();

	/**
	 * Expiration timestamps keyed by transient.
	 *
	 * @var array<string,int>
	 */
	public $expires_at = array();

	/**
	 * Whether set operations should fail.
	 *
	 * @var bool
	 */
	public $fail_sets = false;

	/**
	 * Current fake time.
	 *
	 * @var int
	 */
	public $now = 1783814400;

	/**
	 * Read one transient value.
	 *
	 * @param string $key Transient key.
	 * @return mixed
	 */
	public function get( string $key ) {
		if ( isset( $this->expires_at[ $key ] ) && $this->expires_at[ $key ] <= $this->now ) {
			unset( $this->values[ $key ], $this->expires_at[ $key ] );

			return false;
		}

		return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : false;
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
		if ( $this->fail_sets ) {
			return false;
		}

		$this->values[ $key ]      = $value;
		$this->expires_at[ $key ] = $this->now + max( 1, $expiration );

		return true;
	}

	/**
	 * Delete one transient value.
	 *
	 * @param string $key Transient key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		$exists = array_key_exists( $key, $this->values ) || array_key_exists( $key, $this->expires_at );

		unset( $this->values[ $key ], $this->expires_at[ $key ] );

		return $exists;
	}
}
