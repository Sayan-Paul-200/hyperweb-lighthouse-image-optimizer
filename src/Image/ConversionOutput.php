<?php
/**
 * Conversion output metadata.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents validated final derivative facts.
 */
final class ConversionOutput {

	/**
	 * Uploads-relative derivative path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Output MIME type.
	 *
	 * @var string
	 */
	private $mime_type;

	/**
	 * Output width.
	 *
	 * @var int
	 */
	private $width;

	/**
	 * Output height.
	 *
	 * @var int
	 */
	private $height;

	/**
	 * Output byte size.
	 *
	 * @var int
	 */
	private $bytes;

	/**
	 * Quality used.
	 *
	 * @var int
	 */
	private $quality;

	/**
	 * Generated timestamp.
	 *
	 * @var int
	 */
	private $generated_at;

	/**
	 * Create output metadata.
	 *
	 * @param string $relative_path Uploads-relative path.
	 * @param string $mime_type MIME type.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @param int    $bytes Bytes.
	 * @param int    $quality Quality.
	 * @param int    $generated_at Generated timestamp.
	 */
	public function __construct(
		string $relative_path,
		string $mime_type,
		int $width,
		int $height,
		int $bytes,
		int $quality,
		int $generated_at
	) {
		$this->relative_path = $this->normalize_relative_path( $relative_path );
		$this->mime_type     = '' === trim( $mime_type ) ? 'application/octet-stream' : strtolower( trim( $mime_type ) );
		$this->width         = max( 1, $width );
		$this->height        = max( 1, $height );
		$this->bytes         = max( 0, $bytes );
		$this->quality       = max( 1, min( 100, $quality ) );
		$this->generated_at  = max( 0, $generated_at );
	}

	/**
	 * Get uploads-relative path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Get MIME type.
	 *
	 * @return string
	 */
	public function mime_type(): string {
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
	 * Get quality.
	 *
	 * @return int
	 */
	public function quality(): int {
		return $this->quality;
	}

	/**
	 * Get generated timestamp.
	 *
	 * @return int
	 */
	public function generated_at(): int {
		return $this->generated_at;
	}

	/**
	 * Serialize output metadata.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'relative_path' => $this->relative_path,
			'mime_type'     => $this->mime_type,
			'width'         => $this->width,
			'height'        => $this->height,
			'bytes'         => $this->bytes,
			'quality'       => $this->quality,
			'generated_at'  => $this->generated_at,
		);
	}

	/**
	 * Normalize a relative path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		return ltrim( str_replace( '\\', '/', trim( $path ) ), '/' );
	}
}
