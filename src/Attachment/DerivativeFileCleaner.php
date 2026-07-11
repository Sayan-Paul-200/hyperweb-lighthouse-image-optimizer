<?php
/**
 * Derivative file cleaner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\FilesystemInterface;

/**
 * Safely deletes and inspects plugin-owned derivative files inside uploads.
 */
final class DerivativeFileCleaner {

	/**
	 * Uploads base directory.
	 *
	 * @var string
	 */
	private $uploads_base_dir;

	/**
	 * Filesystem adapter.
	 *
	 * @var FilesystemInterface
	 */
	private $filesystem;

	/**
	 * Create cleaner.
	 *
	 * @param string              $uploads_base_dir Uploads base directory.
	 * @param FilesystemInterface $filesystem Filesystem adapter.
	 */
	public function __construct( string $uploads_base_dir, FilesystemInterface $filesystem ) {
		$this->uploads_base_dir = $uploads_base_dir;
		$this->filesystem       = $filesystem;
	}

	/**
	 * Delete safe derivative files from one manifest scope.
	 *
	 * @param array<string,bool> $source_files Relative source files that must be preserved.
	 * @param string[]           $derivative_files Relative derivative files.
	 * @return AttachmentCleanupResult
	 */
	public function cleanup_files( array $source_files, array $derivative_files ): AttachmentCleanupResult {
		$base_realpath = $this->base_realpath();

		if ( '' === $base_realpath ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_UPLOADS_DIRECTORY_UNAVAILABLE ),
				array( 'The uploads directory could not be resolved.' )
			);
		}

		$deleted = 0;
		$results = array();
		$samples = array();

		foreach ( array_values( array_unique( $derivative_files ) ) as $relative_file ) {
			$relative_file = $this->normalize_relative_path( $relative_file );
			$result        = $this->delete_one( $relative_file, $source_files, $base_realpath );

			if ( $result->has_code( AttachmentCleanupResult::CODE_DERIVATIVES_DELETED ) ) {
				++$deleted;
				$samples[] = $relative_file;
			}

			if ( AttachmentCleanupResult::SEVERITY_SUCCESS !== $result->severity() ) {
				$results[] = $result;
			}
		}

		$results[] = AttachmentCleanupResult::success(
			array( AttachmentCleanupResult::CODE_DERIVATIVES_DELETED ),
			array( sprintf( 'Deleted %d derivative file(s).', $deleted ) ),
			$deleted,
			0,
			0,
			0,
			$samples
		);

		return AttachmentCleanupResult::combine( ...$results );
	}

	/**
	 * Report existing deterministic sidecars not present in authoritative metadata.
	 *
	 * @param string[] $candidate_sources Relative source paths.
	 * @param string[] $authoritative_files Authoritative derivative files.
	 * @return AttachmentCleanupResult
	 */
	public function find_existing_orphans( array $candidate_sources, array $authoritative_files ): AttachmentCleanupResult {
		$base_realpath = $this->base_realpath();

		if ( '' === $base_realpath ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_UPLOADS_DIRECTORY_UNAVAILABLE ),
				array( 'The uploads directory could not be resolved.' )
			);
		}

		$known   = array();
		$orphans = array();

		foreach ( $authoritative_files as $file ) {
			$file = $this->normalize_relative_path( $file );

			if ( '' !== $file ) {
				$known[ $file ] = true;
			}
		}

		foreach ( array_values( array_unique( $candidate_sources ) ) as $source ) {
			$source = $this->normalize_relative_path( $source );

			if ( ! $this->is_safe_relative_path( $source ) ) {
				continue;
			}

			foreach ( AttachmentStatus::formats() as $format ) {
				$derivative = $source . '.hwlio.' . $format;

				if ( isset( $known[ $derivative ] ) ) {
					continue;
				}

				if ( $this->is_existing_regular_file_within_base( $derivative, $base_realpath ) ) {
					$orphans[] = $derivative;
				}
			}
		}

		$orphans = array_values( array_unique( $orphans ) );

		if ( array() === $orphans ) {
			return AttachmentCleanupResult::success(
				array( AttachmentCleanupResult::CODE_NO_ORPHAN_DERIVATIVES ),
				array( 'No orphan derivative files were detected.' )
			);
		}

		return AttachmentCleanupResult::warning(
			array( AttachmentCleanupResult::CODE_ORPHAN_DERIVATIVES_DETECTED ),
			array( sprintf( 'Detected %d orphan derivative file(s) in dry-run mode.', count( $orphans ) ) ),
			0,
			0,
			0,
			count( $orphans ),
			array(),
			$orphans
		);
	}

	/**
	 * Extract relative source files from a raw manifest array.
	 *
	 * @param array<string,mixed> $manifest Raw manifest.
	 * @return array<string,bool>
	 */
	public static function source_files_from_manifest_array( array $manifest ): array {
		$sources = array();
		$sizes   = $manifest['sizes'] ?? array();

		if ( ! is_array( $sizes ) ) {
			return $sources;
		}

		foreach ( $sizes as $size ) {
			if ( ! is_array( $size ) || ! isset( $size['source'] ) || ! is_array( $size['source'] ) ) {
				continue;
			}

			$file = isset( $size['source']['file'] ) && is_scalar( $size['source']['file'] )
				? self::normalize_static_relative_path( (string) $size['source']['file'] )
				: '';

			if ( '' !== $file ) {
				$sources[ $file ] = true;
			}
		}

		return $sources;
	}

	/**
	 * Extract relative derivative files from a raw manifest array.
	 *
	 * @param array<string,mixed> $manifest Raw manifest.
	 * @return string[]
	 */
	public static function derivative_files_from_manifest_array( array $manifest ): array {
		$files = array();
		$sizes = $manifest['sizes'] ?? array();

		if ( ! is_array( $sizes ) ) {
			return $files;
		}

		foreach ( $sizes as $size ) {
			if ( ! is_array( $size ) || ! isset( $size['formats'] ) || ! is_array( $size['formats'] ) ) {
				continue;
			}

			foreach ( $size['formats'] as $format ) {
				if ( ! is_array( $format ) ) {
					continue;
				}

				$file = isset( $format['file'] ) && is_scalar( $format['file'] )
					? self::normalize_static_relative_path( (string) $format['file'] )
					: '';

				if ( '' !== $file ) {
					$files[] = $file;
				}
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * Extract relative source files from a sanitized manifest.
	 *
	 * @param DerivativeManifest $manifest Manifest.
	 * @return array<string,bool>
	 */
	public static function source_files_from_manifest( DerivativeManifest $manifest ): array {
		return self::source_files_from_manifest_array( $manifest->to_array() );
	}

	/**
	 * Extract relative derivative files from a sanitized manifest.
	 *
	 * @param DerivativeManifest $manifest Manifest.
	 * @return string[]
	 */
	public static function derivative_files_from_manifest( DerivativeManifest $manifest ): array {
		return self::derivative_files_from_manifest_array( $manifest->to_array() );
	}

	/**
	 * Delete one derivative if it is safe.
	 *
	 * @param string             $relative_file Relative derivative path.
	 * @param array<string,bool> $source_files Source file map.
	 * @param string             $base_realpath Normalized uploads base realpath.
	 * @return AttachmentCleanupResult
	 */
	private function delete_one( string $relative_file, array $source_files, string $base_realpath ): AttachmentCleanupResult {
		if ( '' === $relative_file || isset( $source_files[ $relative_file ] ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_ORIGINAL_PRESERVED ),
				array( 'A source/original file path was preserved during derivative cleanup.' )
			);
		}

		if ( ! $this->is_safe_relative_path( $relative_file ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it is not a safe uploads-relative path.' )
			);
		}

		$candidate = $this->normalize_path( $this->uploads_base_dir . '/' . $relative_file );

		if ( ! $this->filesystem->exists( $candidate ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_DERIVATIVE_MISSING ),
				array( 'A derivative path listed in metadata does not exist.' )
			);
		}

		if ( $this->filesystem->is_link( $candidate ) || ! $this->filesystem->is_file( $candidate ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it is not a regular file.' )
			);
		}

		$candidate_realpath = $this->normalize_path( $this->filesystem->realpath( $candidate ) );

		if ( ! $this->is_within_base( $candidate_realpath, $base_realpath ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it resolves outside uploads.' )
			);
		}

		if ( ! $this->filesystem->delete( $candidate_realpath ) ) {
			return AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path could not be deleted.' )
			);
		}

		return AttachmentCleanupResult::success(
			array( AttachmentCleanupResult::CODE_DERIVATIVES_DELETED ),
			array(),
			1,
			0,
			0,
			0,
			array( $relative_file )
		);
	}

	/**
	 * Resolve the uploads base realpath.
	 *
	 * @return string
	 */
	private function base_realpath(): string {
		return $this->normalize_path( $this->filesystem->realpath( $this->uploads_base_dir ) );
	}

	/**
	 * Whether a deterministic derivative exists as a safe regular file.
	 *
	 * @param string $relative_file Relative derivative path.
	 * @param string $base_realpath Base realpath.
	 * @return bool
	 */
	private function is_existing_regular_file_within_base( string $relative_file, string $base_realpath ): bool {
		if ( ! $this->is_safe_relative_path( $relative_file ) ) {
			return false;
		}

		$candidate = $this->normalize_path( $this->uploads_base_dir . '/' . $relative_file );

		if ( ! $this->filesystem->exists( $candidate ) || $this->filesystem->is_link( $candidate ) || ! $this->filesystem->is_file( $candidate ) ) {
			return false;
		}

		$candidate_realpath = $this->normalize_path( $this->filesystem->realpath( $candidate ) );

		return $this->is_within_base( $candidate_realpath, $base_realpath );
	}

	/**
	 * Determine whether a relative path is safe.
	 *
	 * @param string $relative_file Relative path.
	 * @return bool
	 */
	private function is_safe_relative_path( string $relative_file ): bool {
		if ( false !== strpos( $relative_file, "\0" ) ) {
			return false;
		}

		if ( 1 === preg_match( '#^(?:[A-Za-z]:)?[\\\\/]#', $relative_file ) || false !== strpos( $relative_file, '://' ) ) {
			return false;
		}

		foreach ( explode( '/', $relative_file ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine whether a real path is inside uploads.
	 *
	 * @param string $candidate_realpath Candidate realpath.
	 * @param string $base_realpath Base realpath.
	 * @return bool
	 */
	private function is_within_base( string $candidate_realpath, string $base_realpath ): bool {
		if ( '' === $candidate_realpath || '' === $base_realpath || $candidate_realpath === $base_realpath ) {
			return false;
		}

		return 0 === strpos( $candidate_realpath, rtrim( $base_realpath, '/' ) . '/' );
	}

	/**
	 * Normalize path separators.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return str_replace( '\\', '/', $path );
	}

	/**
	 * Normalize a relative path.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		return self::normalize_static_relative_path( $path );
	}

	/**
	 * Normalize a relative path statically.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private static function normalize_static_relative_path( string $path ): string {
		return ltrim( str_replace( '\\', '/', trim( $path ) ), '/' );
	}
}
