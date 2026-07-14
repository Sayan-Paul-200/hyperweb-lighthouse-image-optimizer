<?php
/**
 * Content issue report service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalyzerInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\IntrinsicDimensionRepair;
use HyperWeb\LighthouseImageOptimizer\Delivery\UploadsRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\AnimationDetectorInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds conservative page-level image findings from one inventory snapshot.
 */
final class ContentIssueReportService {

	/**
	 * Enabled format settings.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Attachment metadata runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $attachments;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Animation detector.
	 *
	 * @var AnimationDetectorInterface
	 */
	private $animation;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Attachment size resolver.
	 *
	 * @var AttachmentSizeResolver
	 */
	private $resolver;

	/**
	 * Intrinsic-dimension repair service.
	 *
	 * @var IntrinsicDimensionRepair
	 */
	private $dimension_repair;

	/**
	 * Critical image selector.
	 *
	 * @var ContentCriticalImageSelector
	 */
	private $critical_selector;

	/**
	 * Shared occurrence asset mapper.
	 *
	 * @var OccurrenceAssetMapper
	 */
	private $mapper;

	/**
	 * Create service.
	 *
	 * @param SettingsRepositoryInterface     $settings Settings repository.
	 * @param AttachmentImageRuntimeInterface $attachments Attachment runtime.
	 * @param UploadsRuntimeInterface         $uploads Uploads runtime.
	 * @param ImageFileProbeInterface         $files File probe.
	 * @param AnimationDetectorInterface      $animation Animation detector.
	 * @param ImageMarkupAnalyzerInterface    $analyzer Markup analyzer.
	 * @param AttachmentImageSourceExtractor  $extractor Source extractor.
	 * @param AttachmentSizeResolver          $resolver Size resolver.
	 * @param IntrinsicDimensionRepair        $dimension_repair Intrinsic dimension repair.
	 * @param ContentCriticalImageSelector    $critical_selector Critical selector.
	 * @param DerivativeManifestSanitizer     $sanitizer Path sanitizer.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		AttachmentImageRuntimeInterface $attachments,
		UploadsRuntimeInterface $uploads,
		ImageFileProbeInterface $files,
		AnimationDetectorInterface $animation,
		ImageMarkupAnalyzerInterface $analyzer,
		AttachmentImageSourceExtractor $extractor,
		AttachmentSizeResolver $resolver,
		IntrinsicDimensionRepair $dimension_repair,
		ContentCriticalImageSelector $critical_selector,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->settings          = $settings;
		$this->attachments       = $attachments;
		$this->files             = $files;
		$this->animation         = $animation;
		$this->analyzer          = $analyzer;
		$this->resolver          = $resolver;
		$this->dimension_repair  = $dimension_repair;
		$this->critical_selector = $critical_selector;
		$this->mapper            = new OccurrenceAssetMapper(
			$attachments,
			$uploads,
			$analyzer,
			$extractor,
			$resolver,
			$sanitizer
		);
	}

	/**
	 * Build the issue report for one inventory snapshot.
	 *
	 * @param ContentInventorySnapshot $snapshot Inventory snapshot.
	 * @return ImageIssueReport
	 */
	public function report( ContentInventorySnapshot $snapshot ): ImageIssueReport {
		$findings       = array();
		$critical_ids   = $this->critical_selector->select( $snapshot );
		$attachment_map = array();
		$duplicates     = array();

		foreach ( $snapshot->occurrences() as $occurrence ) {
			if ( null !== $occurrence->attachment_id() ) {
				$attachment_map[ $occurrence->attachment_id() ][] = $occurrence;
			}

			foreach ( $this->occurrence_findings( $occurrence, $critical_ids ) as $finding ) {
				$findings[ $this->finding_key( $finding ) ] = $finding;
			}
		}

		foreach ( $attachment_map as $attachment_id => $occurrences ) {
			$finding = $this->missing_modern_derivative_finding( (int) $attachment_id, $occurrences );

			if ( $finding instanceof ImageIssueFinding ) {
				$findings[ $this->finding_key( $finding ) ] = $finding;
			}

			$duplicate = $this->duplicate_source_downloads_finding( (int) $attachment_id, $occurrences );

			if ( $duplicate instanceof ImageIssueFinding ) {
				$duplicates[ $this->finding_key( $duplicate ) ] = $duplicate;
			}
		}

		foreach ( $duplicates as $key => $finding ) {
			$findings[ $key ] = $finding;
		}

		uasort(
			$findings,
			static function ( ImageIssueFinding $left, ImageIssueFinding $right ): int {
				$severity_order = array(
					ImageIssueFinding::SEVERITY_HIGH   => 0,
					ImageIssueFinding::SEVERITY_MEDIUM => 1,
					ImageIssueFinding::SEVERITY_LOW    => 2,
				);

				$left_rank  = $severity_order[ $left->severity() ] ?? 99;
				$right_rank = $severity_order[ $right->severity() ] ?? 99;

				if ( $left_rank !== $right_rank ) {
					return $left_rank <=> $right_rank;
				}

				return strcmp( $left->code(), $right->code() );
			}
		);

		return new ImageIssueReport( array_values( $findings ) );
	}

