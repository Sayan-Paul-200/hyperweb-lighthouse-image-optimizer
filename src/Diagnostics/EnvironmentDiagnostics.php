<?php
/**
 * Environment diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentReport;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\RuntimeConstraints;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UploadsStatus;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds structured environment diagnostics.
 */
final class EnvironmentDiagnostics {

	private const MEMORY_WARNING_BYTES = 134217728;

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
	 * Sanitizer.
	 *
	 * @var DiagnosticSanitizer
	 */
	private $sanitizer;

	/**
	 * Temporary file diagnostic.
	 *
	 * @var TemporaryFileDiagnostic
	 */
	private $temporary_file;

	/**
	 * Sample conversion diagnostic.
	 *
	 * @var SampleConversionDiagnostic
	 */
	private $sample_conversion;

	/**
	 * Create WordPress-backed diagnostics.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			EnvironmentInspector::for_wordpress(),
			SettingsRepository::for_wordpress(),
			new DiagnosticSanitizer(),
			TemporaryFileDiagnostic::for_wordpress(),
			SampleConversionDiagnostic::for_wordpress()
		);
	}

	/**
	 * Create diagnostics.
	 *
	 * @param EnvironmentInspector        $inspector Environment inspector.
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 * @param DiagnosticSanitizer         $sanitizer Sanitizer.
	 * @param TemporaryFileDiagnostic     $temporary_file Temporary file diagnostic.
	 * @param SampleConversionDiagnostic  $sample_conversion Sample conversion diagnostic.
	 */
	public function __construct(
		EnvironmentInspector $inspector,
		SettingsRepositoryInterface $settings,
		DiagnosticSanitizer $sanitizer,
		TemporaryFileDiagnostic $temporary_file,
		SampleConversionDiagnostic $sample_conversion
	) {
		$this->inspector         = $inspector;
		$this->settings          = $settings;
		$this->sanitizer         = $sanitizer;
		$this->temporary_file    = $temporary_file;
		$this->sample_conversion = $sample_conversion;
	}

	/**
	 * Run diagnostics.
	 *
	 * @return DiagnosticReport
	 */
	public function run(): DiagnosticReport {
		$environment     = $this->inspector->inspect();
		$enabled_formats = $this->settings->enabled_formats();
		$uploads         = $environment->uploads();
		$results         = array(
			$this->php_version( $environment ),
			$this->wordpress_version( $environment ),
			$this->image_editors( $environment ),
			$this->format_support( FormatSupportResult::FORMAT_WEBP, $environment, $enabled_formats ),
			$this->format_support( FormatSupportResult::FORMAT_AVIF, $environment, $enabled_formats ),
			$this->upload_base( $uploads ),
			$this->upload_writable( $uploads ),
			$this->temporary_file->run( $uploads->basedir() ),
			$this->memory_limit( $environment->runtime()->memory_limit() ),
			$this->max_execution_time( $environment->runtime() ),
			$this->action_scheduler( $environment->action_scheduler() ),
		);

		foreach ( array( FormatSupportResult::FORMAT_WEBP, FormatSupportResult::FORMAT_AVIF ) as $format ) {
			$results[] = $this->sample_conversion( $format, $environment );
		}

		return new DiagnosticReport(
			array_map(
				array( $this->sanitizer, 'sanitize_result' ),
				$results
			)
		);
	}

	/**
	 * Build PHP version result.
	 *
	 * @param EnvironmentReport $environment Environment.
	 * @return DiagnosticResult
	 */
	private function php_version( EnvironmentReport $environment ): DiagnosticResult {
		return new DiagnosticResult(
			'php_version',
			$environment->php_supported() ? DiagnosticStatus::PASS : DiagnosticStatus::FAIL,
			$environment->php_supported() ? 'php_version_supported' : 'php_version_unsupported',
			'PHP version',
			$environment->php_supported()
				? 'PHP meets the plugin minimum version.'
				: 'PHP does not meet the plugin minimum version.',
			array(
				'current' => $environment->php_version(),
				'minimum' => $environment->minimum_php(),
			)
		);
	}

	/**
	 * Build WordPress version result.
	 *
	 * @param EnvironmentReport $environment Environment.
	 * @return DiagnosticResult
	 */
	private function wordpress_version( EnvironmentReport $environment ): DiagnosticResult {
		if ( null === $environment->wordpress_version() ) {
			return new DiagnosticResult(
				'wordpress_version',
				DiagnosticStatus::WARNING,
				'wordpress_version_unknown',
				'WordPress version',
				'The WordPress version could not be determined.',
				array(
					'minimum' => $environment->minimum_wordpress(),
				)
			);
		}

		return new DiagnosticResult(
			'wordpress_version',
			$environment->wordpress_supported() ? DiagnosticStatus::PASS : DiagnosticStatus::FAIL,
			$environment->wordpress_supported() ? 'wordpress_version_supported' : 'wordpress_version_unsupported',
			'WordPress version',
			$environment->wordpress_supported()
				? 'WordPress meets the plugin minimum version.'
				: 'WordPress does not meet the plugin minimum version.',
			array(
				'current' => $environment->wordpress_version(),
				'minimum' => $environment->minimum_wordpress(),
			)
		);
	}

