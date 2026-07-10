<?php
/**
 * Destination resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Resolves deterministic sidecar paths for source image derivatives.
 */
final class DestinationResolver {

	/**
	 * Target MIME map.
	 *
	 * @var array<string,string>
	 */
	private const TARGET_MIME_TYPES = array(
		SourceMimePolicy::TARGET_WEBP => 'image/webp',
		SourceMimePolicy::TARGET_AVIF => 'image/avif',
	);

	/**
	 * Uploads base directory.
	 *
	 * @var string
	 */
	private $uploads_base_dir;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Create WordPress-backed resolver.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$uploads_base_dir = '';

		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = \wp_upload_dir( null, false );

			if ( is_array( $uploads ) && empty( $uploads['error'] ) ) {
				$uploads_base_dir = (string) $uploads['basedir'];
			}
		}

		return new self( $uploads_base_dir, new WordPressImageFileProbe() );
	}

	/**
	 * Create resolver.
	 *
	 * @param string                  $uploads_base_dir Uploads base directory.
	 * @param ImageFileProbeInterface $files File probe.
	 */
	public function __construct( string $uploads_base_dir, ImageFileProbeInterface $files ) {
		$this->uploads_base_dir = $uploads_base_dir;
		$this->files            = $files;
	}

	/**
	 * Resolve a destination for a source and target format.
	 *
	 * @param SourceImage $source Source image.
	 * @param string      $target_format Target format.
	 * @return DestinationResolutionResult
	 */
	public function resolve( SourceImage $source, string $target_format ): DestinationResolutionResult {
		$target_format = strtolower( trim( $target_format ) );

		if ( ! isset( self::TARGET_MIME_TYPES[ $target_format ] ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_INVALID_TARGET_FORMAT,
				'The target format is not supported for destination resolution.',
				array(
					'target_format' => $target_format,
				)
			);
		}

		$base_realpath = $this->uploads_base_realpath();

		if ( '' === $base_realpath ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_UPLOADS_UNAVAILABLE,
				'The uploads directory could not be resolved for destination resolution.'
			);
		}

		$source_relative_path = $this->normalize_relative_path( $source->relative_path() );

		if ( ! $this->is_safe_relative_path( $source_relative_path ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_UNSAFE_SOURCE_PATH,
				'The source relative path is not safe for destination resolution.',
				array(
					'relative_path' => $source_relative_path,
				)
			);
		}

		$source_realpath = $this->normalize_path( $this->files->realpath( $source->absolute_path() ) );

		if ( ! $this->is_within_base( $source_realpath, $base_realpath ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_SOURCE_OUTSIDE_UPLOADS,
				'The source real path is outside uploads.'
			);
		}

		$destination_relative_path = $source_relative_path . '.hwlio.' . $target_format;
		$temporary_relative_path   = $destination_relative_path . '.tmp';

		if ( ! $this->is_safe_relative_path( $destination_relative_path ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_DESTINATION_OUTSIDE_UPLOADS,
				'The destination relative path is not safe.'
			);
		}

		if ( ! $this->is_safe_relative_path( $temporary_relative_path ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_TEMPORARY_OUTSIDE_UPLOADS,
				'The temporary relative path is not safe.'
			);
		}

		$destination_absolute_path = $this->absolute_path( $base_realpath, $destination_relative_path );
		$temporary_absolute_path   = $this->absolute_path( $base_realpath, $temporary_relative_path );

		if ( ! $this->is_within_base( $destination_absolute_path, $base_realpath ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_DESTINATION_OUTSIDE_UPLOADS,
				'The destination path is outside uploads.'
			);
		}

		if ( ! $this->is_within_base( $temporary_absolute_path, $base_realpath ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_TEMPORARY_OUTSIDE_UPLOADS,
				'The temporary path is outside uploads.'
			);
		}

		if ( $this->same_path( $source_realpath, $destination_absolute_path ) || $source_relative_path === $destination_relative_path ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_DESTINATION_COLLISION,
				'The destination path collides with the source path.'
			);
		}

		if (
			$this->same_path( $temporary_absolute_path, $source_realpath )
			|| $this->same_path( $temporary_absolute_path, $destination_absolute_path )
			|| $temporary_relative_path === $source_relative_path
			|| $temporary_relative_path === $destination_relative_path
		) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_TEMPORARY_COLLISION,
				'The temporary path collides with a source or destination path.'
			);
		}

		$destination_realpath = $this->existing_realpath( $destination_absolute_path );

		if ( '' !== $destination_realpath && ! $this->is_within_base( $destination_realpath, $base_realpath ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_DESTINATION_REALPATH_OUTSIDE_UPLOADS,
				'The existing destination real path resolves outside uploads.'
			);
		}

		$temporary_realpath = $this->existing_realpath( $temporary_absolute_path );

		if ( '' !== $temporary_realpath && ! $this->is_within_base( $temporary_realpath, $base_realpath ) ) {
			return DestinationResolutionResult::invalid(
				$source,
				DestinationResolutionResult::CODE_TEMPORARY_REALPATH_OUTSIDE_UPLOADS,
				'The existing temporary real path resolves outside uploads.'
			);
		}

		return DestinationResolutionResult::resolved(
			$source,
			new DestinationPath(
				$target_format,
				self::TARGET_MIME_TYPES[ $target_format ],
				$destination_relative_path,
				$destination_absolute_path,
				$temporary_relative_path,
				$temporary_absolute_path
			)
		);
	}

	/**
	 * Resolve uploads base realpath.
	 *
	 * @return string
	 */
	private function uploads_base_realpath(): string {
		if ( '' === trim( $this->uploads_base_dir ) ) {
			return '';
		}

		return rtrim( $this->normalize_path( $this->files->realpath( $this->uploads_base_dir ) ), '/' );
	}

	/**
	 * Get an existing realpath when a candidate exists.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function existing_realpath( string $path ): string {
		if ( ! $this->files->exists( $path ) ) {
			return '';
		}

		return $this->normalize_path( $this->files->realpath( $path ) );
	}

	/**
	 * Build an absolute path inside uploads.
	 *
	 * @param string $base_realpath Base realpath.
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function absolute_path( string $base_realpath, string $relative_path ): string {
		return rtrim( $this->normalize_path( $base_realpath ), '/' ) . '/' . $this->normalize_relative_path( $relative_path );
	}

	/**
	 * Determine whether a relative path is safe.
	 *
	 * @param string $relative_path Relative path.
	 * @return bool
	 */
	private function is_safe_relative_path( string $relative_path ): bool {
		if ( '' === $relative_path || false !== strpos( $relative_path, "\0" ) || $this->is_url_like( $relative_path ) || $this->is_absolute_path( $relative_path ) ) {
			return false;
		}

		foreach ( explode( '/', $this->normalize_path( $relative_path ) ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine whether a path is absolute.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_absolute_path( string $path ): bool {
		return 1 === preg_match( '#^(?:[A-Za-z]:)?/#', $this->normalize_path( $path ) );
	}

	/**
	 * Determine whether a path is URL-like.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_url_like( string $path ): bool {
		return 0 === strpos( $path, '//' ) || false !== strpos( $path, '://' );
	}

	/**
	 * Determine whether a path is inside uploads.
	 *
	 * @param string $path Path.
	 * @param string $base_realpath Base real path.
	 * @return bool
	 */
	private function is_within_base( string $path, string $base_realpath ): bool {
		$path          = $this->normalize_path( $path );
		$base_realpath = rtrim( $this->normalize_path( $base_realpath ), '/' );

		return '' !== $path && '' !== $base_realpath && 0 === strpos( $path, $base_realpath . '/' );
	}

	/**
	 * Determine whether two paths normalize to the same path.
	 *
	 * @param string $left Left path.
	 * @param string $right Right path.
	 * @return bool
	 */
	private function same_path( string $left, string $right ): bool {
		return $this->normalize_path( $left ) === $this->normalize_path( $right );
	}

	/**
	 * Normalize a path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return str_replace( '\\', '/', trim( $path ) );
	}

	/**
	 * Normalize a relative path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		return ltrim( $this->normalize_path( $path ), '/' );
	}
}
