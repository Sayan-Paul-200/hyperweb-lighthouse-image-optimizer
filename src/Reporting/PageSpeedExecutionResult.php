<?php
/**
 * PageSpeed Insights execution result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries the outcome of one PSI execution request.
 */
final class PageSpeedExecutionResult {

	/**
	 * Whether the execution succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Report payload.
	 *
	 * @var PageSpeedReport|null
	 */
	private $report;

	/**
	 * Create the result.
	 *
	 * @param bool                 $successful Whether the execution succeeded.
	 * @param string               $code Result code.
	 * @param PageSpeedReport|null $report Optional report.
	 */
	public function __construct( bool $successful, string $code, ?PageSpeedReport $report = null ) {
		$this->successful = $successful;
		$this->code       = trim( $code );
		$this->report     = $report;
	}

	/**
	 * Build a success result.
	 *
	 * @param PageSpeedReport $report Report payload.
	 * @return self
	 */
	public static function success( PageSpeedReport $report ): self {
		return new self( true, 'pagespeed_live_result', $report );
	}

	/**
	 * Build a failure result.
	 *
	 * @param string $code Result code.
	 * @return self
	 */
	public static function failure( string $code ): self {
		return new self( false, $code );
	}

	/**
	 * Whether the execution succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get the stable result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get the report payload.
	 *
	 * @return PageSpeedReport|null
	 */
	public function report(): ?PageSpeedReport {
		return $this->report;
	}
}
