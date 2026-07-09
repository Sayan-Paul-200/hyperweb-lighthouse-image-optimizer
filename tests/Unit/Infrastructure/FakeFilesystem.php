<?php
/**
 * Fake filesystem.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\FilesystemInterface;

/**
 * Provides in-memory file operations.
 */
final class FakeFilesystem implements FilesystemInterface {

	/**
	 * Existing regular files.
	 *
	 * @var array<string,bool>
	 */
	public $files = array();

	/**
	 * Existing directories.
	 *
	 * @var array<string,bool>
	 */
	public $directories = array();

	/**
	 * Existing symlinks.
	 *
	 * @var array<string,bool>
	 */
	public $links = array();

	/**
	 * Deleted paths.
	 *
	 * @var string[]
	 */
	public $deleted = array();

	/**
	 * Create the filesystem.
	 *
	 * @param string[] $files Files.
	 * @param string[] $directories Directories.
	 * @param string[] $links Symlinks.
	 */
	public function __construct( array $files = array(), array $directories = array(), array $links = array() ) {
		foreach ( $files as $file ) {
			$this->files[ $this->normalize( $file ) ] = true;
		}

		foreach ( $directories as $directory ) {
			$this->directories[ $this->normalize( $directory ) ] = true;
		}

		foreach ( $links as $link ) {
			$this->links[ $this->normalize( $link ) ] = true;
		}
	}

	/**
	 * Determine whether a path exists.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		$path = $this->normalize( $path );

		return isset( $this->files[ $path ] ) || isset( $this->directories[ $path ] ) || isset( $this->links[ $path ] );
	}

	/**
	 * Determine whether a path is a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		return isset( $this->files[ $this->normalize( $path ) ] );
	}

	/**
	 * Determine whether a path is a symbolic link.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_link( string $path ): bool {
		return isset( $this->links[ $this->normalize( $path ) ] );
	}

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Absolute path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$path = $this->normalize( $path );

		return $this->exists( $path ) ? $path : '';
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function delete( string $path ): bool {
		$path = $this->normalize( $path );

		if ( ! isset( $this->files[ $path ] ) ) {
			return false;
		}

		unset( $this->files[ $path ] );
		$this->deleted[] = $path;

		return true;
	}

	/**
	 * Normalize paths for tests.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize( string $path ): string {
		return str_replace( '\\', '/', $path );
	}
}
