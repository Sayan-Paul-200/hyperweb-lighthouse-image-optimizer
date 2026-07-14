<?php
/**
 * Occurrence asset mapper.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageSourceExtractor;
use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentSizeResolver;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalyzerInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\UploadsRuntimeInterface;

/**
 * Maps one reporting occurrence to conservative local-asset facts.
 */
final class OccurrenceAssetMapper {

	/**
	 * Attachment runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $attachments;

	/**
	 * Uploads runtime.
	 *
	 * @var UploadsRuntimeInterface
	 */
	private $uploads;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Source extractor.
	 *
	 * @var AttachmentImageSourceExtractor
	 */
	private $extractor;

	/**
	 * Attachment size resolver.
	 *
	 * @var AttachmentSizeResolver
	 */
	private $resolver;

	/**
	 * Path sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Create mapper.
	 *
	 * @param AttachmentImageRuntimeInterface $attachments Attachment runtime.
	 * @param UploadsRuntimeInterface         $uploads Uploads runtime.
	 * @param ImageMarkupAnalyzerInterface    $analyzer Markup analyzer.
	 * @param AttachmentImageSourceExtractor  $extractor Source extractor.
	 * @param AttachmentSizeResolver          $resolver Size resolver.
	 * @param DerivativeManifestSanitizer     $sanitizer Path sanitizer.
	 */
	public function __construct(
		AttachmentImageRuntimeInterface $attachments,
		UploadsRuntimeInterface $uploads,
		ImageMarkupAnalyzerInterface $analyzer,
		AttachmentImageSourceExtractor $extractor,
		AttachmentSizeResolver $resolver,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->attachments = $attachments;
		$this->uploads     = $uploads;
		$this->analyzer    = $analyzer;
		$this->extractor   = $extractor;
		$this->resolver    = $resolver;
		$this->sanitizer   = $sanitizer;
	}

	/**
	 * Map one inventory occurrence.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return OccurrenceAssetMapping
	 */
	public function map( InventoryOccurrence $occurrence ): OccurrenceAssetMapping {
		$raw_img_html      = $this->raw_img_html( $occurrence );
		$image_meta        = $this->image_meta( $occurrence->attachment_id() );
		$source_candidates = '' !== $raw_img_html ? $this->extractor->extract( $raw_img_html )->sources() : array();
		$concrete_url      = $this->concrete_source_url( $occurrence, $raw_img_html );
		$matched_candidate = $this->matched_candidate( $occurrence, $raw_img_html, $image_meta );
		$local_reference   = $this->local_file_reference( $occurrence, $image_meta, $matched_candidate );

		return new OccurrenceAssetMapping(
			$raw_img_html,
			$image_meta,
			$source_candidates,
			$matched_candidate,
			$local_reference,
			$concrete_url
		);
	}

	/**
	 * Read raw IMG HTML from context.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @return string
	 */
	private function raw_img_html( InventoryOccurrence $occurrence ): string {
		$context = $occurrence->context();

		return isset( $context['raw_img_html'] ) && is_string( $context['raw_img_html'] ) ? $context['raw_img_html'] : '';
	}

	/**
	 * Read image metadata when safe.
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
	 * Resolve a matched metadata candidate when confidence is sufficient.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @param string              $raw_img_html Raw IMG HTML.
	 * @param array<string,mixed> $image_meta Attachment metadata.
	 * @return array<string,mixed>|null
	 */
	private function matched_candidate( InventoryOccurrence $occurrence, string $raw_img_html, array $image_meta ): ?array {
		if ( array() === $image_meta ) {
			return null;
		}

		if ( '' !== $raw_img_html ) {
			$analysis    = $this->analyzer->analyze( $raw_img_html );
			$known_width = $analysis->has_valid_width() ? $analysis->width() : null;
			$candidate   = $this->resolver->resolve_from_analysis( $analysis, $image_meta, $known_width );

			if ( is_array( $candidate ) ) {
				return $candidate;
			}
		}

		if ( null !== $occurrence->url() ) {
			return $this->resolver->resolve_from_url( $occurrence->url(), $image_meta );
		}

		return null;
	}

