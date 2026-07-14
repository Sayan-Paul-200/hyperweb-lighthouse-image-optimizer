<?php
/**
 * Offload support diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;

/**
 * Reports current media-offload compatibility status through the existing diagnostics pipeline.
 */
final class OffloadSupportDiagnostics {

	/**
	 * Support service.
	 *
	 * @var OffloadSupportService
	 */
	private $support;

	/**
	 * Create diagnostics.
	 *
	 * @param OffloadSupportService $support Support service.
	 */
	public function __construct( OffloadSupportService $support ) {
		$this->support = $support;
	}

	/**
	 * Run diagnostics.
	 *
	 * @return DiagnosticResult
	 */
	public function run(): DiagnosticResult {
		$site = $this->support->site_support();

		if ( ! $site->plugin_active() ) {
			return new DiagnosticResult(
				'media_offload_support',
				DiagnosticStatus::INFO,
				$site->code(),
				'Media offload compatibility',
				'No supported media offload plugin is active on this site.',
				$site->to_array()
			);
		}

		if ( ! $site->supported() ) {
			return new DiagnosticResult(
				'media_offload_support',
				DiagnosticStatus::WARNING,
				$site->code(),
				'Media offload compatibility',
				$site->message(),
				$site->to_array()
			);
		}

		return new DiagnosticResult(
			'media_offload_support',
			DiagnosticStatus::PASS,
			$site->code(),
			'Media offload compatibility',
			'WP Offload Media compatibility is active.',
			$site->to_array()
		);
	}
}
