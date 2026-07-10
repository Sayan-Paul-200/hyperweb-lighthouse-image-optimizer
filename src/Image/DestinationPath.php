<?php
/**
 * Destination path value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents resolved final and temporary derivative paths.
 */
final class DestinationPath {

	/**
	 * Target format.
	 *
	 * @var string
	 */
	private $target_format;

	/**
	 * Target MIME type.
	 *
	 * @var string
	 */
	private $target_mime;

	/**
	 * Uploads-relative final path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Absolute final path.
	 *
	 * @var string
	 */
	private $absolute_path;

	/**
	 * Uploads-relative temporary path.
	 *
	 * @var string
	 */
	private $temporary_relative_path;

	/**
	 * Absolute temporary path.
	 *
	 * @var string
	 */
	private $temporary_absolute_path;

	/**
	 * Create destination path.
	 *
	 * @param string $target_format Target format.
	 * @param string $target_mime Target MIME type.
	 * @param string $relative_path Uploads-relative final path.
	 * @param string $absolute_path Absolute final path.
	 * @param string $temporary_relative_path Uploads-relative temporary path.
	 * @param string $temporary_absolute_path Absolute temporary path.
	 */
	public function __construct(
		string $target_format,
		string $target_mime,
		string $relative_path,
		string $absolute_path,
		string $temporary_relative_path,
		string $temporary_absolute_path
	) {
		$this->target_format           = strtolower( trim( $target_format ) );
		$this->target_mime             = strtolower( trim( $target_mime ) );
		$this->relative_path           = $this->normalize_relative_path( $relative_path );
		$this->absolute_path           = $this->normalize_path( $absolute_path );
		$this->temporary_relative_path = $this->normalize_relative_path( $temporary_relative_path );
		$this->temporary_absolute_path = $this->normalize_path( $temporary_absolute_path );
	}

	/**
	 * Get target format.
	 *
	 * @return string
	 */
	public function target_format(): string {
		return $this->target_format;
	}

	/**
	 * Get target MIME type.
	 *
	 * @return string
	 */
	public function target_mime(): string {
		return $this->target_mime;
	}

	/**
	 * Get uploads-relative final path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Get absolute final path.
	 *
	 * @return string
	 */
	public function absolute_path(): string {
		return $this->absolute_path;
	}

	/**
	 * Get uploads-relative temporary path.
	 *
	 * @return string
	 */
	public function temporary_relative_path(): string {
		return $this->temporary_relative_path;
	}

	/**
	 * Get absolute temporary path.
	 *
	 * @return string
	 */
	public function temporary_absolute_path(): string {
		return $this->temporary_absolute_path;
	}

	/**
	 * Serialize without exposing absolute paths.
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'target_format'           => $this->target_format,
			'target_mime'             => $this->target_mime,
			'relative_path'           => $this->relative_path,
			'temporary_relative_path' => $this->temporary_relative_path,
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
