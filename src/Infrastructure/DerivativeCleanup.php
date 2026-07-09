<?php
/**
 * Safe derivative cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Deletes only attachment-owned derivative files recorded in plugin metadata.
 */
final class DerivativeCleanup implements DerivativeCleanupInterface {

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
	 * Manifest provider.
	 *
	 * @var DerivativeManifestProviderInterface
	 */
	private $manifests;

	/**
	 * Create the cleanup service.
	 *
	 * @param string                              $uploads_base_dir Uploads base directory.
	 * @param FilesystemInterface                 $filesystem Filesystem adapter.
	 * @param DerivativeManifestProviderInterface $manifests Manifest provider.
	 */
	public function __construct(
		string $uploads_base_dir,
		FilesystemInterface $filesystem,
		DerivativeManifestProviderInterface $manifests
	) {
		$this->uploads_base_dir = $uploads_base_dir;
		$this->filesystem       = $filesystem;
		$this->manifests        = $manifests;
	}

	/**
	 * Delete eligible derivative files.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult {
		$base_realpath = $this->filesystem->realpath( $this->uploads_base_dir );

		if ( '' === $base_realpath ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_UPLOADS_DIRECTORY_UNAVAILABLE ),
				array( 'The uploads directory could not be resolved.' )
			);
		}

		$base_realpath = $this->normalize_path( $base_realpath );
		$deleted       = 0;
		$warnings      = array();

		foreach ( $this->manifests->manifests() as $manifest ) {
			$sources = $this->source_files( $manifest );

			foreach ( $this->derivative_files( $manifest ) as $relative_file ) {
				$result = $this->delete_derivative( $relative_file, $sources, $base_realpath );

				if ( $result->has_code( LifecycleResult::CODE_DERIVATIVES_DELETED ) ) {
					++$deleted;
				}

				if ( $result->has_warnings() ) {
					$warnings[] = $result;
				}
			}
		}

		$result = LifecycleResult::success(
			array( LifecycleResult::CODE_DERIVATIVES_DELETED ),
			array( sprintf( 'Deleted %d derivative file(s).', $deleted ) )
		);

		if ( array() === $warnings ) {
			return $result;
		}

		return LifecycleResult::combine( $result, ...$warnings );
	}

	/**
	 * Delete one derivative file if it is safe.
	 *
	 * @param string             $relative_file Relative derivative path from metadata.
	 * @param array<string,bool> $source_files Relative source paths that must never be deleted.
	 * @param string             $base_realpath Normalized uploads base real path.
	 * @return LifecycleResult
	 */
	private function delete_derivative( string $relative_file, array $source_files, string $base_realpath ): LifecycleResult {
		$relative_file = $this->normalize_relative_path( $relative_file );

		if ( '' === $relative_file || isset( $source_files[ $relative_file ] ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_ORIGINAL_PRESERVED ),
				array( 'A source/original file path was preserved during derivative cleanup.' )
			);
		}

		if ( ! $this->is_safe_relative_path( $relative_file ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it is not a safe uploads-relative path.' )
			);
		}

		$candidate = $this->normalize_path( $this->uploads_base_dir . '/' . $relative_file );

		if ( ! $this->filesystem->exists( $candidate ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_DERIVATIVE_MISSING ),
				array( 'A derivative path listed in metadata does not exist.' )
			);
		}

		if ( $this->filesystem->is_link( $candidate ) || ! $this->filesystem->is_file( $candidate ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it is not a regular file.' )
			);
		}

		$candidate_realpath = $this->normalize_path( $this->filesystem->realpath( $candidate ) );

		if ( ! $this->is_within_base( $candidate_realpath, $base_realpath ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path was rejected because it resolves outside uploads.' )
			);
		}

		if ( ! $this->filesystem->delete( $candidate_realpath ) ) {
			return LifecycleResult::warning(
				array( LifecycleResult::CODE_DERIVATIVE_REJECTED ),
				array( 'A derivative path could not be deleted.' )
			);
		}

		return LifecycleResult::success( array( LifecycleResult::CODE_DERIVATIVES_DELETED ) );
	}

	/**
	 * Extract source files from derivative metadata.
	 *
	 * @param array<string,mixed> $manifest Derivative manifest.
	 * @return array<string,bool>
	 */
	private function source_files( array $manifest ): array {
		$sources = array();
		$sizes   = $manifest['sizes'] ?? array();

		if ( ! is_array( $sizes ) ) {
			return $sources;
		}

		foreach ( $sizes as $size ) {
			if ( ! is_array( $size ) || ! isset( $size['source'] ) || ! is_array( $size['source'] ) ) {
				continue;
			}

			$file = $this->value_to_string( $size['source']['file'] ?? '' );

			if ( '' !== $file ) {
				$sources[ $this->normalize_relative_path( $file ) ] = true;
			}
		}

		return $sources;
	}

	/**
	 * Extract derivative files from derivative metadata.
	 *
	 * @param array<string,mixed> $manifest Derivative manifest.
	 * @return string[]
	 */
	private function derivative_files( array $manifest ): array {
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

				$file = $this->value_to_string( $format['file'] ?? '' );

				if ( '' !== $file ) {
					$files[] = $file;
				}
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * Determine whether a relative path is safe.
	 *
	 * @param string $relative_file Relative file.
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
	 * Determine whether a candidate path is inside uploads.
	 *
	 * @param string $candidate_realpath Normalized candidate real path.
	 * @param string $base_realpath Normalized uploads base real path.
	 * @return bool
	 */
	private function is_within_base( string $candidate_realpath, string $base_realpath ): bool {
		if ( '' === $candidate_realpath || $candidate_realpath === $base_realpath ) {
			return false;
		}

		return 0 === strpos( $candidate_realpath, rtrim( $base_realpath, '/' ) . '/' );
	}

	/**
	 * Normalize an absolute path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return str_replace( '\\', '/', $path );
	}

	/**
	 * Normalize a relative metadata path.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		return ltrim( $this->normalize_path( trim( $path ) ), '/' );
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
