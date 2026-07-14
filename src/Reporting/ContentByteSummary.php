<?php
/**
 * Content byte summary value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;

/**
 * Carries summary byte reporting for one content record.
 */
final class ContentByteSummary {

	/**
	 * Actual conversion summary.
	 *
	 * @var array<string,mixed>
	 */
	private $actual_conversion;

	/**
	 * Theoretical page-transfer summary.
	 *
	 * @var array<string,mixed>
	 */
	private $theoretical_page_transfer;

	/**
	 * Create summary.
	 *
	 * @param array<string,mixed> $actual_conversion Actual conversion summary.
	 * @param array<string,mixed> $theoretical_page_transfer Transfer summary.
	 */
	public function __construct( array $actual_conversion, array $theoretical_page_transfer ) {
		$this->actual_conversion        = $this->sanitize_actual_conversion( $actual_conversion );
		$this->theoretical_page_transfer = $this->sanitize_theoretical_page_transfer( $theoretical_page_transfer );
	}

	/**
	 * Get actual conversion summary.
	 *
	 * @return array<string,mixed>
	 */
	public function actual_conversion(): array {
		return $this->actual_conversion;
	}

	/**
	 * Get theoretical page-transfer summary.
	 *
	 * @return array<string,mixed>
	 */
	public function theoretical_page_transfer(): array {
		return $this->theoretical_page_transfer;
	}

	/**
	 * Serialize summary.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'actual_conversion'         => $this->actual_conversion,
			'theoretical_page_transfer' => $this->theoretical_page_transfer,
		);
	}

	/**
	 * Sanitize actual conversion summary.
	 *
	 * @param array<string,mixed> $summary Raw summary.
	 * @return array<string,mixed>
	 */
	private function sanitize_actual_conversion( array $summary ): array {
		$formats = array();

		foreach ( AttachmentStatus::formats() as $format ) {
			$raw              = isset( $summary['formats'][ $format ] ) && is_array( $summary['formats'][ $format ] ) ? $summary['formats'][ $format ] : array();
			$formats[ $format ] = array(
				'sources_ready'   => isset( $raw['sources_ready'] ) && is_numeric( $raw['sources_ready'] ) ? max( 0, (int) $raw['sources_ready'] ) : 0,
				'source_bytes'    => isset( $raw['source_bytes'] ) && is_numeric( $raw['source_bytes'] ) ? max( 0, (int) $raw['source_bytes'] ) : 0,
				'generated_bytes' => isset( $raw['generated_bytes'] ) && is_numeric( $raw['generated_bytes'] ) ? max( 0, (int) $raw['generated_bytes'] ) : 0,
				'savings_bytes'   => isset( $raw['savings_bytes'] ) && is_numeric( $raw['savings_bytes'] ) ? max( 0, (int) $raw['savings_bytes'] ) : 0,
			);
		}

		return array(
			'basis'                  => 'stored_file_sizes',
			'attachments_considered' => isset( $summary['attachments_considered'] ) && is_numeric( $summary['attachments_considered'] ) ? max( 0, (int) $summary['attachments_considered'] ) : 0,
			'source_sizes_represented' => isset( $summary['source_sizes_represented'] ) && is_numeric( $summary['source_sizes_represented'] ) ? max( 0, (int) $summary['source_sizes_represented'] ) : 0,
			'source_bytes'           => isset( $summary['source_bytes'] ) && is_numeric( $summary['source_bytes'] ) ? max( 0, (int) $summary['source_bytes'] ) : 0,
			'generated_bytes'        => isset( $summary['generated_bytes'] ) && is_numeric( $summary['generated_bytes'] ) ? max( 0, (int) $summary['generated_bytes'] ) : 0,
			'savings_bytes'          => isset( $summary['savings_bytes'] ) && is_numeric( $summary['savings_bytes'] ) ? max( 0, (int) $summary['savings_bytes'] ) : 0,
			'savings_percent'        => isset( $summary['savings_percent'] ) && is_numeric( $summary['savings_percent'] ) ? round( max( 0.0, (float) $summary['savings_percent'] ), 2 ) : 0.0,
			'formats'                => $formats,
		);
	}

	/**
	 * Sanitize transfer summary.
	 *
	 * @param array<string,mixed> $summary Raw summary.
	 * @return array<string,mixed>
	 */
	private function sanitize_theoretical_page_transfer( array $summary ): array {
		return array(
			'basis'                        => 'theoretical_best_ready_modern',
			'unique_downloads_considered'  => isset( $summary['unique_downloads_considered'] ) && is_numeric( $summary['unique_downloads_considered'] ) ? max( 0, (int) $summary['unique_downloads_considered'] ) : 0,
			'estimated_downloads'          => isset( $summary['estimated_downloads'] ) && is_numeric( $summary['estimated_downloads'] ) ? max( 0, (int) $summary['estimated_downloads'] ) : 0,
			'estimate_unavailable_downloads' => isset( $summary['estimate_unavailable_downloads'] ) && is_numeric( $summary['estimate_unavailable_downloads'] ) ? max( 0, (int) $summary['estimate_unavailable_downloads'] ) : 0,
			'source_bytes'                 => isset( $summary['source_bytes'] ) && is_numeric( $summary['source_bytes'] ) ? max( 0, (int) $summary['source_bytes'] ) : 0,
			'modern_bytes'                 => isset( $summary['modern_bytes'] ) && is_numeric( $summary['modern_bytes'] ) ? max( 0, (int) $summary['modern_bytes'] ) : 0,
			'savings_bytes'                => isset( $summary['savings_bytes'] ) && is_numeric( $summary['savings_bytes'] ) ? max( 0, (int) $summary['savings_bytes'] ) : 0,
			'savings_percent'              => isset( $summary['savings_percent'] ) && is_numeric( $summary['savings_percent'] ) ? round( max( 0.0, (float) $summary['savings_percent'] ), 2 ) : 0.0,
		);
	}
}
