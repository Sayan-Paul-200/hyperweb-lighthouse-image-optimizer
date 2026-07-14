<?php
/**
 * Temporary source lease.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Tracks request-scoped temporary source cleanup.
 */
final class TemporarySourceLease {

	/**
	 * Lease path.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Release callback.
	 *
	 * @var callable
	 */
	private $release;

	/**
	 * Released state.
	 *
	 * @var bool
	 */
	private $released = false;

	/**
	 * Create lease.
	 *
	 * @param string   $path Local temporary path.
	 * @param callable $release Release callback.
	 */
	public function __construct( string $path, callable $release ) {
		$this->path    = str_replace( '\\', '/', trim( $path ) );
		$this->release = $release;
	}

	/**
	 * Get path.
	 *
	 * @return string
	 */
	public function path(): string {
		return $this->path;
	}

	/**
	 * Release the lease idempotently.
	 *
	 * @return void
	 */
	public function release(): void {
		if ( $this->released ) {
			return;
		}

		$this->released = true;

		try {
			call_user_func( $this->release, $this->path );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		}
	}
}
