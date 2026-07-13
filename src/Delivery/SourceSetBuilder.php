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
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Create builder.
	 *
	 * @param DerivativeRepository       $repository Repository.
	 * @param DerivativeUrlResolver      $resolver URL resolver.
	 * @param UploadsRuntimeInterface    $uploads Uploads runtime.
	 * @param ImageFileProbeInterface    $files File probe.
	 * @param DerivativeManifestSanitizer $sanitizer Manifest sanitizer.
	 */
	public function __construct(
		DerivativeRepository $repository,
		DerivativeUrlResolver $resolver,
		UploadsRuntimeInterface $uploads,
		ImageFileProbeInterface $files,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->repository = $repository;
		$this->resolver   = $resolver;
		$this->uploads    = $uploads;
		$this->files      = $files;
		$this->sanitizer  = $sanitizer;
	}

	/**
	 * Build responsive source sets from WordPress original candidates.
	 *
	 * @param SourceSetBuildRequest $request Build request.
	 * @return SourceSetBuildResult
	 */
	public function build( SourceSetBuildRequest $request ): SourceSetBuildResult {
		$codes               = array();
		$had_omissions       = false;
		$formats             = array();
		$uploads_base_dir    = $this->uploads_base_dir();
		$normalized_sources  = $this->normalize_original_sources( $request->original_sources(), $had_omissions );
		$repository_result   = $this->repository->read( $request->attachment_id() );
		$manifest            = $repository_result->manifest();
		$metadata_candidates = $this->metadata_candidates( $request->image_meta(), $had_omissions );

		if ( ! $manifest->has_derivatives() ) {
			$codes[] = SourceSetBuildResult::CODE_MANIFEST_EMPTY;
		}

		if ( null === $metadata_candidates ) {
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
			$metadata_candidate = $this->match_metadata_candidate( $candidate, $metadata_candidates );

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

			$url = isset( $source['url'] ) && is_scalar( $source['url'] ) ? trim( (string) $source['url'] ) : '';
			$descriptor = isset( $source['descriptor'] ) && is_scalar( $source['descriptor'] ) ? trim( (string) $source['descriptor'] ) : '';
			$value = isset( $source['value'] ) && is_numeric( $source['value'] ) ? (int) $source['value'] : 0;

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
	 * Build metadata candidates from image metadata.
	 *
	 * @param array<string,mixed> $image_meta Image metadata.
	 * @param bool                $had_omissions Omission flag.
	 * @return array<int,array<string,mixed>>|null
	 */
	private function metadata_candidates( array $image_meta, bool &$had_omissions ): ?array {
		$file = $this->sanitizer->safe_relative_path( $image_meta['file'] ?? '' );

		if ( '' === $file ) {
			return null;
		}

		$directory  = $this->relative_directory( $file );
		$candidates = array();
		$full       = $this->metadata_candidate(
			'full',
			$file,
			$image_meta['width'] ?? null,
			$image_meta['height'] ?? null
		);

		if ( is_array( $full ) ) {
			$candidates[] = $full;
		} else {
			$had_omissions = true;
		}

		$sizes = $image_meta['sizes'] ?? array();

		if ( isset( $image_meta['sizes'] ) && ! is_array( $sizes ) ) {
			$had_omissions = true;
			$sizes         = array();
		}

		foreach ( $sizes as $size_name => $size ) {
			if ( ! is_string( $size_name ) || ! is_array( $size ) ) {
				$had_omissions = true;
				continue;
			}

			$relative_path = $this->metadata_relative_path(
				isset( $size['file'] ) && is_scalar( $size['file'] ) ? (string) $size['file'] : '',
				$directory
			);
			$candidate     = $this->metadata_candidate(
				$size_name,
				$relative_path,
				$size['width'] ?? null,
				$size['height'] ?? null
			);

			if ( is_array( $candidate ) ) {
				$candidates[] = $candidate;
			} else {
				$had_omissions = true;
			}
		}

		return array() !== $candidates ? $candidates : null;
	}

	/**
	 * Build one metadata candidate.
	 *
	 * @param string     $size_name Size name.
	 * @param string     $relative_path Relative path.
	 * @param mixed      $width Width.
	 * @param mixed      $height Height.
	 * @return array<string,mixed>|null
	 */
	private function metadata_candidate( string $size_name, string $relative_path, $width, $height ): ?array {
		$relative_path = $this->sanitizer->safe_relative_path( $relative_path );
		$width         = is_numeric( $width ) ? (int) $width : 0;
		$height        = is_numeric( $height ) ? (int) $height : 0;

		if ( '' === $relative_path || '' === trim( $size_name ) || $width < 1 || $height < 1 ) {
			return null;
		}

		return array(
			'size_name'     => substr( trim( $size_name ), 0, 64 ),
			'relative_path' => $relative_path,
			'width'         => $width,
			'height'        => $height,
		);
	}

	/**
	 * Match one original candidate to one metadata candidate.
	 *
	 * @param array<string,mixed>       $candidate Original candidate.
	 * @param array<int,array<string,mixed>> $metadata_candidates Metadata candidates.
	 * @return array<string,mixed>|null
	 */
	private function match_metadata_candidate( array $candidate, array $metadata_candidates ): ?array {
		$matches = array();

		foreach ( $metadata_candidates as $metadata_candidate ) {
			if (
				(int) $candidate['value'] === (int) $metadata_candidate['width']
				&& $this->url_matches_relative_path( (string) $candidate['url'], (string) $metadata_candidate['relative_path'] )
			) {
				$matches[] = $metadata_candidate;
			}
		}

		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/**
	 * Get manifest formats for a matched metadata candidate.
	 *
	 * @param DerivativeManifest   $manifest Manifest.
	 * @param array<string,mixed>  $metadata_candidate Metadata candidate.
	 * @return array<string,array<string,mixed>>
	 */
	private function manifest_formats_for_candidate( DerivativeManifest $manifest, array $metadata_candidate ): array {
		$sizes     = $manifest->sizes();
		$size_name = (string) $metadata_candidate['size_name'];

		if ( ! isset( $sizes[ $size_name ] ) || ! is_array( $sizes[ $size_name ] ) ) {
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

	/**
	 * Extract a relative directory.
	 *
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function relative_directory( string $relative_path ): string {
		$directory = dirname( str_replace( '\\', '/', trim( $relative_path ) ) );

		return '.' === $directory ? '' : trim( $directory, '/' );
	}

	/**
	 * Resolve one metadata file path relative to the main image directory.
	 *
	 * @param string $file File path.
	 * @param string $directory Relative directory.
	 * @return string
	 */
	private function metadata_relative_path( string $file, string $directory ): string {
		$file = str_replace( '\\', '/', trim( $file ) );

		if ( '' === $file ) {
			return '';
		}

		if ( false !== strpos( $file, '/' ) || '' === $directory ) {
			return $file;
		}

		return $directory . '/' . $file;
	}

	/**
	 * Determine whether a candidate URL matches a relative uploads path.
	 *
	 * @param string $url URL.
	 * @param string $relative_path Relative uploads path.
	 * @return bool
	 */
	private function url_matches_relative_path( string $url, string $relative_path ): bool {
		$relative_path = ltrim( str_replace( '\\', '/', trim( $relative_path ) ), '/' );

		if ( '' === trim( $url ) || '' === $relative_path ) {
			return false;
		}

		$path = parse_url( $url, PHP_URL_PATH );
		$path = is_string( $path ) ? rawurldecode( $path ) : $url;
		$path = str_replace( '\\', '/', trim( $path ) );

		if ( '' === $path ) {
			return false;
		}

		if ( $path === $relative_path ) {
			return true;
		}

		$suffix = '/' . $relative_path;

		return strlen( $path ) >= strlen( $suffix ) && substr( $path, -strlen( $suffix ) ) === $suffix;
	}
}
