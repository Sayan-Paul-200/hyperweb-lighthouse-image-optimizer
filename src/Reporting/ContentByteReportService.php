<?php
/**
 * Content byte report service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds conservative measured and theoretical byte reporting for one content record.
 */
final class ContentByteReportService {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Occurrence asset mapper.
	 *
	 * @var OccurrenceAssetMapper
	 */
	private $mapper;

	/**
	 * Create service.
	 *
	 * @param SettingsRepositoryInterface $settings Settings repository.
	 * @param DerivativeRepository        $repository Derivative repository.
	 * @param ImageFileProbeInterface     $files File probe.
	 * @param OccurrenceAssetMapper       $mapper Occurrence asset mapper.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		DerivativeRepository $repository,
		ImageFileProbeInterface $files,
		OccurrenceAssetMapper $mapper
	) {
		$this->settings   = $settings;
		$this->repository = $repository;
		$this->files      = $files;
		$this->mapper     = $mapper;
	}

	/**
	 * Build the byte report for one content snapshot.
	 *
	 * @param ContentInventorySnapshot $snapshot Inventory snapshot.
	 * @return ContentByteReport
	 */
	public function report( ContentInventorySnapshot $snapshot ): ContentByteReport {
		$occurrences = array();

		foreach ( $snapshot->occurrences() as $occurrence ) {
			$occurrences[] = $this->build_occurrence_report( $occurrence );
		}

		return new ContentByteReport(
			new ContentByteSummary(
				$this->build_actual_conversion_summary( $snapshot ),
				$this->build_theoretical_transfer_summary( $occurrences )
			),
			$occurrences
		);
	}

	/**
	 * Build measured attachment-centric conversion totals.
	 *
	 * @param ContentInventorySnapshot $snapshot Inventory snapshot.
	 * @return array<string,mixed>
	 */
	private function build_actual_conversion_summary( ContentInventorySnapshot $snapshot ): array {
		$attachment_ids = array();
		$totals         = array(
			'attachments_considered'   => 0,
			'source_sizes_represented' => 0,
			'source_bytes'             => 0,
			'generated_bytes'          => 0,
			'savings_bytes'            => 0,
			'savings_percent'          => 0.0,
			'formats'                  => $this->empty_format_totals(),
		);

		foreach ( $snapshot->occurrences() as $occurrence ) {
			if ( PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT !== $occurrence->origin() || null === $occurrence->attachment_id() ) {
				continue;
			}

			$attachment_ids[ $occurrence->attachment_id() ] = true;
		}

		$totals['attachments_considered'] = count( $attachment_ids );

		foreach ( array_keys( $attachment_ids ) as $attachment_id ) {
			$manifest = $this->repository->read( (int) $attachment_id )->manifest();
			$this->accumulate_actual_manifest( $manifest, $totals );
		}

		$totals['savings_percent'] = 0 < $totals['source_bytes']
			? round( ( $totals['savings_bytes'] / $totals['source_bytes'] ) * 100, 2 )
			: 0.0;

		return $totals;
	}

	/**
	 * Accumulate one manifest into actual totals.
	 *
	 * @param DerivativeManifest  $manifest Manifest.
	 * @param array<string,mixed> $totals Totals.
	 * @return void
	 */
	private function accumulate_actual_manifest( DerivativeManifest $manifest, array &$totals ): void {
		foreach ( $manifest->sizes() as $size ) {
			if ( ! isset( $size['source'], $size['formats'] ) || ! is_array( $size['source'] ) || ! is_array( $size['formats'] ) ) {
				continue;
			}

			$source_bytes = isset( $size['source']['bytes'] ) && is_numeric( $size['source']['bytes'] )
				? max( 0, (int) $size['source']['bytes'] )
				: 0;
			$best_bytes   = null;

			foreach ( AttachmentStatus::formats() as $format ) {
				if ( ! isset( $size['formats'][ $format ] ) || ! is_array( $size['formats'][ $format ] ) ) {
					continue;
				}

				$entry_bytes   = isset( $size['formats'][ $format ]['bytes'] ) && is_numeric( $size['formats'][ $format ]['bytes'] )
					? max( 0, (int) $size['formats'][ $format ]['bytes'] )
					: 0;
				$savings_bytes = isset( $size['formats'][ $format ]['savings_bytes'] ) && is_numeric( $size['formats'][ $format ]['savings_bytes'] )
					? max( 0, (int) $size['formats'][ $format ]['savings_bytes'] )
					: max( 0, $source_bytes - $entry_bytes );

				++$totals['formats'][ $format ]['sources_ready'];
				$totals['formats'][ $format ]['source_bytes']    += $source_bytes;
				$totals['formats'][ $format ]['generated_bytes'] += $entry_bytes;
				$totals['formats'][ $format ]['savings_bytes']   += $savings_bytes;

				if ( null === $best_bytes || $entry_bytes < $best_bytes ) {
					$best_bytes = $entry_bytes;
				}
			}

			if ( null === $best_bytes ) {
				continue;
			}

			++$totals['source_sizes_represented'];
			$totals['source_bytes']    += $source_bytes;
			$totals['generated_bytes'] += $best_bytes;
			$totals['savings_bytes']   += max( 0, $source_bytes - $best_bytes );
		}
	}