	/**
	 * Build occurrence-scoped findings.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @param int[]               $critical_ids Critical attachment IDs.
	 * @return ImageIssueFinding[]
	 */
	private function occurrence_findings( InventoryOccurrence $occurrence, array $critical_ids ): array {
		$findings = array();

		foreach (
			array(
				$this->oversized_source_selection_finding( $occurrence ),
				$this->missing_responsive_candidates_finding( $occurrence ),
				$this->missing_intrinsic_dimensions_finding( $occurrence ),
				$this->critical_image_lazy_loaded_finding( $occurrence, $critical_ids ),
				$this->below_the_fold_eager_loading_finding( $occurrence, $critical_ids ),
				$this->external_image_finding( $occurrence ),
				$this->animated_gif_finding( $occurrence ),
				$this->broken_image_url_finding( $occurrence ),
				$this->css_background_image_finding( $occurrence ),
			) as $finding
		) {
			if ( $finding instanceof ImageIssueFinding ) {
				$findings[] = $finding;
			}
		}

		return $findings;
	}

	/**
	 * Build a stable finding map key.
	 *
	 * @param ImageIssueFinding $finding Finding.
	 * @return string
	 */
	private function finding_key( ImageIssueFinding $finding ): string {
		$data = $finding->to_array();
		$json = function_exists( 'wp_json_encode' )
			? wp_json_encode( array( $data['occurrence_ids'], $data['attachment_ids'], $data['evidence'] ) )
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Safe fallback outside WordPress bootstrap.
			: json_encode( array( $data['occurrence_ids'], $data['attachment_ids'], $data['evidence'] ) );

		return $finding->code() . ':' . md5( is_string( $json ) ? $json : '' );
	}

	/**
	 * Report missing ready derivatives for one attachment.
	 *
	 * @param int                   $attachment_id Attachment ID.
	 * @param InventoryOccurrence[] $occurrences Attachment occurrences.
	 * @return ImageIssueFinding|null
	 */
	private function missing_modern_derivative_finding( int $attachment_id, array $occurrences ): ?ImageIssueFinding {
		$attachment = $this->attachment_summary_from_occurrences( $occurrences );

		if ( ! is_array( $attachment ) || ! empty( $attachment['excluded'] ) ) {
			return null;
		}

		$enabled_formats = $this->settings->enabled_formats();
		$ready_formats   = isset( $attachment['ready_formats'] ) && is_array( $attachment['ready_formats'] ) ? array_values( $attachment['ready_formats'] ) : array();
		$missing_formats = array_values( array_diff( $enabled_formats, $ready_formats ) );

		if ( array() === $enabled_formats || array() === $missing_formats ) {
			return null;
		}

		$state    = isset( $attachment['state'] ) && is_string( $attachment['state'] ) ? $attachment['state'] : AttachmentStatus::STATE_UNPROCESSED;
		$severity = AttachmentStatus::STATE_PARTIAL === $state ? ImageIssueFinding::SEVERITY_MEDIUM : ImageIssueFinding::SEVERITY_HIGH;

		return new ImageIssueFinding(
			'missing_modern_derivative',
			$severity,
			'Missing modern derivative',
			'One or more enabled modern-image formats are not ready for this attachment yet.',
			'Optimize or retry this attachment so all enabled formats can be generated before expecting modern delivery.',
			$this->occurrence_ids( $occurrences ),
			array( $attachment_id ),
			array(
				'attachment_state' => $state,
				'enabled_formats'  => $enabled_formats,
				'ready_formats'    => $ready_formats,
				'missing_formats'  => $missing_formats,
			)
		);
	}

