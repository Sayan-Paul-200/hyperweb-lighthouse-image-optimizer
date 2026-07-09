<?php
/**
 * Log pruner contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Removes old log rows in bounded batches.
 */
interface LogPrunerInterface {

	/**
	 * Prune old log rows.
	 *
	 * @return int Number of rows removed.
	 */
	public function prune(): int;
}
