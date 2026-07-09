<?php
/**
 * Log writer contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Persists sanitized log entries.
 */
interface LogWriterInterface {

	/**
	 * Persist a log entry.
	 *
	 * @param LogEntry $entry Sanitized entry.
	 * @return bool
	 */
	public function write( LogEntry $entry ): bool;
}