	/**
	 * Report oversized full-size selection for one inline attachment occurrence.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function oversized_source_selection_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		if ( ! $this->is_inline_attachment_with_markup( $occurrence ) ) {
			return null;
		}

		$attachment_id = $occurrence->attachment_id();
		$mapping       = $this->mapper->map( $occurrence );
		$html          = $mapping->raw_img_html();
		$analysis      = $this->analyzer->analyze( $html );
		$known_width   = $analysis->has_valid_width() ? $analysis->width() : null;
		$candidate     = $mapping->matched_candidate();

		if ( ! is_array( $candidate ) || 'full' !== (string) $candidate['size_name'] || (int) ( $candidate['width'] ?? 0 ) < 1 ) {
			return null;
		}

		$slot_width = $known_width;

		if ( ! is_int( $slot_width ) || $slot_width < 1 ) {
			return null;
		}

		$selected_width = isset( $candidate['width'] ) ? (int) $candidate['width'] : 0;

		if ( $selected_width < 1 ) {
			return null;
		}

		$ratio = round( $selected_width / $slot_width, 2 );

		if ( $ratio < 1.5 ) {
			return null;
		}

		return new ImageIssueFinding(
			'oversized_source_selection',
			ImageIssueFinding::SEVERITY_MEDIUM,
			'Oversized source selection',
			'This inline attachment appears to render a full-size source into a much smaller slot.',
			'Use a more appropriate registered image size where the smaller rendered slot is intentional.',
			array( $occurrence->id() ),
			array( $attachment_id ),
			array(
				'selected_size_name'    => (string) $candidate['size_name'],
				'selected_source_width' => $selected_width,
				'slot_width'            => $slot_width,
				'width_ratio'           => $ratio,
			)
		);
	}

	/**
	 * Report missing responsive candidates for one inline attachment occurrence.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function missing_responsive_candidates_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		if ( ! $this->is_inline_attachment_with_markup( $occurrence ) ) {
			return null;
		}

		$mapping           = $this->mapper->map( $occurrence );
		$metadata_count    = count( $this->resolver->metadata_candidates( $mapping->image_meta() ) );
		$extracted_sources = $mapping->source_candidates();

		if ( $metadata_count < 2 || count( $extracted_sources ) > 1 ) {
			return null;
		}

		return new ImageIssueFinding(
			'missing_responsive_candidates',
			ImageIssueFinding::SEVERITY_MEDIUM,
			'Missing responsive candidates',
			'This inline attachment has multiple registered image candidates in metadata, but the rendered markup does not expose a usable responsive source set.',
			'Render this attachment with a responsive image tag or block configuration that preserves WordPress candidate selection.',
			array( $occurrence->id() ),
			array( $occurrence->attachment_id() ),
			array(
				'metadata_candidate_count' => $metadata_count,
				'rendered_candidate_count' => count( $extracted_sources ),
			)
		);
	}

	/**
	 * Report missing intrinsic dimensions when repair certainty exists.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function missing_intrinsic_dimensions_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		if ( ! $this->is_inline_attachment_with_markup( $occurrence ) ) {
			return null;
		}

		$html     = $this->raw_img_html( $occurrence );
		$analysis = $this->analyzer->analyze( $html );

		if ( ( $analysis->has_valid_width() && $analysis->has_valid_height() ) || ( ! $analysis->has_width_attribute() && ! $analysis->has_height_attribute() && null === $analysis->src() ) ) {
			return null;
		}

		$result = $this->dimension_repair->repair(
			$occurrence->attachment_id(),
			$html,
			$this->image_meta( $occurrence->attachment_id() ),
			$analysis->has_valid_width() ? $analysis->width() : null
		);

		if ( ! $result->is_repaired() ) {
			return null;
		}

		return new ImageIssueFinding(
			'missing_intrinsic_dimensions',
			ImageIssueFinding::SEVERITY_HIGH,
			'Missing intrinsic dimensions',
			'This inline attachment is missing intrinsic width or height attributes in markup even though reliable attachment dimensions are available.',
			'Preserve width and height on the rendered image markup so layout can reserve space more reliably.',
			array( $occurrence->id() ),
			array( $occurrence->attachment_id() ),
			array(
				'missing_width'  => ! $analysis->has_width_attribute(),
				'missing_height' => ! $analysis->has_height_attribute(),
			)
		);
	}

	/**
	 * Report lazy loading on a content-local critical inline image.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @param int[]               $critical_ids Critical attachment IDs.
	 * @return ImageIssueFinding|null
	 */
	private function critical_image_lazy_loaded_finding( InventoryOccurrence $occurrence, array $critical_ids ): ?ImageIssueFinding {
		if (
			! $this->is_inline_attachment_with_markup( $occurrence )
			|| ! in_array( (int) $occurrence->attachment_id(), $critical_ids, true )
		) {
			return null;
		}

		$analysis = $this->analyzer->analyze( $this->raw_img_html( $occurrence ) );

		if ( 'lazy' !== $analysis->loading() ) {
			return null;
		}

		return new ImageIssueFinding(
			'critical_image_lazy_loaded',
			ImageIssueFinding::SEVERITY_HIGH,
			'Critical image lazy-loaded',
			'An attachment selected as critical for this content record still carries lazy loading in stored markup.',
			'Remove lazy loading from the critical image so it can be discovered and requested earlier.',
			array( $occurrence->id() ),
			array( $occurrence->attachment_id() ),
			array(
				'loading' => $analysis->loading(),
			)
		);
	}

