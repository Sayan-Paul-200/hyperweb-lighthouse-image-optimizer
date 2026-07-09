<?php
/**
 * Fake plugin data cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\PluginDataCleanerInterface;

/**
 * Records plugin data cleanup calls.
 */
final class FakePluginDataCleaner implements PluginDataCleanerInterface {

	/**
	 * Number of cleanup calls.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * Delete plugin-owned data.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult {
		++$this->calls;

		return LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_DATA_DELETED ) );
	}
}
