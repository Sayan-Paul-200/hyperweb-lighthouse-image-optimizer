<?php
/**
 * Responsive source-set builder.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\WordPressImageFileProbe;

/**
 * Maps normalized WordPress image candidates to ready modern-derivative source sets.
 */
final class SourceSetBuilder {

	/**
	 * Size resolver.
	 *
	 * @var AttachmentSizeResolver
	 */
	private $size_resolver;

	/**
	 * Repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * URL resolver.
	 *
	 * @var DerivativeUrlResolver
	 */
	private $resolver;

	/**
	 * Uploads runtime.
	 *
	 * @var UploadsRuntimeInterface
	 */
	private $uploads;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Manifest sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Build a WordPress-backed builder.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$uploads = new WordPressUploadsRuntime();

		return new self(
			DerivativeRepository::for_wordpress(),
			new DerivativeUrlResolver( $uploads, new DerivativeManifestSanitizer() ),
			$uploads,
			new WordPressImageFileProbe(),
			new DerivativeManifestSanitizer(),
			new AttachmentSizeResolver( new DerivativeManifestSanitizer() )
		);
	}

	/**
	 * Create builder.
	 *
	 * @param DerivativeRepository        $repository Repository.
	 * @param DerivativeUrlResolver       $resolver URL resolver.
	 * @param UploadsRuntimeInterface     $uploads Uploads runtime.
	 * @param ImageFileProbeInterface     $files File probe.
	 * @param DerivativeManifestSanitizer $sanitizer Manifest sanitizer.
	 * @param AttachmentSizeResolver      $size_resolver Size resolver.
	 */
	public function __construct(
		DerivativeRepository $repository,
		DerivativeUrlResolver $resolver,
		UploadsRuntimeInterface $uploads,
		ImageFileProbeInterface $files,
		DerivativeManifestSanitizer $sanitizer,
		AttachmentSizeResolver $size_resolver
	) {
		$this->repository    = $repository;
		$this->resolver      = $resolver;
		$this->uploads       = $uploads;
		$this->files         = $files;
		$this->sanitizer     = $sanitizer;
		$this->size_resolver = $size_resolver;
	}

	/**
	 * Build responsive source sets from WordPress original candidates.
	 *
	 * @param SourceSetBuildRequest $request Build request.
	 * @return SourceSetBuildResult
	 */
	public function build( SourceSetBuildRequest $request ): SourceSetBuildResult {
		$codes              = array();
		$had_omissions      = false;
		$formats            = array();
		$uploads_base_dir   = $this->uploads_base_dir();
		$normalized_sources = $this->normalize_original_sources( $request->original_sources(), $had_omissions );
		$repository_result  = $this->repository->read( $request->attachment_id() );
		$manifest           = $repository_result->manifest();
		$image_meta         = $request->image_meta();

		if ( ! $manifest->has_derivatives() ) {
			$codes[] = SourceSetBuildResult::CODE_MANIFEST_EMPTY;
		}

		if ( array() === $this->size_resolver->metadata_candidates( $image_meta ) ) {
			$codes[] = SourceSetBuildResult::CODE_INVALID_IMAGE_META;

			if ( $had_omissions ) {
				$codes[] = SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED;
			}

			return new SourceSetBuildResult( $request->attachment_id(), array(), $codes );
		}

		if ( array() === $normalized_sources ) {
			$codes[] = SourceSetBuildResult::CODE_NO_CANDIDATES;

			if ( $had_omissions ) {
				$codes[] = SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED;
			}

			return new SourceSetBuildResult( $request->attachment_id(), array(), $codes );
		}

		foreach ( $normalized_sources as $candidate ) {
			$metadata_candidate = $this->size_resolver->resolve_source_candidate( $candidate, $image_meta );

			if ( ! is_array( $metadata_candidate ) ) {
				$had_omissions = true;
				continue;
			}

			$manifest_formats = $this->manifest_formats_for_candidate( $manifest, $metadata_candidate );

			if ( array() === $manifest_formats ) {
				$had_omissions = true;
				continue;
			}

			foreach ( $manifest_formats as $format => $format_entry ) {
				$relative_path = $this->sanitizer->safe_relative_path( $format_entry['file'] ?? '' );

				if ( '' === $relative_path || '' === $uploads_base_dir ) {
					$had_omissions = true;
					continue;
				}

				$absolute_path = $this->absolute_uploads_path( $uploads_base_dir, $relative_path );

				if ( ! $this->files->exists( $absolute_path ) || ! $this->files->is_file( $absolute_path ) ) {
					$had_omissions = true;
					continue;
				}

				$resolution = $this->resolver->resolve(
					new DerivativeUrlRequest(
						$relative_path,
						$request->attachment_id(),
						(string) $metadata_candidate['size_name'],
						$format
					)
				);

				if ( ! $resolution->is_successful() || null === $resolution->url() ) {
					$had_omissions = true;
					continue;
				}

				$width = (int) $candidate['value'];

				if ( isset( $formats[ $format ][ $width ] ) ) {
					$had_omissions = true;
					continue;
				}

				$formats[ $format ][ $width ] = array(
					'url'        => $resolution->url(),
					'descriptor' => 'w',
					'value'      => $width,
				);
			}
		}

		$format_results = array();

		foreach ( $formats as $format => $sources ) {
			$source_set = new FormatSourceSet( $format, $this->sanitizer->expected_mime( $format ), $sources );

			if ( $source_set->has_sources() ) {
				$format_results[ $format ] = $source_set;
			}
		}

		if ( array() !== $format_results ) {
			$codes[] = SourceSetBuildResult::CODE_BUILT;
		}

		if ( $had_omissions ) {
			$codes[] = SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED;
		}

		return new SourceSetBuildResult( $request->attachment_id(), $format_results, $codes );
	}

	/**
	 * Normalize original WordPress source candidates.
	 *
	 * @param array<int|string,mixed> $sources Sources.
	 * @param bool                    $had_omissions Omission flag.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_original_sources( array $sources, bool &$had_omissions ): array {
		$normalized = array();
		$seen       = array();

		foreach ( $sources as $source ) {
			if ( ! is_array( $source ) ) {
				$had_omissions = true;
				continue;
			}

			$url        = isset( $source['url'] ) && is_scalar( $source['url'] ) ? trim( (string) $source['url'] ) : '';
			$descriptor = isset( $source['descriptor'] ) && is_scalar( $source['descriptor'] ) ? trim( (string) $source['descriptor'] ) : '';
			$value      = isset( $source['value'] ) && is_numeric( $source['value'] ) ? (int) $source['value'] : 0;

			if ( '' === $url || 'w' !== $descriptor || $value < 1 || isset( $seen[ $value ] ) ) {
				$had_omissions = true;
				continue;
			}

			$seen[ $value ] = true;
			$normalized[]   = array(
				'url'        => $url,
				'descriptor' => 'w',
				'value'      => $value,
			);
		}

		return $normalized;
	}

	/**
	 * Get manifest formats for a matched metadata candidate.
	 *
	 * @param DerivativeManifest  $manifest Manifest.
	 * @param array<string,mixed> $metadata_candidate Metadata candidate.
	 * @return array<string,array<string,mixed>>
	 */
	private function manifest_formats_for_candidate( DerivativeManifest $manifest, array $metadata_candidate ): array {
		$sizes     = $manifest->sizes();
		$size_name = (string) $metadata_candidate['size_name'];

		if ( ! isset( $sizes[ $size_name ] ) ) {
			return array();
		}

		$size   = $sizes[ $size_name ];
		$source = isset( $size['source'] ) && is_array( $size['source'] ) ? $size['source'] : null;

		if (
			! is_array( $source )
			|| ! isset( $source['file'], $source['width'] )
			|| (string) $source['file'] !== (string) $metadata_candidate['relative_path']
			|| (int) $source['width'] !== (int) $metadata_candidate['width']
		) {
			return array();
		}

		return isset( $size['formats'] ) && is_array( $size['formats'] ) ? $size['formats'] : array();
	}

	/**
	 * Normalize uploads base directory.
	 *
	 * @return string
	 */
	private function uploads_base_dir(): string {
		$base_dir = $this->uploads->uploads_base_dir();

		return null === $base_dir ? '' : rtrim( str_replace( '\\', '/', trim( $base_dir ) ), '/' );
	}

	/**
	 * Build an absolute uploads path from a safe relative path.
	 *
	 * @param string $base_dir Base directory.
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function absolute_uploads_path( string $base_dir, string $relative_path ): string {
		return rtrim( str_replace( '\\', '/', trim( $base_dir ) ), '/' ) . '/' . ltrim( str_replace( '\\', '/', trim( $relative_path ) ), '/' );
	}
}