	/**
	 * Report conservative eager/high loading on likely non-critical later inline images.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @param int[]               $critical_ids Critical attachment IDs.
	 * @return ImageIssueFinding|null
	 */
	private function below_the_fold_eager_loading_finding( InventoryOccurrence $occurrence, array $critical_ids ): ?ImageIssueFinding {
		if (
			! $this->is_inline_attachment_with_markup( $occurrence )
			|| in_array( (int) $occurrence->attachment_id(), $critical_ids, true )
		) {
			return null;
		}

		$position = isset( $occurrence->evidence()['occurrence'] ) ? (int) $occurrence->evidence()['occurrence'] : 0;

		if ( $position < 2 ) {
			return null;
		}

		$analysis = $this->analyzer->analyze( $this->raw_img_html( $occurrence ) );
		$signals  = array();

		if ( 'eager' === $analysis->loading() ) {
			$signals[] = 'loading=eager';
		}

		if ( 'high' === $analysis->fetchpriority() ) {
			$signals[] = 'fetchpriority=high';
		}

		if ( array() === $signals ) {
			return null;
		}

		return new ImageIssueFinding(
			'below_the_fold_eager_loading',
			ImageIssueFinding::SEVERITY_LOW,
			'Possible below-the-fold eager loading',
			'This non-critical inline image carries eager or high-priority loading signals even though its later content order suggests it may not need them. This is a conservative heuristic, not measured viewport proof.',
			'Review whether this later image really needs eager or high-priority loading, and prefer keeping those signals reserved for clearly critical content.',
			array( $occurrence->id() ),
			array( $occurrence->attachment_id() ),
			array(
				'occurrence_order' => $position,
				'signals'          => $signals,
			)
		);
	}

