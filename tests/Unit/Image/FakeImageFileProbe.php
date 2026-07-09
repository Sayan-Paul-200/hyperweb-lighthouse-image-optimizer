<?php
/**
 * Fake image file probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;

/**
 * Provides in-memory file facts for source collector tests.
 */
final class FakeImageFileProbe implements ImageFileProbeInterface {

	/**
	 * Files keyed by normalized path.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	public $files = array();

	/**
	 * Directories keyed by normalized path.
	 *
	 * @var array<string,bool>
	 */
	public $directories = array();

	/**
	 * Create fake probe.
	 *
	 * @param string[] $directories Directories.
	 */
	public function __construct( array $directories = array( 'C:/site/wp-content/uploads' ) ) {
		foreach ( $directories as $directory ) {
			$this->directories[ $this->normalize( $directory ) ] = true;
		}
	}

	/**
	 * Add a file.
	 *
	 * @param string      $path Path.
	 * @param int         $bytes Bytes.
	 * @param int         $modified_time Modified time.
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
		int $modified_time = 1783526400,
		?string $mime_type = 'image/jpeg',
		?int $width = 100,
		?int $height = 100,
		bool $readable = true,
		bool $is_file = true,
		?string $realpath = null
	): void {
		$path                 = $this->normalize( $path );
		$this->files[ $path ] = array(
			'bytes'         => $bytes,
			'modified_time' => $modified_time,
			'mime_type'     => $mime_type,
			'width'         => $width,
			'height'        => $height,
			'readable'      => $readable,
			'is_file'       => $is_file,
			'realpath'      => null === $realpath ? $path : $this->normalize( $realpath ),
		);
	}

	/**
	 * Resolve a real path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public function realpath( string $path ): string {
		$path = $this->normalize( $path );

		if ( isset( $this->directories[ $path ] ) ) {
			return $path;
		}

		if ( isset( $this->files[ $path ] ) ) {
			return is_string( $this->files[ $path ]['realpath'] ) ? $this->files[ $path ]['realpath'] : '';
		}

		return '';
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
		$file = $this->file_for_path( $path );

		return null !== $file && true === $file['is_file'];
	}

	/**
	 * Determine whether a path is readable.
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
	 * Get modified time.
	 *
	 * @param string $path Path.
	 * @return int|null
	 */
	public function modified_time( string $path ): ?int {
		$file = $this->file_for_path( $path );

		return null !== $file && is_int( $file['modified_time'] ) ? $file['modified_time'] : null;
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
	 * Get file by original or real path.
	 *
	 * @param string $path Path.
	 * @return array<string,mixed>|null
	 */
	private function file_for_path( string $path ): ?array {
		$path = $this->normalize( $path );

		if ( isset( $this->files[ $path ] ) ) {
			return $this->files[ $path ];
		}

		foreach ( $this->files as $file ) {
			if ( isset( $file['realpath'] ) && $path === $file['realpath'] ) {
				return $file;
			}
		}

		return null;
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
