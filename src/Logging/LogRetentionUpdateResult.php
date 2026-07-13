<?php
/**
 * Log retention update result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries the normalized saved retention value.
 */
final class LogRetentionUpdateResult {

	/**
	 * Saved retention days.
	 *
	 * @var int
	 */
	private $retention_days;

	/**
	 * Create the result.
	 *
	 * @param int $retention_days Saved retention days.
	 */
	public function __construct( int $retention_days ) {
		$this->retention_days = max( 1, $retention_days );
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,int>
	 */
	public function to_array(): array {
		return array(
			'retentionDays' => $this->retention_days,
		);
	}
}