	/**
	 * Report external images.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function external_image_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		if ( PageInventoryItem::ORIGIN_EXTERNAL !== $occurrence->origin() ) {
			return null;
		}

		return new ImageIssueFinding(
			'external_image',
			ImageIssueFinding::SEVERITY_LOW,
			'External image reference',
			'This occurrence points at an external image URL, so plugin-owned optimization and derivative delivery do not apply automatically here.',
			'Review whether this external image should stay remote or be managed locally if you want plugin-owned responsive modern-format handling.',
			array( $occurrence->id() ),
			array(),
			array(
				'url'          => $occurrence->url(),
				'presentation' => $occurrence->presentation(),
			)
		);
	}

	/**
	 * Report animated GIFs for safely mappable local files only.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function animated_gif_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		$local = $this->local_file_reference( $occurrence );

		if ( ! is_array( $local ) || empty( $local['absolute_path'] ) || empty( $local['relative_path'] ) ) {
			return null;
		}

		$mime_type = $this->files->mime_type( $local['absolute_path'] );

		if ( 'image/gif' !== $mime_type ) {
			return null;
		}

		$status = $this->animation->detect( $local['absolute_path'], $mime_type );

		if ( ! $status->is_animated() ) {
			return null;
		}

		return new ImageIssueFinding(
			'animated_gif',
			ImageIssueFinding::SEVERITY_MEDIUM,
			'Animated GIF source',
			'This occurrence references a local animated GIF, which is typically expensive to transfer and render compared with modern video or image alternatives.',
			'Consider replacing animated GIF content with a more efficient media strategy when appropriate.',
			array( $occurrence->id() ),
			null !== $occurrence->attachment_id() ? array( $occurrence->attachment_id() ) : array(),
			array(
				'mime_type'      => $mime_type,
				'animation_code' => $status->code(),
				'relative_path'  => $local['relative_path'],
			)
		);
	}

	/**
	 * Report broken local image references that can be mapped safely.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function broken_image_url_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		$local = $this->local_file_reference( $occurrence );

		if ( ! is_array( $local ) || empty( $local['absolute_path'] ) || empty( $local['relative_path'] ) ) {
			return null;
		}

		if ( $this->files->exists( $local['absolute_path'] ) && $this->files->is_file( $local['absolute_path'] ) && $this->files->is_readable( $local['absolute_path'] ) ) {
			return null;
		}

		return new ImageIssueFinding(
			'broken_image_url',
			ImageIssueFinding::SEVERITY_HIGH,
			'Broken local image reference',
			'This occurrence points at a local image path that could be mapped safely, but the referenced file is missing or unreadable.',
			'Restore the missing local file or update the stored content so it no longer references this unavailable image path.',
			array( $occurrence->id() ),
			null !== $occurrence->attachment_id() ? array( $occurrence->attachment_id() ) : array(),
			array(
				'relative_path' => $local['relative_path'],
				'readable'      => $this->files->is_readable( $local['absolute_path'] ),
			)
		);
	}

	/**
	 * Report supported structured Elementor background image usage as advisory.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return ImageIssueFinding|null
	 */
	private function css_background_image_finding( InventoryOccurrence $occurrence ): ?ImageIssueFinding {
		if ( 'elementor_background' !== $occurrence->source() ) {
			return null;
		}

		return new ImageIssueFinding(
			'css_background_image',
			ImageIssueFinding::SEVERITY_LOW,
			'CSS background image',
			'This image is used through a structured Elementor background configuration, so it is not part of ordinary inline image markup analysis.',
			'Review background-image usage carefully because delivery, loading, and responsive behavior differ from inline attachment markup.',
			array( $occurrence->id() ),
			null !== $occurrence->attachment_id() ? array( $occurrence->attachment_id() ) : array(),
			array(
				'device'        => $occurrence->evidence()['device'] ?? null,
				'setting_group' => $occurrence->evidence()['setting_group'] ?? null,
				'element_id'    => $occurrence->evidence()['element_id'] ?? null,
			)
		);
	}