	/**
	 * Build a local file reference when uploads mapping is safe.
	 *
	 * @param InventoryOccurrence      $occurrence Occurrence.
	 * @param array<string,mixed>      $image_meta Attachment metadata.
	 * @param array<string,mixed>|null $matched_candidate Matched candidate.
	 * @return array<string,string>|null
	 */
	private function local_file_reference( InventoryOccurrence $occurrence, array $image_meta, ?array $matched_candidate ): ?array {
		$base_dir = $this->uploads->uploads_base_dir();

		if ( ! is_string( $base_dir ) || '' === trim( $base_dir ) ) {
			return null;
		}

		$base_dir = rtrim( str_replace( '\\', '/', trim( $base_dir ) ), '/' );
		$relative = '';

		if ( is_array( $matched_candidate ) && isset( $matched_candidate['relative_path'] ) ) {
			$relative = $this->sanitizer->safe_relative_path( (string) $matched_candidate['relative_path'] );
		}

		if ( '' === $relative && null !== $occurrence->url() ) {
			$relative = $this->relative_path_from_uploads_url( $occurrence->url() );
		}

		if ( '' === $relative && PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT === $occurrence->origin() && array() !== $image_meta ) {
			$relative = $this->sanitizer->safe_relative_path( $image_meta['file'] ?? '' );
		}

		if ( '' === $relative ) {
			return null;
		}

		return array(
			'relative_path' => $relative,
			'absolute_path' => $base_dir . '/' . ltrim( $relative, '/' ),
		);
	}

	/**
	 * Determine the strongest concrete source URL for one occurrence.
	 *
	 * @param InventoryOccurrence $occurrence Occurrence.
	 * @param string              $raw_img_html Raw IMG HTML.
	 * @return string|null
	 */
	private function concrete_source_url( InventoryOccurrence $occurrence, string $raw_img_html ): ?string {
		if ( '' !== $raw_img_html ) {
			$analysis = $this->analyzer->analyze( $raw_img_html );

			if ( $analysis->is_renderable_img() && null !== $analysis->src() && '' !== trim( $analysis->src() ) ) {
				return trim( $analysis->src() );
			}
		}

		return null !== $occurrence->url() && '' !== trim( $occurrence->url() ) ? trim( $occurrence->url() ) : null;
	}

	/**
	 * Map one uploads URL to a relative path conservatively.
	 *
	 * @param string $url Uploads URL.
	 * @return string
	 */
	private function relative_path_from_uploads_url( string $url ): string {
		$base_url = $this->uploads->uploads_base_url( new DerivativeUrlRequest( 'inventory-placeholder' ) );

		if ( ! is_string( $base_url ) || '' === trim( $base_url ) || '' === trim( $url ) ) {
			return '';
		}

		if ( function_exists( 'wp_parse_url' ) ) {
			$base_host = wp_parse_url( $base_url, PHP_URL_HOST );
			$url_host  = wp_parse_url( $url, PHP_URL_HOST );
			$base_port = wp_parse_url( $base_url, PHP_URL_PORT );
			$url_port  = wp_parse_url( $url, PHP_URL_PORT );
			$base_path = wp_parse_url( $base_url, PHP_URL_PATH );
			$url_path  = wp_parse_url( $url, PHP_URL_PATH );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Conservative read-only URL parsing outside guaranteed WordPress contexts.
			$base_parts = parse_url( $base_url );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Conservative read-only URL parsing outside guaranteed WordPress contexts.
			$url_parts = parse_url( $url );

			if ( ! is_array( $base_parts ) || ! is_array( $url_parts ) ) {
				return '';
			}

			$base_host = $base_parts['host'] ?? null;
			$url_host  = $url_parts['host'] ?? null;
			$base_port = $base_parts['port'] ?? null;
			$url_port  = $url_parts['port'] ?? null;
			$base_path = $base_parts['path'] ?? null;
			$url_path  = $url_parts['path'] ?? null;
		}

		if (
			! is_string( $base_host )
			|| ! is_string( $url_host )
			|| strtolower( $base_host ) !== strtolower( $url_host )
		) {
			return '';
		}

		$base_port = is_numeric( $base_port ) ? (int) $base_port : 0;
		$url_port  = is_numeric( $url_port ) ? (int) $url_port : 0;

		if ( $base_port !== $url_port ) {
			return '';
		}

		$base_path = is_string( $base_path ) ? trim( rawurldecode( $base_path ), '/' ) : '';
		$url_path  = is_string( $url_path ) ? trim( rawurldecode( $url_path ), '/' ) : '';

		if ( '' === $base_path || '' === $url_path || 0 !== strpos( $url_path . '/', $base_path . '/' ) ) {
			return '';
		}

		return $this->sanitizer->safe_relative_path( ltrim( substr( $url_path, strlen( $base_path ) ), '/' ) );
	}
}
