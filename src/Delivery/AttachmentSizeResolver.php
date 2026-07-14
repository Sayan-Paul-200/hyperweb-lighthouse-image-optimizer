<?php
/**
 * Attachment size resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;

/**
 * Resolves one attachment metadata size candidate from conservative markup facts.
 */
final class AttachmentSizeResolver {

	/**
	 * Manifest sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Create resolver.
	 *
	 * @param DerivativeManifestSanitizer $sanitizer Manifest sanitizer.
	 */
	public function __construct( DerivativeManifestSanitizer $sanitizer ) {
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Build metadata candidates from attachment image metadata.
	 *
	 * @param array<string,mixed> $image_meta Image metadata.
	 * @return array<int,array<string,mixed>>
	 */
	public function metadata_candidates( array $image_meta ): array {
		$file = $this->sanitizer->safe_relative_path( $image_meta['file'] ?? '' );

		if ( '' === $file ) {
			return array();
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
		}

		$sizes = $image_meta['sizes'] ?? array();

		if ( ! is_array( $sizes ) ) {
			return $candidates;
		}

		foreach ( $sizes as $size_name => $size ) {
			if ( ! is_string( $size_name ) || ! is_array( $size ) ) {
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
			}
		}

		return $candidates;
	}

	/**
	 * Resolve one metadata candidate from an original candidate used for source-set building.
	 *
	 * @param array<string,mixed> $candidate Original candidate.
	 * @param array<string,mixed> $image_meta Image metadata.
	 * @return array<string,mixed>|null
	 */
	public function resolve_source_candidate( array $candidate, array $image_meta ): ?array {
		$metadata_candidates = $this->metadata_candidates( $image_meta );

		if ( array() === $metadata_candidates ) {
			return null;
		}

		$matches = array();

		foreach ( $metadata_candidates as $metadata_candidate ) {
			if (
				(int) ( $candidate['value'] ?? 0 ) === (int) $metadata_candidate['width']
				&& $this->url_matches_relative_path(
					isset( $candidate['url'] ) && is_scalar( $candidate['url'] ) ? (string) $candidate['url'] : '',
					(string) $metadata_candidate['relative_path']
				)
			) {
				$matches[] = $metadata_candidate;
			}
		}

		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/**
	 * Resolve one metadata candidate directly from a concrete source URL.
	 *
	 * @param string              $url Source URL.
	 * @param array<string,mixed> $image_meta Image metadata.
	 * @param int|null            $known_width Known width.
	 * @return array<string,mixed>|null
	 */
	public function resolve_from_url( string $url, array $image_meta, ?int $known_width = null ): ?array {
		if ( '' === trim( $url ) ) {
			return null;
		}

		$metadata_candidates = $this->metadata_candidates( $image_meta );

		if ( array() === $metadata_candidates ) {
			return null;
		}

		$matches = array();

		foreach ( $metadata_candidates as $metadata_candidate ) {
			if ( $this->url_matches_relative_path( $url, (string) $metadata_candidate['relative_path'] ) ) {
				$matches[] = $metadata_candidate;
			}
		}

		if ( 1 === count( $matches ) ) {
			return $matches[0];
		}

		if ( count( $matches ) > 1 && null !== $known_width && $known_width > 0 ) {
			$width_matches = array();

			foreach ( $matches as $metadata_candidate ) {
				if ( (int) $metadata_candidate['width'] === $known_width ) {
					$width_matches[] = $metadata_candidate;
				}
			}

			if ( 1 === count( $width_matches ) ) {
				return $width_matches[0];
			}
		}

		return null;
	}

	/**
	 * Resolve one metadata candidate directly from fallback IMG analysis.
	 *
	 * @param ImageMarkupAnalysis $analysis Markup analysis.
	 * @param array<string,mixed> $image_meta Image metadata.
	 * @param int|null            $known_width Known runtime width.
	 * @return array<string,mixed>|null
	 */
	public function resolve_from_analysis( ImageMarkupAnalysis $analysis, array $image_meta, ?int $known_width = null ): ?array {
		$src = $analysis->src();

		if ( null === $src || '' === $src ) {
			return null;
		}

		return $this->resolve_from_url( $src, $image_meta, $known_width );
	}

	/**
	 * Build one metadata candidate.
	 *
	 * @param string $size_name Size name.
	 * @param string $relative_path Relative path.
	 * @param mixed  $width Width.
	 * @param mixed  $height Height.
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

		if ( function_exists( 'wp_parse_url' ) ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Fallback for non-WordPress test/runtime contexts.
			$path = parse_url( $url, PHP_URL_PATH );
		}

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