	/**
	 * Report likely duplicate source downloads only with strong same-attachment evidence.
	 *
	 * @param int                   $attachment_id Attachment ID.
	 * @param InventoryOccurrence[] $occurrences Attachment occurrences.
	 * @return ImageIssueFinding|null
	 */
	private function duplicate_source_downloads_finding( int $attachment_id, array $occurrences ): ?ImageIssueFinding {
		$urls = array();

		foreach ( $occurrences as $occurrence ) {
			$url = $this->concrete_source_url( $occurrence );

			if ( null !== $url ) {
				$urls[ $url ] = true;
			}
		}

		if ( count( $urls ) < 2 ) {
			return null;
		}

		return new ImageIssueFinding(
			'duplicate_source_downloads',
			ImageIssueFinding::SEVERITY_LOW,
			'Possible duplicate source downloads',
			'The same attachment appears multiple times with different concrete source URLs, which can increase the chance of multiple file downloads for one logical image.',
			'Prefer consistent attachment size selection when the same image repeats across the same content record.',
			$this->occurrence_ids( $occurrences ),
			array( $attachment_id ),
			array(
				'urls'      => array_keys( $urls ),
				'url_count' => count( $urls ),
			)
		);
	}

	/**
	 * Determine whether the occurrence is an inline local attachment with raw IMG markup.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return bool
	 */
	private function is_inline_attachment_with_markup( InventoryOccurrence $occurrence ): bool {
		return PageInventoryItem::PRESENTATION_INLINE === $occurrence->presentation()
			&& PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT === $occurrence->origin()
			&& null !== $occurrence->attachment_id()
			&& '' !== $this->raw_img_html( $occurrence );
	}

	/**
	 * Get raw IMG HTML from internal context.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return string
	 */
	private function raw_img_html( InventoryOccurrence $occurrence ): string {
		return $this->mapper->map( $occurrence )->raw_img_html();
	}

	/**
	 * Read image metadata for one attachment.
	 *
	 * @param int|null $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	private function image_meta( ?int $attachment_id ): array {
		if ( null === $attachment_id || $attachment_id < 1 || ! $this->attachments->attachment_is_image( $attachment_id ) ) {
			return array();
		}

		return $this->attachments->attachment_metadata( $attachment_id );
	}

	/**
	 * Build local file reference details when mapping is safe.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return array<string,string>|null
	 */
	private function local_file_reference( InventoryOccurrence $occurrence ): ?array {
		return $this->mapper->map( $occurrence )->local_file_reference();
	}

	/**
	 * Get the first available attachment summary from grouped occurrences.
	 *
	 * @param InventoryOccurrence[] $occurrences Occurrences.
	 * @return array<string,mixed>|null
	 */
	private function attachment_summary_from_occurrences( array $occurrences ): ?array {
		foreach ( $occurrences as $occurrence ) {
			$attachment = $occurrence->attachment();

			if ( is_array( $attachment ) ) {
				return $attachment;
			}
		}

		return null;
	}

	/**
	 * Collect occurrence IDs from grouped occurrences.
	 *
	 * @param InventoryOccurrence[] $occurrences Occurrences.
	 * @return string[]
	 */
	private function occurrence_ids( array $occurrences ): array {
		return array_values(
			array_unique(
				array_map(
					static function ( InventoryOccurrence $occurrence ): string {
						return $occurrence->id();
					},
					$occurrences
				)
			)
		);
	}

	/**
	 * Determine the strongest concrete source URL for duplicate detection.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return string|null
	 */
	private function concrete_source_url( InventoryOccurrence $occurrence ): ?string {
		return $this->mapper->map( $occurrence )->concrete_source_url();
	}
}
