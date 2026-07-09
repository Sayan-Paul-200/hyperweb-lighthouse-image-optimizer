<?php
/**
 * Fake log writer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogEntry;
use HyperWeb\LighthouseImageOptimizer\Logging\LogWriterInterface;

/**
 * Captures log entries for logger tests.
 */
final class FakeLogWriter implements LogWriterInterface {

	/**
	 * Write result.
	 *
	 * @var bool
	 */
	public $result = true;

	/**
	 * Whether writes should throw.
	 *
	 * @var bool
	 */
	public $throw = false;

	/**
	 * Last written entry.
	 *
	 * @var LogEntry|null
	 */
	public $entry;

	/**
	 * Persist a log entry.
	 *
	 * @param LogEntry $entry Sanitized entry.
	 * @throws \RuntimeException When configured to fail.
	 * @return bool
	 */
	public function write( LogEntry $entry ): bool {
		if ( $this->throw ) {
			throw new \RuntimeException( 'Writer failed.' );
		}

		$this->entry = $entry;

		return $this->result;
	}
}
