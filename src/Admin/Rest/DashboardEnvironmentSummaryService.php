<?php
/**
 * Dashboard environment summary service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentReport;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UploadsStatus;
use HyperWeb\LighthouseImageOptimizer\Integration\Conflict\ConflictDetector;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds a cheap dashboard environment summary and conservative conflict list.
 */
final class DashboardEnvironmentSummaryService {

	/**
	 * Environment inspector.
	 *
	 * @var EnvironmentInspector
	 */
	private $inspector;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Conflict detector.
	 *
	 * @var ConflictDetector
	 */
	private $detector;

	/**
	 * Create the service.
	 *
	 * @param EnvironmentInspector        $inspector Environment inspector.
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 * @param ConflictDetector            $detector Conflict detector.
	 */
	public function __construct(
		EnvironmentInspector $inspector,
		SettingsRepositoryInterface $settings,
		ConflictDetector $detector
	) {
		$this->inspector = $inspector;
		$this->settings  = $settings;
		$this->detector  = $detector;
	}

	/**
	 * Build the dashboard environment summary payload.
	 *
	 * @return array<string,mixed>
	 */
	public function build(): array {
		$report          = $this->inspector->inspect();
		$enabled_formats = $this->settings->enabled_formats();
		$available       = array_keys(
			array_filter(
				$report->image_editors(),
				static function ( $value ): bool {
					return true === $value;
				}
			)
		);

		return array(
			'environment' => array(
				'php'                    => array(
					'version'   => $report->php_version(),
					'supported' => $report->php_supported(),
					'minimum'   => $report->minimum_php(),
				),
				'wordpress'              => array(
					'version'   => $report->wordpress_version(),
					'supported' => $report->wordpress_supported(),
					'minimum'   => $report->minimum_wordpress(),
				),
				'image_editors'          => array(
					'candidates' => $report->image_editors(),
					'available'  => array_values( $available ),
				),
				'formats'                => array(
					'webp' => $this->format_summary( $report->support_for( FormatSupportResult::FORMAT_WEBP ), $enabled_formats ),
					'avif' => $this->format_summary( $report->support_for( FormatSupportResult::FORMAT_AVIF ), $enabled_formats ),
				),
				'uploads'                => array(
					'status'   => $report->uploads()->status(),
					'writable' => $report->uploads()->is_writable(),
				),
				'action_scheduler'       => array(
					'status'      => $report->action_scheduler()->status(),
					'loaded'      => $report->action_scheduler()->is_loaded(),
					'initialized' => $report->action_scheduler()->is_initialized(),
				),
				'automatic_optimization' => $this->settings->automatic_optimization_enabled(),
				'delivery_enabled'       => $this->settings->delivery_enabled(),
			),
			'conflicts'   => array_merge(
				$this->conflicts( $report, $enabled_formats, $available ),
				$this->detector->detect()->to_array()
			),
		);
	}

	/**
	 * Build one format summary.
	 *
	 * @param FormatSupportResult $result Format support result.
	 * @param string[]            $enabled_formats Enabled formats.
	 * @return array<string,mixed>
	 */
	private function format_summary( FormatSupportResult $result, array $enabled_formats ): array {
		return array(
			'status'             => $result->status(),
			'reason'             => $result->reason(),
			'enabled'            => in_array( $result->format(), $enabled_formats, true ),
			'supported'          => $result->is_supported(),
			'mime_recognized'    => $result->mime_recognized(),
			'encoding_supported' => $result->encoding_supported(),
		);
	}

	/**
	 * Build conservative conflict warnings from lightweight environment data.
	 *
	 * @param EnvironmentReport $report Environment report.
	 * @param string[]          $enabled_formats Enabled formats.
	 * @param string[]          $available_editors Available editor classes.
	 * @return array<int,array<string,mixed>>
	 */
	private function conflicts( EnvironmentReport $report, array $enabled_formats, array $available_editors ): array {
		$conflicts = array();

		if ( ! $report->php_supported() ) {
			$conflicts[] = array(
				'severity' => 'error',
				'code'     => 'php_unsupported',
				'label'    => 'PHP version',
				'message'  => 'PHP does not meet the plugin minimum version.',
			);
		}

		if ( ! $report->wordpress_supported() ) {
			$conflicts[] = array(
				'severity' => 'error',
				'code'     => 'wordpress_unsupported',
				'label'    => 'WordPress version',
				'message'  => 'WordPress does not meet the plugin minimum version.',
			);
		}

		if ( array() === $available_editors ) {
			$conflicts[] = array(
				'severity' => 'error',
				'code'     => 'no_image_editor_available',
				'label'    => 'Image editors',
				'message'  => 'No supported WordPress image editor is currently available.',
			);
		}

		foreach ( array( FormatSupportResult::FORMAT_WEBP, FormatSupportResult::FORMAT_AVIF ) as $format ) {
			if ( ! in_array( $format, $enabled_formats, true ) ) {
				continue;
			}

			$support = $report->support_for( $format );
			if ( $support->is_supported() ) {
				continue;
			}

			$conflicts[] = array(
				'severity' => FormatSupportResult::STATUS_UNKNOWN === $support->status() ? 'warning' : 'error',
				'code'     => $format . '_' . $support->reason(),
				'label'    => strtoupper( $format ) . ' encoding',
				'message'  => strtoupper( $format ) . ' is enabled but the environment does not currently report ready encode support.',
			);
		}

		if ( UploadsStatus::STATUS_AVAILABLE !== $report->uploads()->status() ) {
			$conflicts[] = array(
				'severity' => UploadsStatus::STATUS_UNKNOWN === $report->uploads()->status() ? 'warning' : 'error',
				'code'     => 'uploads_' . $report->uploads()->status(),
				'label'    => 'Uploads directory',
				'message'  => 'The WordPress uploads directory is not fully ready for reliable optimization work.',
			);
		}

		if ( ActionSchedulerStatus::STATUS_READY !== $report->action_scheduler()->status() ) {
			$conflicts[] = array(
				'severity' => ActionSchedulerStatus::STATUS_MISSING === $report->action_scheduler()->status() ? 'error' : 'warning',
				'code'     => 'action_scheduler_' . $report->action_scheduler()->status(),
				'label'    => 'Action Scheduler',
				'message'  => 'Background processing is not fully ready, so queueing may be delayed or unavailable.',
			);
		}

		return $conflicts;
	}
}
