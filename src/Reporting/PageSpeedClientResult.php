<?php
/**
 * PageSpeed Insights client result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one normalized PSI client response.
 */
final class PageSpeedClientResult {

	/**
	 * Whether the request succeeded.
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
	 * Requested public URL.
	 *
	 * @var string
	 */
	private $requested_url;

	/**
	 * Final PSI URL.
	 *
	 * @var string
	 */
	private $final_url;

	/**
	 * Fetch timestamp.
	 *
	 * @var string
	 */
	private $fetched_at_gmt;

	/**
	 * Lab metrics.
	 *
	 * @var PageSpeedMetrics
	 */
	private $lab_data;

	/**
	 * Image audit summaries.
	 *
	 * @var PageSpeedAuditSummary[]
	 */
	private $image_audits;

	/**
	 * Create the result.
	 *
	 * @param bool                    $successful Whether the request succeeded.
	 * @param string                  $code Result code.
	 * @param string                  $requested_url Requested URL.
	 * @param string                  $final_url Final URL.
	 * @param string                  $fetched_at_gmt Fetch timestamp.
	 * @param PageSpeedMetrics|null   $lab_data Lab metrics.
	 * @param PageSpeedAuditSummary[] $image_audits Image audit summaries.
	 */
	public function __construct(
		bool $successful,
		string $code,
		string $requested_url,
		string $final_url = '',
		string $fetched_at_gmt = '',
		?PageSpeedMetrics $lab_data = null,
		array $image_audits = array()
	) {
		$this->successful     = $successful;
		$this->code           = trim( $code );
		$this->requested_url  = trim( $requested_url );
		$this->final_url      = trim( $final_url );
		$this->fetched_at_gmt = trim( $fetched_at_gmt );
		$this->lab_data       = $lab_data ?? PageSpeedMetrics::empty();
		$this->image_audits   = array_values(
			array_filter(
				$image_audits,
				static function ( $audit ): bool {
					return $audit instanceof PageSpeedAuditSummary && $audit->is_valid();
				}
			)
		);
	}

	/**
	 * Build a failure result.
	 *
	 * @param string $code Result code.
	 * @param string $requested_url Requested URL.
	 * @return self
	 */
	public static function failure( string $code, string $requested_url ): self {
		return new self( false, $code, $requested_url );
	}

	/**
	 * Whether the request succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get the result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get the requested URL.
	 *
	 * @return string
	 */
	public function requested_url(): string {
		return $this->requested_url;
	}

	/**
	 * Get the final URL.
	 *
	 * @return string
	 */
	public function final_url(): string {
		return $this->final_url;
	}

	/**
	 * Get the fetch timestamp.
	 *
	 * @return string
	 */
	public function fetched_at_gmt(): string {
		return $this->fetched_at_gmt;
	}

	/**
	 * Get lab metrics.
	 *
	 * @return PageSpeedMetrics
	 */
	public function lab_data(): PageSpeedMetrics {
		return $this->lab_data;
	}

	/**
	 * Get image audit summaries.
	 *
	 * @return PageSpeedAuditSummary[]
	 */
	public function image_audits(): array {
		return $this->image_audits;
	}
}
