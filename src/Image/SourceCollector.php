<?php
/**
 * Source image collector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Collects normalized source image records from attachment metadata.
 */
final class SourceCollector {

	/**
	 * Attachment source provider.
	 *
	 * @var AttachmentSourceProviderInterface
	 */
	private $provider;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Create a WordPress-backed collector.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentSourceProvider(),
			new WordPressImageFileProbe()
		);
	}

	/**
	 * Create collector.
	 *
	 * @param AttachmentSourceProviderInterface $provider Source provider.
	 * @param ImageFileProbeInterface           $files File probe.
	 */
	public function __construct( AttachmentSourceProviderInterface $provider, ImageFileProbeInterface $files ) {
		$this->provider = $provider;
		$this->files    = $files;
	}

	/**
	 * Collect source images for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return SourceImageCollection
	 */
	public function collect( int $attachment_id ): SourceImageCollection {
		$attachment_id = max( 0, $attachment_id );
		$sources       = array();
		$issues        = array();
		$seen          = array();

		$uploads_base = $this->provider->uploads_base_dir();
		$base_real    = $this->base_realpath( $uploads_base );

		if ( '' === $base_real ) {
			return new SourceImageCollection(
				array(),
				array(
					$this->issue(
						$attachment_id,
						'attachment',
						SourceImage::ROLE_FULL,
						SourceImageIssue::CODE_UPLOADS_UNAVAILABLE,
						'The uploads directory could not be resolved for source collection.'
					),
				)
			);
		}

		$metadata         = $this->provider->metadata( $attachment_id );
		$metadata_file    = is_array( $metadata ) ? $this->value_to_string( $metadata['file'] ?? '' ) : '';
		$metadata_dir     = $this->metadata_directory( $metadata_file );
		$attached_file    = $this->provider->attached_file( $attachment_id );
		$full_dimensions  = $this->dimensions_from_metadata( $metadata );
		$full_source_path = is_string( $attached_file ) ? $attached_file : '';

		if ( null === $metadata ) {
			$issues[] = $this->issue(
				$attachment_id,
				'full',
				SourceImage::ROLE_FULL,
				SourceImageIssue::CODE_MALFORMED_METADATA,
				'Attachment metadata is missing or malformed.'
			);
		}

		if ( '' === trim( $full_source_path ) ) {
			$issues[] = $this->issue(
				$attachment_id,
				'full',
				SourceImage::ROLE_FULL,
				SourceImageIssue::CODE_SOURCE_MISSING,
				'The current attached file is missing.'
			);
		} else {
			$this->collect_candidate(
				$attachment_id,
				'full',
				SourceImage::ROLE_FULL,
				$full_source_path,
				false,
				$metadata_dir,
				$full_dimensions,
				$base_real,
				$sources,
				$issues,
				$seen
			);
		}

		$this->collect_sizes(
			$attachment_id,
			$metadata,
			$metadata_dir,
			$base_real,
			$sources,
			$issues,
			$seen
		);

		$this->collect_original(
			$attachment_id,
			$metadata,
			$metadata_dir,
			$base_real,
			$sources,
			$issues,
			$seen
		);

		return new SourceImageCollection( $sources, $issues );
	}

	/**
	 * Collect metadata sizes.
	 *
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @param string                   $metadata_dir Metadata directory.
	 * @param string                   $base_real Base real path.
	 * @param SourceImage[]            $sources Sources.
	 * @param SourceImageIssue[]       $issues Issues.
	 * @param array<string,bool>       $seen Seen relative paths.
	 * @return void
	 */
	private function collect_sizes(
		int $attachment_id,
		?array $metadata,
		string $metadata_dir,
		string $base_real,
		array &$sources,
		array &$issues,
		array &$seen
	): void {
		if ( ! is_array( $metadata ) || ! isset( $metadata['sizes'] ) ) {
			return;
		}

		if ( ! is_array( $metadata['sizes'] ) ) {
			$issues[] = $this->issue(
				$attachment_id,
				'sizes',
				SourceImage::ROLE_SUBSIZE,
				SourceImageIssue::CODE_MALFORMED_METADATA,
				'Attachment size metadata is malformed.'
			);
			return;
		}

		foreach ( $metadata['sizes'] as $size_name => $size ) {
			$size_name = is_string( $size_name ) ? $size_name : (string) $size_name;

			if ( ! is_array( $size ) ) {
				$issues[] = $this->issue(
					$attachment_id,
					$size_name,
					SourceImage::ROLE_SUBSIZE,
					SourceImageIssue::CODE_MALFORMED_METADATA,
					'An attachment size record is malformed.'
				);
				continue;
			}

			$file = $this->value_to_string( $size['file'] ?? '' );

			if ( '' === $file ) {
				$issues[] = $this->issue(
					$attachment_id,
					$size_name,
					SourceImage::ROLE_SUBSIZE,
					SourceImageIssue::CODE_MALFORMED_METADATA,
					'An attachment size record is missing its file.'
				);
				continue;
			}

			$this->collect_candidate(
				$attachment_id,
				$size_name,
				SourceImage::ROLE_SUBSIZE,
				$file,
				true,
				$metadata_dir,
				$this->dimensions_from_size( $size ),
				$base_real,
				$sources,
				$issues,
				$seen
			);
		}
	}

	/**
	 * Collect original image source when present.
	 *
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @param string                   $metadata_dir Metadata directory.
	 * @param string                   $base_real Base real path.
	 * @param SourceImage[]            $sources Sources.
	 * @param SourceImageIssue[]       $issues Issues.
	 * @param array<string,bool>       $seen Seen relative paths.
	 * @return void
	 */
	private function collect_original(
		int $attachment_id,
		?array $metadata,
		string $metadata_dir,
		string $base_real,
		array &$sources,
		array &$issues,
		array &$seen
	): void {
		if ( ! is_array( $metadata ) || ! isset( $metadata['original_image'] ) ) {
			return;
		}

		$original = $this->value_to_string( $metadata['original_image'] );

		if ( '' === $original ) {
			$issues[] = $this->issue(
				$attachment_id,
				'original',
				SourceImage::ROLE_ORIGINAL,
				SourceImageIssue::CODE_MALFORMED_METADATA,
				'The original image metadata is malformed.'
			);
			return;
		}

		$this->collect_candidate(
			$attachment_id,
			'original',
			SourceImage::ROLE_ORIGINAL,
			$original,
			true,
			$metadata_dir,
			null,
			$base_real,
			$sources,
			$issues,
			$seen
		);
	}

	/**
	 * Collect one source candidate.
	 *
	 * @param int                              $attachment_id Attachment ID.
	 * @param string                           $size_name Size name.
	 * @param string                           $role Role.
	 * @param string                           $path Path.
	 * @param bool                             $metadata_relative Whether path came from metadata.
	 * @param string                           $metadata_dir Metadata directory.
	 * @param array{width:int,height:int}|null $dimensions Dimensions from metadata.
	 * @param string                           $base_real Base real path.
	 * @param SourceImage[]                    $sources Sources.
	 * @param SourceImageIssue[]               $issues Issues.
	 * @param array<string,bool>               $seen Seen relative paths.
	 * @return void
	 */
	private function collect_candidate(
		int $attachment_id,
		string $size_name,
		string $role,
		string $path,
		bool $metadata_relative,
		string $metadata_dir,
		?array $dimensions,
		string $base_real,
		array &$sources,
		array &$issues,
		array &$seen
	): void {
		$resolved = $this->resolve_path( $path, $metadata_relative, $metadata_dir, $base_real );

		if ( isset( $resolved['issue_code'] ) ) {
			$issues[] = $this->issue(
				$attachment_id,
				$size_name,
				$role,
				(string) $resolved['issue_code'],
				(string) $resolved['message'],
				array(
					'relative_path' => $resolved['relative_path'] ?? '',
				)
			);
			return;
		}

		$absolute_path = (string) $resolved['absolute_path'];
		$relative_path = (string) $resolved['relative_path'];

		if ( isset( $seen[ $relative_path ] ) ) {
			$issues[] = $this->issue(
				$attachment_id,
				$size_name,
				$role,
				SourceImageIssue::CODE_DUPLICATE_SOURCE,
				'A duplicate source path was ignored.',
				array(
					'relative_path' => $relative_path,
				)
			);
			return;
		}

		$file = $this->file_facts( $absolute_path, $dimensions );

		if ( isset( $file['issue_code'] ) ) {
			$issues[] = $this->issue(
				$attachment_id,
				$size_name,
				$role,
				(string) $file['issue_code'],
				(string) $file['message'],
				array(
					'relative_path' => $relative_path,
				)
			);
			return;
		}

		$seen[ $relative_path ] = true;
		$sources[]              = new SourceImage(
			$attachment_id,
			$size_name,
			$role,
			$relative_path,
			$absolute_path,
			$file['mime_type'],
			(int) $file['width'],
			(int) $file['height'],
			(int) $file['bytes'],
			(int) $file['modified_time']
		);
	}

	/**
	 * Resolve and validate a candidate path.
	 *
	 * @param string $path Path.
	 * @param bool   $metadata_relative Whether path came from metadata.
	 * @param string $metadata_dir Metadata directory.
	 * @param string $base_real Base real path.
	 * @return array<string,mixed>
	 */
	private function resolve_path( string $path, bool $metadata_relative, string $metadata_dir, string $base_real ): array {
		$path = $this->normalize_path( $path );

		if ( '' === $path || false !== strpos( $path, "\0" ) || $this->is_url_like( $path ) ) {
			return $this->path_issue( SourceImageIssue::CODE_UNSAFE_SOURCE_PATH, 'The source path is unsafe.' );
		}

		if ( $metadata_relative ) {
			if ( $this->is_absolute_path( $path ) || ! $this->is_safe_relative_path( $path ) ) {
				return $this->path_issue( SourceImageIssue::CODE_UNSAFE_SOURCE_PATH, 'The metadata source path is unsafe.' );
			}

			$relative_path  = $this->metadata_relative_path( $path, $metadata_dir );
			$absolute_path  = rtrim( $base_real, '/' ) . '/' . $relative_path;
			$relative_hint  = $relative_path;
			$lexical_inside = true;
		} elseif ( $this->is_absolute_path( $path ) ) {
			$absolute_path  = $path;
			$lexical_inside = $this->is_within_base_lexically( $absolute_path, $base_real );
			$relative_hint  = $lexical_inside ? $this->relative_from_base( $absolute_path, $base_real ) : '';
		} else {
			if ( ! $this->is_safe_relative_path( $path ) ) {
				return $this->path_issue( SourceImageIssue::CODE_UNSAFE_SOURCE_PATH, 'The source path is unsafe.' );
			}

			$relative_path  = $path;
			$absolute_path  = rtrim( $base_real, '/' ) . '/' . $relative_path;
			$relative_hint  = $relative_path;
			$lexical_inside = true;
		}

		$absolute_path = $this->normalize_path( $absolute_path );
		$realpath      = $this->normalize_path( $this->files->realpath( $absolute_path ) );

		if ( '' === $realpath ) {
			if ( ! $lexical_inside ) {
				return $this->path_issue(
					SourceImageIssue::CODE_OUTSIDE_UPLOADS,
					'The source path is outside uploads.',
					$relative_hint
				);
			}

			return $this->path_issue(
				SourceImageIssue::CODE_SOURCE_MISSING,
				'The source file does not exist.',
				$relative_hint
			);
		}

		if ( ! $this->is_within_base_lexically( $realpath, $base_real ) ) {
			return $this->path_issue(
				SourceImageIssue::CODE_OUTSIDE_UPLOADS,
				'The source real path resolves outside uploads.',
				$relative_hint
			);
		}

		return array(
			'absolute_path' => $realpath,
			'relative_path' => $this->relative_from_base( $realpath, $base_real ),
		);
	}

	/**
	 * Read file facts.
	 *
	 * @param string                           $absolute_path Absolute path.
	 * @param array{width:int,height:int}|null $dimensions Metadata dimensions.
	 * @return array<string,mixed>
	 */
	private function file_facts( string $absolute_path, ?array $dimensions ): array {
		if ( ! $this->files->exists( $absolute_path ) ) {
			return $this->file_issue( SourceImageIssue::CODE_SOURCE_MISSING, 'The source file does not exist.' );
		}

		if ( ! $this->files->is_file( $absolute_path ) || ! $this->files->is_readable( $absolute_path ) ) {
			return $this->file_issue( SourceImageIssue::CODE_SOURCE_UNREADABLE, 'The source file is not readable.' );
		}

		$bytes         = $this->files->file_size( $absolute_path );
		$modified_time = $this->files->modified_time( $absolute_path );

		if ( null === $bytes || null === $modified_time ) {
			return $this->file_issue( SourceImageIssue::CODE_SOURCE_UNREADABLE, 'The source file facts could not be read.' );
		}

		$dimensions = $dimensions ?? $this->files->dimensions( $absolute_path );

		if ( null === $dimensions ) {
			return $this->file_issue( SourceImageIssue::CODE_MALFORMED_METADATA, 'The source dimensions could not be determined.' );
		}

		return array(
			'mime_type'     => $this->files->mime_type( $absolute_path ),
			'width'         => (int) $dimensions['width'],
			'height'        => (int) $dimensions['height'],
			'bytes'         => (int) $bytes,
			'modified_time' => (int) $modified_time,
		);
	}

	/**
	 * Resolve uploads base real path.
	 *
	 * @param string|null $uploads_base Uploads base path.
	 * @return string
	 */
	private function base_realpath( ?string $uploads_base ): string {
		if ( null === $uploads_base || '' === trim( $uploads_base ) ) {
			return '';
		}

		return $this->normalize_path( $this->files->realpath( $uploads_base ) );
	}

	/**
	 * Extract metadata directory.
	 *
	 * @param string $metadata_file Metadata file.
	 * @return string
	 */
	private function metadata_directory( string $metadata_file ): string {
		$metadata_file = $this->normalize_path( trim( $metadata_file ) );

		if ( '' === $metadata_file || ! $this->is_safe_relative_path( $metadata_file ) ) {
			return '';
		}

		$directory = dirname( $metadata_file );

		return '.' === $directory ? '' : trim( $this->normalize_path( $directory ), '/' );
	}

	/**
	 * Resolve a metadata relative path.
	 *
	 * @param string $path Path.
	 * @param string $metadata_dir Metadata directory.
	 * @return string
	 */
	private function metadata_relative_path( string $path, string $metadata_dir ): string {
		$path = ltrim( $this->normalize_path( $path ), '/' );

		if ( false !== strpos( $path, '/' ) || '' === $metadata_dir ) {
			return $path;
		}

		return trim( $metadata_dir, '/' ) . '/' . $path;
	}

	/**
	 * Get dimensions from full metadata.
	 *
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @return array{width:int,height:int}|null
	 */
	private function dimensions_from_metadata( ?array $metadata ): ?array {
		if ( ! is_array( $metadata ) ) {
			return null;
		}

		return $this->dimensions_from_values( $metadata['width'] ?? null, $metadata['height'] ?? null );
	}

	/**
	 * Get dimensions from size metadata.
	 *
	 * @param array<string,mixed> $size Size metadata.
	 * @return array{width:int,height:int}|null
	 */
	private function dimensions_from_size( array $size ): ?array {
		return $this->dimensions_from_values( $size['width'] ?? null, $size['height'] ?? null );
	}

	/**
	 * Normalize dimensions from values.
	 *
	 * @param mixed $width Width.
	 * @param mixed $height Height.
	 * @return array{width:int,height:int}|null
	 */
	private function dimensions_from_values( $width, $height ): ?array {
		if ( ! is_numeric( $width ) || ! is_numeric( $height ) ) {
			return null;
		}

		$width  = (int) $width;
		$height = (int) $height;

		if ( 0 >= $width || 0 >= $height ) {
			return null;
		}

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * Determine whether a relative path is safe.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_safe_relative_path( string $path ): bool {
		if ( '' === $path || false !== strpos( $path, "\0" ) || $this->is_url_like( $path ) || $this->is_absolute_path( $path ) ) {
			return false;
		}

		foreach ( explode( '/', $this->normalize_path( $path ) ) as $segment ) {
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
		return 1 === preg_match( '#^(?:[A-Za-z]:)?/#', $path );
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
	 * Determine whether a path is inside uploads by normalized prefix.
	 *
	 * @param string $path Path.
	 * @param string $base_real Base real path.
	 * @return bool
	 */
	private function is_within_base_lexically( string $path, string $base_real ): bool {
		$path      = $this->normalize_path( $path );
		$base_real = rtrim( $this->normalize_path( $base_real ), '/' );

		return '' !== $path && '' !== $base_real && 0 === strpos( $path, $base_real . '/' );
	}

	/**
	 * Build a relative path from a path inside uploads.
	 *
	 * @param string $path Path.
	 * @param string $base_real Base real path.
	 * @return string
	 */
	private function relative_from_base( string $path, string $base_real ): string {
		$path      = $this->normalize_path( $path );
		$base_real = rtrim( $this->normalize_path( $base_real ), '/' );

		if ( ! $this->is_within_base_lexically( $path, $base_real ) ) {
			return '';
		}

		return ltrim( substr( $path, strlen( $base_real ) ), '/' );
	}

	/**
	 * Build path issue payload.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 * @param string $relative_path Relative path.
	 * @return array<string,mixed>
	 */
	private function path_issue( string $code, string $message, string $relative_path = '' ): array {
		return array(
			'issue_code'    => $code,
			'message'       => $message,
			'relative_path' => $relative_path,
		);
	}

	/**
	 * Build file issue payload.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 * @return array<string,string>
	 */
	private function file_issue( string $code, string $message ): array {
		return array(
			'issue_code' => $code,
			'message'    => $message,
		);
	}

	/**
	 * Build collection issue.
	 *
	 * @param int          $attachment_id Attachment ID.
	 * @param string       $size_name Size name.
	 * @param string       $role Role.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 * @return SourceImageIssue
	 */
	private function issue(
		int $attachment_id,
		string $size_name,
		string $role,
		string $code,
		string $message,
		array $details = array()
	): SourceImageIssue {
		return new SourceImageIssue( $attachment_id, $size_name, $role, $code, $message, $details );
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
	 * Safely cast scalar values to strings.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function value_to_string( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}
