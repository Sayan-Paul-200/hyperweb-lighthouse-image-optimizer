<?php
/**
 * Fake conversion filesystem.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionFilesystemInterface;

/**
 * Provides in-memory filesystem facts for converter tests.
 */
final class FakeConversionFilesystem implements ConversionFilesystemInterface {

	/**
	 * Files keyed by normalized path.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	public $files = array();

	/**
	 * Deleted paths.
	 *
	 * @var string[]
	 */
	public $deleted = array();

	/**
	 * Move operations.
	 *
	 * @var array<int,array{source:string,destination:string}>
	 */
	public $moves = array();

	/**
	 * Paths where deletion fails.
	 *
	 * @var array<string,bool>
	 */
	public $delete_failures = array();

	/**
	 * Whether move should fail.
	 *
	 * @var bool
	 */
	public $move_should_fail = false;

	/**
	 * Add a file.
	 *
	 * @param string      $path Path.
	 * @param int         $bytes Bytes.
	 * @param string|null $mime_type MIME type.
	 * @param int|null    $width Width.
	 * @param int|null    $height Height.
	 * @param bool        $readable Whether readable.
	 * @param bool        $is_file Whether regular file.
	 * @param string|null $realpath Real path.
	 * @return void
	 */
	public function add_file(
		string $path,
		int $bytes = 1000,
		?string $mime_type = 'image/jpeg',
		?int $width = 100,
		?int $height = 100,
		bool $readable = true,
		bool $is_file = true,
		?string $realpath = null
	): void {
		$path                 = $this->normalize( $path );
		$this->files[ $path ] = array(
			'bytes'     => $bytes,
			'mime_type' => $mime_type,
			'width'     => $width,
			'height'    => $height,
			'readable'  => $readable,
			'is_file'   => $is_file,
			'realpath'  => null === $realpath ? $path : $this->normalize( $realpath ),
		);
	}

	/**
	 * Make deletion fail for path.
	 *
	 * @param string $path Path.
	 * @return void
	 */
	public function fail_delete_for( string $path ): void {
		$this->delete_failures[ $this->normalize( $path ) ] = true;
	}

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$path = $this->normalize( $path );

		if ( ! isset( $this->files[ $path ] ) ) {
			return '';
		}

		return is_string( $this->files[ $path ]['realpath'] ) ? $this->files[ $path ]['realpath'] : '';
	}

	/**
	 * Determine whether path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return isset( $this->files[ $this->normalize( $path ) ] );
	}

	/**
	 * Determine whether path is a regular file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_file( string $path ): bool {
		$file = $this->file_for_path( $path );

		return null !== $file && true === $file['is_file'];
	}

	/**
	 * Determine whether path is readable.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_readable( string $path ): bool {
		$file = $this->file_for_path( $path );

		return null !== $file && true === $file['readable'];
	}

	/**
	 * Get file size.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function file_size( string $path ): ?int {
		$file = $this->file_for_path( $path );

		return null !== $file && is_int( $file['bytes'] ) ? $file['bytes'] : null;
	}

	/**
	 * Detect MIME type.
	 *
	 * @param string $path Path.
	 * @return string|null
	 */
	public function mime_type( string $path ): ?string {
		$file = $this->file_for_path( $path );

		return null !== $file && is_string( $file['mime_type'] ) ? $file['mime_type'] : null;
	}

	/**
	 * Read image dimensions.
	 *
	 * @param string $path Path.
	 * @return array{width:int,height:int}|null
	 */
	public function dimensions( string $path ): ?array {
		$file = $this->file_for_path( $path );

		if ( null === $file || ! is_int( $file['width'] ) || ! is_int( $file['height'] ) ) {
			return null;
		}

		return array(
			'width'  => $file['width'],
			'height' => $file['height'],
		);
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

		if ( isset( $this->delete_failures[ $path ] ) ) {
			return false;
		}

		unset( $this->files[ $path ] );

		return true;
	}

	/**
	 * Move a file into place.
	 *
	 * @param string $source Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool {
		$source      = $this->normalize( $source );
		$destination = $this->normalize( $destination );

		$this->moves[] = array(
			'source'      => $source,
			'destination' => $destination,
		);

		if ( $this->move_should_fail || ! isset( $this->files[ $source ] ) || isset( $this->files[ $destination ] ) ) {
			return false;
		}

		$this->files[ $destination ]             = $this->files[ $source ];
		$this->files[ $destination ]['realpath'] = $destination;
		unset( $this->files[ $source ] );

		return true;
	}

	/**
	 * Get file by path.
	 *
	 * @param string $path Path.
	 * @return array<string,mixed>|null
	 */
	private function file_for_path( string $path ): ?array {
		$path = $this->normalize( $path );

		return isset( $this->files[ $path ] ) ? $this->files[ $path ] : null;
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