	/**
	 * Build one occurrence-centric transfer summary.
	 *
	 * @param ByteOccurrenceReport[] $occurrences Occurrence rows.
	 * @return array<string,mixed>
	 */
	private function build_theoretical_transfer_summary( array $occurrences ): array {
		$summary = array(
			'unique_downloads_considered'    => 0,
			'estimated_downloads'            => 0,
			'estimate_unavailable_downloads' => 0,
			'source_bytes'                   => 0,
			'modern_bytes'                   => 0,
			'savings_bytes'                  => 0,
			'savings_percent'                => 0.0,
		);
		$seen    = array();

		foreach ( $occurrences as $occurrence ) {
			if ( ! $occurrence instanceof ByteOccurrenceReport ) {
				continue;
			}

			$download_key = $occurrence->download_key();

			if ( '' === $download_key || isset( $seen[ $download_key ] ) ) {
				continue;
			}

			$seen[ $download_key ] = true;
			++$summary['unique_downloads_considered'];

			if ( null !== $occurrence->source_bytes() ) {
				$summary['source_bytes'] += $occurrence->source_bytes();
			}

			if ( 'estimated' === $occurrence->estimate_status() ) {
				++$summary['estimated_downloads'];
				$summary['modern_bytes']  += null !== $occurrence->modern_bytes() ? $occurrence->modern_bytes() : 0;
				$summary['savings_bytes'] += null !== $occurrence->savings_bytes() ? $occurrence->savings_bytes() : 0;
			} else {
				++$summary['estimate_unavailable_downloads'];
			}
		}

		$summary['savings_percent'] = 0 < $summary['source_bytes']
			? round( ( $summary['savings_bytes'] / $summary['source_bytes'] ) * 100, 2 )
			: 0.0;

		return $summary;
	}

