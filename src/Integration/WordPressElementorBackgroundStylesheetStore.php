<?php
/**
 * WordPress-backed Elementor companion stylesheet store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Stores plugin-owned Elementor companion CSS safely inside uploads.
 */
final class WordPressElementorBackgroundStylesheetStore implements ElementorBackgroundStylesheetStoreInterface {

	/**
	 * Plugin-owned uploads subdirectory.
	 *
	 * @var string
	 */
	private const RELATIVE_DIRECTORY = 'hwlio/elementor-background-css';

	/**
	 * Optional uploads-data provider.
	 *
	 * @var callable|null
	 */
	private $uploads_provider;

	/**
	 * Create store.
	 *
	 * @param callable|null $uploads_provider Optional uploads-data provider for tests.
	 */
	public function __construct( ?callable $uploads_provider = null ) {
		$this->uploads_provider = $uploads_provider;
	}

	/**
	 * Get the uploads-relative stylesheet path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function relative_path( int $document_id ): ?string {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return null;
		}

		return self::RELATIVE_DIRECTORY . '/' . $document_id . '.hwlio-backgrounds.css';
	}

	/**
	 * Get the public stylesheet URL.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function url( int $document_id ): ?string {
		$uploads       = $this->uploads();
		$relative_path = $this->relative_path( $document_id );

		if ( null === $relative_path || ! isset( $uploads['baseurl'] ) || ! is_string( $uploads['baseurl'] ) ) {
			return null;
		}

		$base_url = trim( $uploads['baseurl'] );

		if ( '' === $base_url ) {
			return null;
		}

		return rtrim( $base_url, '/' ) . '/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Whether the stylesheet exists.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function exists( int $document_id ): bool {
		$path = $this->absolute_path( $document_id );

		return null !== $path && file_exists( $path );
	}

	/**
	 * Read one stylesheet.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function read( int $document_id ): ?string {
		$path = $this->absolute_path( $document_id );

		if ( null === $path || ! file_exists( $path ) || ! is_file( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading plugin-owned stylesheet artifact from a validated uploads path.
		$contents = file_get_contents( $path );

		return is_string( $contents ) ? $contents : null;
	}

	/**
	 * Write one stylesheet safely.
	 *
	 * @param int    $document_id Document ID.
	 * @param string $contents Stylesheet contents.
	 * @return bool
	 */
	public function write( int $document_id, string $contents ): bool {
		$path        = $this->absolute_path( $document_id );
		$temp_path   = $this->temp_path( $document_id );
		$backup_path = $this->backup_path( $document_id );

		if ( null === $path || null === $temp_path || null === $backup_path ) {
			return false;
		}

		$directory = dirname( $path );

		if ( ! is_dir( $directory ) && ! $this->create_directory( $directory ) ) {
			return false;
		}

		if ( file_exists( $temp_path ) && ! $this->delete_file( $temp_path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing plugin-owned stylesheet artifact to a validated uploads path.
		$written = file_put_contents( $temp_path, $contents );

		if ( false === $written ) {
			$this->delete_file( $temp_path );

			return false;
		}

		if ( file_exists( $backup_path ) && ! $this->delete_file( $backup_path ) ) {
			$this->delete_file( $temp_path );

			return false;
		}

		$had_existing = file_exists( $path );

		if ( $had_existing && ! $this->rename_file( $path, $backup_path ) ) {
			$this->delete_file( $temp_path );

			return false;
		}

		if ( ! $this->rename_file( $temp_path, $path ) ) {
			$this->delete_file( $temp_path );

			if ( $had_existing && file_exists( $backup_path ) ) {
				$this->rename_file( $backup_path, $path );
			}

			return false;
		}

		if ( $had_existing && file_exists( $backup_path ) && ! $this->delete_file( $backup_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete one stylesheet safely.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function delete( int $document_id ): bool {
		$path        = $this->absolute_path( $document_id );
		$temp_path   = $this->temp_path( $document_id );
		$backup_path = $this->backup_path( $document_id );

		if ( null === $path || null === $temp_path || null === $backup_path ) {
			return false;
		}

		$ok = true;

		foreach ( array( $path, $temp_path, $backup_path ) as $candidate ) {
			if ( file_exists( $candidate ) && ! $this->delete_file( $candidate ) ) {
				$ok = false;
			}
		}

		return $ok;
	}

	/**
	 * Get validated absolute stylesheet path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	private function absolute_path( int $document_id ): ?string {
		$uploads       = $this->uploads();
		$relative_path = $this->relative_path( $document_id );

		if ( null === $relative_path || ! isset( $uploads['basedir'] ) || ! is_string( $uploads['basedir'] ) ) {
			return null;
		}

		$base_dir = $this->normalize_directory( $uploads['basedir'] );

		if ( '' === $base_dir ) {
			return null;
		}

		$path = $base_dir . '/' . str_replace( '\\', '/', $relative_path );

		return $this->is_path_within_base( $path, $base_dir ) ? $path : null;
	}

	/**
	 * Get validated temporary path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	private function temp_path( int $document_id ): ?string {
		$path = $this->absolute_path( $document_id );

		return is_string( $path ) ? $path . '.tmp' : null;
	}

	/**
	 * Get validated backup path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	private function backup_path( int $document_id ): ?string {
		$path = $this->absolute_path( $document_id );

		return is_string( $path ) ? $path . '.bak' : null;
	}

	/**
	 * Read uploads data.
	 *
	 * @return array<string,mixed>
	 */
	private function uploads(): array {
		if ( null !== $this->uploads_provider ) {
			$uploads = call_user_func( $this->uploads_provider );

			return is_array( $uploads ) ? $uploads : array();
		}

		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return array();
		}

		$uploads = \wp_upload_dir( null, false );

		if ( ! is_array( $uploads ) ) {
			return array();
		}

		if ( is_string( $uploads['error'] ) && '' !== trim( $uploads['error'] ) ) {
			return array();
		}

		return $uploads;
	}

	/**
	 * Normalize a filesystem directory.
	 *
	 * @param string $path Raw path.
	 * @return string
	 */
	private function normalize_directory( string $path ): string {
		$path = str_replace( '\\', '/', trim( $path ) );

		return '' !== $path ? rtrim( $path, '/' ) : '';
	}

	/**
	 * Whether a candidate path stays inside the uploads base directory.
	 *
	 * @param string $path Candidate path.
	 * @param string $base_dir Base directory.
	 * @return bool
	 */
	private function is_path_within_base( string $path, string $base_dir ): bool {
		$path     = str_replace( '\\', '/', $path );
		$base_dir = str_replace( '\\', '/', $base_dir );

		return 0 === strpos( $path, $base_dir . '/' ) || $path === $base_dir;
	}

	/**
	 * Create a directory recursively.
	 *
	 * @param string $directory Directory path.
	 * @return bool
	 */
	private function create_directory( string $directory ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Creating plugin-owned uploads subdirectory after path validation.
		return mkdir( $directory, 0775, true ) || is_dir( $directory );
	}

	/**
	 * Rename one validated file.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	private function rename_file( string $source, string $destination ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Same-directory replace/rollback for plugin-owned stylesheet artifact.
		$renamed = rename( $source, $destination );
		clearstatcache( true, $source );
		clearstatcache( true, $destination );

		return $renamed;
	}

	/**
	 * Delete one validated file.
	 *
	 * @param string $path File path.
	 * @return bool
	 */
	private function delete_file( string $path ): bool {
		if ( ! file_exists( $path ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Deleting plugin-owned stylesheet artifact or temp file after path validation.
		$deleted = unlink( $path );
		clearstatcache( true, $path );

		return $deleted || ! file_exists( $path );
	}
}
