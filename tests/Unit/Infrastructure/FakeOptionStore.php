<?php
/**
 * In-memory option store for installer tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;

/**
 * Provides in-memory option persistence.
 */
final class FakeOptionStore implements OptionStoreInterface {

	/**
	 * Stored option values.
	 *
	 * @var array<string,mixed>
	 */
	public $options = array();

	/**
	 * Stored autoload flags.
	 *
	 * @var array<string,string>
	 */
	public $autoload = array();

	/**
	 * Create the store.
	 *
	 * @param array<string,mixed> $options Initial options.
	 */
	public function __construct( array $options = array() ) {
		$this->options = $options;
	}

	/**
	 * Get an option value.
	 *
	 * @param string $option Option name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( string $option, $fallback = false ) {
		return array_key_exists( $option, $this->options ) ? $this->options[ $option ] : $fallback;
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
		if ( array_key_exists( $option, $this->options ) ) {
			return false;
		}

		$this->options[ $option ]  = $value;
		$this->autoload[ $option ] = $autoload ? 'yes' : 'no';

		return true;
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
		$this->options[ $option ] = $value;

		if ( null !== $autoload ) {
			$this->autoload[ $option ] = $autoload ? 'yes' : 'no';
		}

		return true;
	}

	/**
	 * Delete an option value.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	public function delete( string $option ): bool {
		if ( ! array_key_exists( $option, $this->options ) ) {
			return false;
		}

		unset( $this->options[ $option ], $this->autoload[ $option ] );

		return true;
	}
}