	/**
	 * Build image editor result.
	 *
	 * @param EnvironmentReport $environment Environment.
	 * @return DiagnosticResult
	 */
	private function image_editors( EnvironmentReport $environment ): DiagnosticResult {
		$editors       = $environment->image_editors();
		$available     = array_keys( array_filter( $editors ) );
		$has_available = array() !== $available;

		if ( array() === $editors ) {
			return new DiagnosticResult(
				'image_editors',
				DiagnosticStatus::WARNING,
				'image_editors_unknown',
				'Image editors',
				'No WordPress image editor candidates were reported.',
				array(
					'candidates' => array(),
				)
			);
		}

		return new DiagnosticResult(
			'image_editors',
			$has_available ? DiagnosticStatus::PASS : DiagnosticStatus::FAIL,
			$has_available ? 'image_editor_available' : 'image_editor_unavailable',
			'Image editors',
			$has_available
				? 'At least one WordPress image editor is available.'
				: 'No WordPress image editor candidate is available.',
			array(
				'candidates' => $editors,
				'available'  => $available,
			)
		);
	}

	/**
	 * Build format support result.
	 *
	 * @param string            $format Format.
	 * @param EnvironmentReport $environment Environment.
	 * @param string[]          $enabled_formats Enabled formats.
	 * @return DiagnosticResult
	 */
	private function format_support( string $format, EnvironmentReport $environment, array $enabled_formats ): DiagnosticResult {
		$support = $environment->support_for( $format );
		$enabled = in_array( $format, $enabled_formats, true );
		$label   = sprintf( '%s encode support', strtoupper( $format ) );
		$details = array(
			'format'             => $support->format(),
			'mime_type'          => $support->mime_type(),
			'support_status'     => $support->status(),
			'reason'             => $support->reason(),
			'mime_recognized'    => $support->mime_recognized(),
			'encoding_supported' => $support->encoding_supported(),
			'enabled'            => $enabled,
		);

		if ( $support->is_supported() ) {
			return new DiagnosticResult(
				'format_support_' . $format,
				DiagnosticStatus::PASS,
				$format . '_encode_supported',
				$label,
				sprintf( '%s encoding is supported.', strtoupper( $format ) ),
				$details
			);
		}

		if ( FormatSupportResult::STATUS_UNKNOWN === $support->status() ) {
			return new DiagnosticResult(
				'format_support_' . $format,
				DiagnosticStatus::WARNING,
				$format . '_encode_unknown',
				$label,
				sprintf( '%s encoding support could not be determined.', strtoupper( $format ) ),
				$details
			);
		}

		return new DiagnosticResult(
			'format_support_' . $format,
			$enabled ? DiagnosticStatus::FAIL : DiagnosticStatus::WARNING,
			$format . '_encode_unavailable',
			$label,
			$enabled
				? sprintf( '%s is enabled but cannot currently be encoded.', strtoupper( $format ) )
				: sprintf( '%s cannot currently be encoded.', strtoupper( $format ) ),
			$details
		);
	}

	/**
	 * Build upload base result.
	 *
	 * @param UploadsStatus $uploads Uploads.
	 * @return DiagnosticResult
	 */
	private function upload_base( UploadsStatus $uploads ): DiagnosticResult {
		$status = $uploads->status();

		if ( UploadsStatus::STATUS_AVAILABLE === $status || UploadsStatus::STATUS_NOT_WRITABLE === $status ) {
			return new DiagnosticResult(
				'upload_base_path',
				DiagnosticStatus::PASS,
				'upload_base_available',
				'Upload base path',
				'The WordPress uploads base path is available.',
				array(
					'status'  => $status,
					'basedir' => $uploads->basedir(),
				)
			);
		}

		return new DiagnosticResult(
			'upload_base_path',
			UploadsStatus::STATUS_UNKNOWN === $status ? DiagnosticStatus::WARNING : DiagnosticStatus::FAIL,
			'upload_base_' . $status,
			'Upload base path',
			'The WordPress uploads base path is not fully available.',
			array(
				'status'  => $status,
				'basedir' => $uploads->basedir(),
				'error'   => $uploads->error(),
			)
		);
	}

	/**
	 * Build upload writable result.
	 *
	 * @param UploadsStatus $uploads Uploads.
	 * @return DiagnosticResult
	 */
	private function upload_writable( UploadsStatus $uploads ): DiagnosticResult {
		$writable = $uploads->is_writable();

		if ( true === $writable ) {
			return new DiagnosticResult(
				'upload_path_writable',
				DiagnosticStatus::PASS,
				'upload_path_writable',
				'Upload path writable',
				'The WordPress uploads path is writable.',
				array(
					'status' => $uploads->status(),
				)
			);
		}

		return new DiagnosticResult(
			'upload_path_writable',
			null === $writable ? DiagnosticStatus::WARNING : DiagnosticStatus::FAIL,
			null === $writable ? 'upload_writable_unknown' : 'upload_path_not_writable',
			'Upload path writable',
			null === $writable
				? 'The plugin could not determine whether uploads are writable.'
				: 'The WordPress uploads path is not writable.',
			array(
				'status'   => $uploads->status(),
				'writable' => $writable,
			)
		);
	}