	/**
	 * Build one occurrence row.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ByteOccurrenceReport
	 */
	private function build_occurrence_report( InventoryOccurrence $occurrence ): ByteOccurrenceReport {
		$base = array(
			'occurrence_id' => $occurrence->id(),
			'source'        => $occurrence->source(),
			'presentation'  => $occurrence->presentation(),
			'origin'        => $occurrence->origin(),
			'attachment_id' => $occurrence->attachment_id(),
			'url'           => $occurrence->url(),
			'download_key'  => '',
			'basis'         => 'unavailable',
		);

		if ( PageInventoryItem::ORIGIN_EXTERNAL === $occurrence->origin() ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'external_image',
					)
				)
			);
		}

		if ( PageInventoryItem::ORIGIN_UNKNOWN === $occurrence->origin() ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'unknown_reference',
					)
				)
			);
		}

		$mapping = $this->mapper->map( $occurrence );

		if ( PageInventoryItem::ORIGIN_LOCAL_UNREGISTERED === $occurrence->origin() ) {
			return $this->local_unregistered_row( $occurrence, $mapping, $base );
		}

		if ( PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT !== $occurrence->origin() || null === $occurrence->attachment_id() ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'unknown_reference',
					)
				)
			);
		}

		return PageInventoryItem::PRESENTATION_BACKGROUND === $occurrence->presentation()
			? $this->background_attachment_row( $occurrence, $mapping, $base )
			: $this->inline_attachment_row( $occurrence, $mapping, $base );
	}

	/**
	 * Build a row for a local unregistered URL.
	 *
	 * @param InventoryOccurrence    $occurrence Occurrence.
	 * @param OccurrenceAssetMapping $mapping Mapping.
	 * @param array<string,mixed>    $base Base row.
	 * @return ByteOccurrenceReport
	 */
	private function local_unregistered_row( InventoryOccurrence $occurrence, OccurrenceAssetMapping $mapping, array $base ): ByteOccurrenceReport {
		$source_bytes = $this->source_bytes_from_mapping( $mapping );
		$url          = null !== $occurrence->url() ? $occurrence->url() : '';

		return new ByteOccurrenceReport(
			array_merge(
				$base,
				array(
					'download_key'    => $source_bytes ? $url : '',
					'estimate_status' => $source_bytes ? 'source_only' : 'unavailable',
					'estimate_reason' => 'local_unregistered_url',
					'source_bytes'    => $source_bytes,
					'basis'           => $source_bytes ? 'source_only' : 'unavailable',
				)
			)
		);
	}

	/**
	 * Build a row for an inline local attachment.
	 *
	 * @param InventoryOccurrence    $occurrence Occurrence.
	 * @param OccurrenceAssetMapping $mapping Mapping.
	 * @param array<string,mixed>    $base Base row.
	 * @return ByteOccurrenceReport
	 */
	private function inline_attachment_row( InventoryOccurrence $occurrence, OccurrenceAssetMapping $mapping, array $base ): ByteOccurrenceReport {
		if ( $mapping->source_candidate_count() > 1 ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'responsive_candidate_uncertain',
					)
				)
			);
		}

		$concrete_url = $mapping->concrete_source_url();
		$source_bytes = $this->source_bytes_from_mapping( $mapping );

		if ( null === $concrete_url || '' === $concrete_url || null === $source_bytes ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'attachment_unmapped',
					)
				)
			);
		}

		if ( ! is_array( $mapping->matched_candidate() ) ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'download_key'    => $concrete_url,
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'attachment_unmapped',
						'source_bytes'    => $source_bytes,
					)
				)
			);
		}

		$best = $this->best_ready_modern_candidate( $occurrence->attachment_id(), (string) $mapping->matched_size_name() );

		if ( ! is_array( $best ) || ! isset( $best['format'], $best['bytes'] ) ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'download_key'      => $concrete_url,
						'estimate_status'   => 'unavailable',
						'estimate_reason'   => 'no_ready_derivative',
						'matched_size_name' => $mapping->matched_size_name(),
						'source_bytes'      => $source_bytes,
					)
				)
			);
		}

		return $this->estimated_row( $base, $concrete_url, $mapping->matched_size_name(), $source_bytes, (string) $best['format'], (int) $best['bytes'] );
	}

	/**
	 * Build a row for a structured background attachment.
	 *
	 * @param InventoryOccurrence    $occurrence Occurrence.
	 * @param OccurrenceAssetMapping $mapping Mapping.
	 * @param array<string,mixed>    $base Base row.
	 * @return ByteOccurrenceReport
	 */
	private function background_attachment_row( InventoryOccurrence $occurrence, OccurrenceAssetMapping $mapping, array $base ): ByteOccurrenceReport {
		$concrete_url = $mapping->concrete_source_url();

		if ( null === $concrete_url || '' === $concrete_url || ! is_array( $mapping->matched_candidate() ) ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'background_candidate_unmapped',
					)
				)
			);
		}

		$source_bytes = $this->source_bytes_from_mapping( $mapping );

		if ( null === $source_bytes ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status' => 'unavailable',
						'estimate_reason' => 'background_candidate_unmapped',
					)
				)
			);
		}

		$best = $this->best_ready_modern_candidate( $occurrence->attachment_id(), (string) $mapping->matched_size_name() );

		if ( ! is_array( $best ) || ! isset( $best['format'], $best['bytes'] ) ) {
			return new ByteOccurrenceReport(
				array_merge(
					$base,
					array(
						'estimate_status'   => 'unavailable',
						'estimate_reason'   => 'no_ready_derivative',
						'matched_size_name' => $mapping->matched_size_name(),
					)
				)
			);
		}

		return $this->estimated_row( $base, $concrete_url, $mapping->matched_size_name(), $source_bytes, (string) $best['format'], (int) $best['bytes'] );
	}

	/**
	 * Build an estimated row.
	 *
	 * @param array<string,mixed> $base Base row.
	 * @param string              $download_key Download key.
	 * @param string|null         $matched_size_name Matched size name.
	 * @param int|null            $source_bytes Source bytes.
	 * @param string              $best_ready_format Best ready format.
	 * @param int                 $modern_bytes Modern bytes.
	 * @return ByteOccurrenceReport
	 */
	private function estimated_row(
		array $base,
		string $download_key,
		?string $matched_size_name,
		?int $source_bytes,
		string $best_ready_format,
		int $modern_bytes
	): ByteOccurrenceReport {
		$savings_bytes   = null;
		$savings_percent = null;

		if ( null !== $source_bytes ) {
			$savings_bytes   = max( 0, $source_bytes - $modern_bytes );
			$savings_percent = 0 < $source_bytes ? round( ( $savings_bytes / $source_bytes ) * 100, 2 ) : 0.0;
		}

		return new ByteOccurrenceReport(
			array_merge(
				$base,
				array(
					'download_key'      => $download_key,
					'estimate_status'   => 'estimated',
					'estimate_reason'   => 'best_ready_modern',
					'matched_size_name' => $matched_size_name,
					'source_bytes'      => $source_bytes,
					'best_ready_format' => $best_ready_format,
					'modern_bytes'      => $modern_bytes,
					'savings_bytes'     => $savings_bytes,
					'savings_percent'   => $savings_percent,
					'basis'             => 'theoretical_best_ready_modern',
				)
			)
		);
	}

	/**
	 * Get source bytes from a safe local mapping.
	 *
	 * @param OccurrenceAssetMapping $mapping Mapping.
	 * @return int|null
	 */
	private function source_bytes_from_mapping( OccurrenceAssetMapping $mapping ): ?int {
		$absolute_path = $mapping->absolute_path();

		if (
			null === $absolute_path
			|| ! $this->files->exists( $absolute_path )
			|| ! $this->files->is_file( $absolute_path )
			|| ! $this->files->is_readable( $absolute_path )
		) {
			return null;
		}

		$bytes = $this->files->file_size( $absolute_path );

		return is_int( $bytes ) && $bytes >= 0 ? $bytes : null;
	}

	/**
	 * Get the smallest ready enabled-format derivative for one size.
	 *
	 * @param int|null $attachment_id Attachment ID.
	 * @param string   $size_name Size name.
	 * @return array<string,mixed>|null
	 */
	private function best_ready_modern_candidate( ?int $attachment_id, string $size_name ): ?array {
		if ( null === $attachment_id || $attachment_id < 1 || '' === trim( $size_name ) ) {
			return null;
		}

		$sizes           = $this->repository->read( $attachment_id )->manifest()->sizes();
		$enabled_formats = $this->settings->enabled_formats();

		if ( ! isset( $sizes[ $size_name ]['formats'] ) || ! is_array( $sizes[ $size_name ]['formats'] ) || array() === $enabled_formats ) {
			return null;
		}

		$best = null;

		foreach ( AttachmentStatus::formats() as $format ) {
			if ( ! in_array( $format, $enabled_formats, true ) || ! isset( $sizes[ $size_name ]['formats'][ $format ] ) || ! is_array( $sizes[ $size_name ]['formats'][ $format ] ) ) {
				continue;
			}

			$bytes = isset( $sizes[ $size_name ]['formats'][ $format ]['bytes'] ) && is_numeric( $sizes[ $size_name ]['formats'][ $format ]['bytes'] )
				? max( 0, (int) $sizes[ $size_name ]['formats'][ $format ]['bytes'] )
				: null;

			if ( null === $bytes ) {
				continue;
			}

			if ( null === $best || $bytes < $best['bytes'] ) {
				$best = array(
					'format' => $format,
					'bytes'  => $bytes,
				);
			}
		}

		return $best;
	}

	/**
	 * Build empty per-format totals.
	 *
	 * @return array<string,array<string,int>>
	 */
	private function empty_format_totals(): array {
		$formats = array();

		foreach ( AttachmentStatus::formats() as $format ) {
			$formats[ $format ] = array(
				'sources_ready'   => 0,
				'source_bytes'    => 0,
				'generated_bytes' => 0,
				'savings_bytes'   => 0,
			);
		}

		return $formats;
	}
}
