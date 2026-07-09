<?php
/**
 * Fake diagnostic filesystem.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticFilesystemInterface;

/**
 * In-memory diagnostic filesystem for unit tests.
 */
final class FakeDiagnosticFilesystem implements DiagnosticFilesystemInterface {

	/**
	 * File contents by normalized path.
	 *
	 * @var array<string,string>
	 */
	public $files = array();

	/**
	 * Directories by normalized path.
	 *
	 * @var array<string,bool>
	 */
	public $directories = array();

	/**
	 * Written paths.
	 *
	 * @var string[]
	 */
	public $written = array();

	/**
	 * Rename operations.
	 *
	 * @var array<int,array{source:string,destination:string}>
	 */
	public $renamed = array();

	/**
	 * Deleted paths.
	 *
	 * @var string[]
	 */
	public $deleted = array();

	/**
	 * Whether writes should fail.
	 *
	 * @var bool
	 */
	public $write_fails = false;

	/**
	 * Whether renames should fail.
	 *
	 * @var bool
	 */
	public $rename_fails = false;

	/**
	 * Whether deletes should fail.
	 *
	 * @var bool
	 */
	public $delete_fails = false;

	/**
	 * Create fake filesystem.
	 *
	 * @param string[] $directories Directories.
	 */
	public function __construct( array $directories = array( '/tmp/uploads' ) ) {
		foreach ( $directories as $directory ) {
			$this->directories[ $this->normalize( $directory ) ] = true;
		}
	}

	/**
	 * Resolve real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$path = $this->normalize( $path );

		return $this->exists( $path ) ? $path : '';
	}

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		$path = $this->normalize( $path );

		return isset( $this->files[ $path ] ) || isset( $this->directories[ $path ] );
	}

	/**
	 * Determine whether a path is a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return isset( $this->files[ $this->normalize( $path ) ] );
	}

	/**
	 * Write a file.
	 *
	 * @param string $path Path.
	 * @param string $contents Contents.
	 * @return bool
	 */
	public function write( string $path, string $contents ): bool {
		$path            = $this->normalize( $path );
		$this->written[] = $path;

		if ( $this->write_fails ) {
			return false;
		}

		$this->files[ $path ] = $contents;

		return true;
	}

	/**
	 * Rename a file.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function rename( string $source, string $destination ): bool {
		$source          = $this->normalize( $source );
		$destination     = $this->normalize( $destination );
		$this->renamed[] = array(
			'source'      => $source,
			'destination' => $destination,
		);

		if ( $this->rename_fails || ! isset( $this->files[ $source ] ) ) {
			return false;
		}

		$this->files[ $destination ] = $this->files[ $source ];
		unset( $this->files[ $source ] );

		return true;
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete( string $path ): bool {
		$path            = $this->normalize( $path );
		$this->deleted[] = $path;

		if ( $this->delete_fails ) {
			return false;
		}

		unset( $this->files[ $path ] );

		return true;
	}

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int {
		$path = $this->normalize( $path );

		return isset( $this->files[ $path ] ) ? strlen( $this->files[ $path ] ) : null;
	}

	/**
	 * Normalize path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize( string $path ): string {
		return str_replace( '\\', '/', $path );
	}
}
