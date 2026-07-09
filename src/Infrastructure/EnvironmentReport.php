<?php
/**
 * Environment report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Aggregates environment capability facts.
 */
final class EnvironmentReport {

	/**
	 * Current PHP version.
	 *
	 * @var string
	 */
	private $php_version;

	/**
	 * Minimum PHP version.
	 *
	 * @var string
	 */
	private $minimum_php;

	/**
	 * Whether PHP meets the minimum.
	 *
	 * @var bool
	 */
	private $php_supported;

	/**
	 * Current WordPress version.
	 *
	 * @var string|null
	 */
	private $wordpress_version;

	/**
	 * Minimum WordPress version.
	 *
	 * @var string
	 */
	private $minimum_wordpress;

	/**
	 * Whether WordPress meets the minimum.
	 *
	 * @var bool
	 */
	private $wordpress_supported;

	/**
	 * Image editor candidate availability map.
	 *
	 * @var array<string,bool>
	 */
	private $image_editors;

	/**
	 * Format support map.
	 *
	 * @var array<string,FormatSupportResult>
	 */
	private $format_support;

	/**
	 * Uploads status.
	 *
	 * @var UploadsStatus
	 */
	private $uploads;

	/**
	 * Runtime constraints.
	 *
	 * @var RuntimeConstraints
	 */
	private $runtime;

	/**
	 * Action Scheduler status.
	 *
	 * @var ActionSchedulerStatus
	 */
	private $action_scheduler;

	/**
	 * Create the report.
	 *
	 * @param string                            $php_version Current PHP version.
	 * @param string                            $minimum_php Minimum PHP version.
	 * @param bool                              $php_supported Whether PHP is supported.
	 * @param string|null                       $wordpress_version Current WordPress version.
	 * @param string                            $minimum_wordpress Minimum WordPress version.
	 * @param bool                              $wordpress_supported Whether WordPress is supported.
	 * @param array<string,bool>                $image_editors Image editor availability map.
	 * @param array<string,FormatSupportResult> $format_support Format support map.
	 * @param UploadsStatus                     $uploads Uploads status.
	 * @param RuntimeConstraints                $runtime Runtime constraints.
	 * @param ActionSchedulerStatus             $action_scheduler Action Scheduler status.
	 */
	public function __construct(
		string $php_version,
		string $minimum_php,
		bool $php_supported,
		?string $wordpress_version,
		string $minimum_wordpress,
		bool $wordpress_supported,
		array $image_editors,
		array $format_support,
		UploadsStatus $uploads,
		RuntimeConstraints $runtime,
		ActionSchedulerStatus $action_scheduler
	) {
		$this->php_version         = $php_version;
		$this->minimum_php         = $minimum_php;
		$this->php_supported       = $php_supported;
		$this->wordpress_version   = $wordpress_version;
		$this->minimum_wordpress   = $minimum_wordpress;
		$this->wordpress_supported = $wordpress_supported;
		$this->image_editors       = $image_editors;
		$this->format_support      = $format_support;
		$this->uploads             = $uploads;
		$this->runtime             = $runtime;
		$this->action_scheduler    = $action_scheduler;
	}

	/**
	 * Get current PHP version.
	 *
	 * @return string
	 */
	public function php_version(): string {
		return $this->php_version;
	}

	/**
	 * Get minimum PHP version.
	 *
	 * @return string
	 */
	public function minimum_php(): string {
		return $this->minimum_php;
	}

	/**
	 * Whether PHP is supported.
	 *
	 * @return bool
	 */
	public function php_supported(): bool {
		return $this->php_supported;
	}

	/**
	 * Get current WordPress version.
	 *
	 * @return string|null
	 */
	public function wordpress_version(): ?string {
		return $this->wordpress_version;
	}

	/**
	 * Get minimum WordPress version.
	 *
	 * @return string
	 */
	public function minimum_wordpress(): string {
		return $this->minimum_wordpress;
	}

	/**
	 * Whether WordPress is supported.
	 *
	 * @return bool
	 */
	public function wordpress_supported(): bool {
		return $this->wordpress_supported;
	}

	/**
	 * Get image editor candidate availability.
	 *
	 * @return array<string,bool>
	 */
	public function image_editors(): array {
		return $this->image_editors;
	}

	/**
	 * Get all format support results.
	 *
	 * @return array<string,FormatSupportResult>
	 */
	public function format_support(): array {
		return $this->format_support;
	}

	/**
	 * Get format support for a format.
	 *
	 * @param string $format Target format.
	 * @return FormatSupportResult
	 */
	public function support_for( string $format ): FormatSupportResult {
		$format = strtolower( trim( $format ) );

		return $this->format_support[ $format ] ?? FormatSupportResult::unknown(
			$format,
			null,
			null,
			null,
			'unknown_format'
		);
	}

	/**
	 * Get uploads status.
	 *
	 * @return UploadsStatus
	 */
	public function uploads(): UploadsStatus {
		return $this->uploads;
	}

	/**
	 * Get runtime constraints.
	 *
	 * @return RuntimeConstraints
	 */
	public function runtime(): RuntimeConstraints {
		return $this->runtime;
	}

	/**
	 * Get Action Scheduler status.
	 *
	 * @return ActionSchedulerStatus
	 */
	public function action_scheduler(): ActionSchedulerStatus {
		return $this->action_scheduler;
	}
}