	/**
	 * Build memory limit result.
	 *
	 * @param MemoryLimit $memory Memory limit.
	 * @return DiagnosticResult
	 */
	private function memory_limit( MemoryLimit $memory ): DiagnosticResult {
		$details = array(
			'raw'       => $memory->raw(),
			'bytes'     => $memory->bytes(),
			'unlimited' => $memory->is_unlimited(),
			'unknown'   => $memory->is_unknown(),
		);

		if ( $memory->is_unlimited() ) {
			return new DiagnosticResult(
				'memory_limit',
				DiagnosticStatus::PASS,
				'memory_limit_unlimited',
				'Memory limit',
				'PHP memory is unlimited for this process.',
				$details
			);
		}

		if ( $memory->is_unknown() || null === $memory->bytes() ) {
			return new DiagnosticResult(
				'memory_limit',
				DiagnosticStatus::WARNING,
				'memory_limit_unknown',
				'Memory limit',
				'The PHP memory limit could not be parsed.',
				$details
			);
		}

		if ( self::MEMORY_WARNING_BYTES > $memory->bytes() ) {
			return new DiagnosticResult(
				'memory_limit',
				DiagnosticStatus::WARNING,
				'memory_limit_low',
				'Memory limit',
				'The PHP memory limit may be low for image processing.',
				$details
			);
		}

		return new DiagnosticResult(
			'memory_limit',
			DiagnosticStatus::PASS,
			'memory_limit_available',
			'Memory limit',
			'The PHP memory limit is available for diagnostics.',
			$details
		);
	}

	/**
	 * Build max execution time result.
	 *
	 * @param RuntimeConstraints $runtime Runtime constraints.
	 * @return DiagnosticResult
	 */
	private function max_execution_time( RuntimeConstraints $runtime ): DiagnosticResult {
		$seconds = $runtime->max_execution_time();

		return new DiagnosticResult(
			'max_execution_time',
			null === $seconds ? DiagnosticStatus::WARNING : DiagnosticStatus::PASS,
			null === $seconds ? 'max_execution_time_unknown' : 'max_execution_time_available',
			'Max execution time',
			null === $seconds
				? 'The PHP max execution time could not be parsed.'
				: 'The PHP max execution time was read successfully.',
			array(
				'raw'     => $runtime->max_execution_time_raw(),
				'seconds' => $seconds,
			)
		);
	}

	/**
	 * Build Action Scheduler result.
	 *
	 * @param ActionSchedulerStatus $status Action Scheduler status.
	 * @return DiagnosticResult
	 */
	private function action_scheduler( ActionSchedulerStatus $status ): DiagnosticResult {
		$diagnostic_status = DiagnosticStatus::WARNING;
		$code              = 'action_scheduler_' . $status->status();
		$message           = 'Action Scheduler readiness could not be fully confirmed.';

		if ( ActionSchedulerStatus::STATUS_READY === $status->status() ) {
			$diagnostic_status = DiagnosticStatus::PASS;
			$message           = 'Action Scheduler is loaded and initialized.';
		} elseif ( ActionSchedulerStatus::STATUS_MISSING === $status->status() ) {
			$diagnostic_status = DiagnosticStatus::FAIL;
			$message           = 'Action Scheduler is not loaded.';
		} elseif ( ActionSchedulerStatus::STATUS_NOT_INITIALIZED === $status->status() ) {
			$message = 'Action Scheduler is loaded but not initialized yet.';
		}

		return new DiagnosticResult(
			'action_scheduler',
			$diagnostic_status,
			$code,
			'Action Scheduler',
			$message,
			array(
				'loaded'      => $status->is_loaded(),
				'initialized' => $status->is_initialized(),
				'status'      => $status->status(),
			)
		);
	}

	/**
	 * Build sample conversion result for a format.
	 *
	 * @param string            $format Format.
	 * @param EnvironmentReport $environment Environment.
	 * @return DiagnosticResult
	 */
	private function sample_conversion( string $format, EnvironmentReport $environment ): DiagnosticResult {
		$support = $environment->support_for( $format );

		if ( ! $support->is_supported() ) {
			return new DiagnosticResult(
				'sample_conversion_' . $format,
				DiagnosticStatus::INFO,
				'sample_conversion_skipped',
				sprintf( 'Sample %s conversion', strtoupper( $format ) ),
				'Sample conversion was skipped because support is not confirmed.',
				array(
					'format'         => $format,
					'support_status' => $support->status(),
				)
			);
		}

		return $this->sample_conversion->run( $format, $environment->uploads()->basedir() );
	}
}
