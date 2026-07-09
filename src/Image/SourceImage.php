<?php
/**
 * Source image value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents one normalized attachment source image candidate.
 */
final class SourceImage {

	public const ROLE_FULL     = 'full';
	public const ROLE_SUBSIZE  = 'subsize';
	public const ROLE_ORIGINAL = 'original';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * WordPress image size name.
	 *
	 * @var string
	 */
	private $size_name;

	/**
	 * Source role.
	 *
	 * @var string
	 */
	private $role;

	/**
	 * Uploads-relative source path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Absolute source path for internal processing.
	 *
	 * @var string
	 */
	private $absolute_path;

	/**
	 * Detected MIME type.
	 *
	 * @var string|null
	 */
	private $mime_type;

	/**
	 * Source width.
	 *
	 * @var int
	 */
	private $width;

	/**
	 * Source height.
	 *
	 * @var int
	 */
	private $height;

	/**
	 * Source byte size.
	 *
	 * @var int
	 */
	private $bytes;

	/**
	 * Source modified time.
	 *
	 * @var int
	 */
	private $modified_time;

	/**
	 * Create source image.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string      $size_name WordPress size name.
	 * @param string      $role Source role.
	 * @param string      $relative_path Uploads-relative source path.
	 * @param string      $absolute_path Absolute source path.
	 * @param string|null $mime_type Detected MIME type.
	 * @param int         $width Width.
	 * @param int         $height Height.
	 * @param int         $bytes Bytes.
	 * @param int         $modified_time Modified time.
	 */
	public function __construct(
		int $attachment_id,
		string $size_name,
		string $role,
		string $relative_path,
		string $absolute_path,
		?string $mime_type,
		int $width,
		int $height,
		int $bytes,
		int $modified_time
	) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->size_name     = '' === trim( $size_name ) ? 'unknown' : trim( $size_name );
		$this->role          = in_array( $role, self::roles(), true ) ? $role : self::ROLE_SUBSIZE;
		$this->relative_path = $this->normalize_relative_path( $relative_path );
		$this->absolute_path = $this->normalize_path( $absolute_path );
		$this->mime_type     = null === $mime_type || '' === trim( $mime_type ) ? null : strtolower( trim( $mime_type ) );
		$this->width         = max( 1, $width );
		$this->height        = max( 1, $height );
		$this->bytes         = max( 0, $bytes );
		$this->modified_time = max( 0, $modified_time );
	}

	/**
	 * Get valid roles.
	 *
	 * @return string[]
	 */
	public static function roles(): array {
		return array(
			self::ROLE_FULL,
			self::ROLE_SUBSIZE,
			self::ROLE_ORIGINAL,
		);
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get size name.
	 *
	 * @return string
	 */
	public function size_name(): string {
		return $this->size_name;
	}

	/**
	 * Get role.
	 *
	 * @return string
	 */
	public function role(): string {
		return $this->role;
	}

	/**
	 * Get relative path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Get absolute path.
	 *
	 * @return string
	 */
	public function absolute_path(): string {
		return $this->absolute_path;
	}

	/**
	 * Get MIME type.
	 *
	 * @return string|null
	 */
	public function mime_type(): ?string {
		return $this->mime_type;
	}

	/**
	 * Get width.
	 *
	 * @return int
	 */
	public function width(): int {
		return $this->width;
	}

	/**
	 * Get height.
	 *
	 * @return int
	 */
	public function height(): int {
		return $this->height;
	}

	/**
	 * Get bytes.
	 *
	 * @return int
	 */
	public function bytes(): int {
		return $this->bytes;
	}

	/**
	 * Get modified time.
	 *
	 * @return int
	 */
	public function modified_time(): int {
		return $this->modified_time;
	}

	/**
	 * Serialize without exposing absolute paths.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'size_name'     => $this->size_name,
			'role'          => $this->role,
			'relative_path' => $this->relative_path,
			'mime_type'     => $this->mime_type,
			'width'         => $this->width,
			'height'        => $this->height,
			'bytes'         => $this->bytes,
			'modified_time' => $this->modified_time,
		);
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
