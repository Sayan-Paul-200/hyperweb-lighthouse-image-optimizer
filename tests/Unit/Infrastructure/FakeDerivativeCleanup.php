<?php
/**
 * Fake derivative cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\DerivativeCleanupInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;

/**
 * Records derivative cleanup calls.
 */
final class FakeDerivativeCleanup implements DerivativeCleanupInterface {

	/**
	 * Number of cleanup calls.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Delete eligible derivative files.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult {
		++$this->calls;

		return LifecycleResult::success( array( LifecycleResult::CODE_DERIVATIVES_DELETED ) );
	}
}
