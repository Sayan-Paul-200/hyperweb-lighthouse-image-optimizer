<?php
/**
 * Fake log pruner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogPrunerInterface;

/**
 * Captures retention cleanup calls.
 */
final class FakeLogPruner implements LogPrunerInterface {

	/**
	 * Prune calls.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Prune result.
	 *
	 * @var int
	 */
	public $result = 0;

	/**
	 * Optional per-call results.
	 *
	 * @var int[]
	 */
	public $results = array();

	/**
	 * Whether pruning should throw.
	 *
	 * @var bool
	 */
	public $throw = false;

	/**
	 * Prune old log rows.
	 *
	 * @throws \RuntimeException When configured to fail.
	 * @return int
	 */
	public function prune(): int {
		++$this->calls;

		if ( $this->throw ) {
			throw new \RuntimeException( 'Prune failed.' );
		}

		if ( array() !== $this->results ) {
			return max( 0, (int) array_shift( $this->results ) );
		}

		return $this->result;
	}
}
