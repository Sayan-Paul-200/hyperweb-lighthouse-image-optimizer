<?php
/**
 * Derivative URL resolver request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries typed context for one derivative URL resolution.
 */
final class DerivativeUrlRequest {

	/**
	 * Relative path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Attachment ID.
	 *
	 * @var int|null
	 */
	private $attachment_id;

	/**
	 * Size name.
	 *
	 * @var string|null
	 */
	private $size_name;

	/**
	 * Format.
	 *
	 * @var string|null
	 */
	private $format;

	/**
	 * Create request.
	 *
	 * @param string      $relative_path Relative path.
	 * @param int|null    $attachment_id Attachment ID.
	 * @param string|null $size_name Size name.
	 * @param string|null $format Format.
	 */
	public function __construct(
		string $relative_path,
		?int $attachment_id = null,
		?string $size_name = null,
		?string $format = null
	) {
		$this->relative_path = trim( $relative_path );
		$this->attachment_id = null !== $attachment_id && $attachment_id > 0
			? $attachment_id
			: null;
		$this->size_name     = $this->normalize_optional_string( $size_name );
		$this->format        = $this->normalize_optional_string( $format, true );
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
	 * Get attachment ID.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get size name.
	 *
	 * @return string|null
	 */
	public function size_name(): ?string {
		return $this->size_name;
	}

	/**
	 * Get format.
	 *
	 * @return string|null
	 */
	public function format(): ?string {
		return $this->format;
	}

	/**
	 * Return a copy with a normalized relative path.
	 *
	 * @param string $relative_path Relative path.
	 * @return self
	 */
	public function with_relative_path( string $relative_path ): self {
		return new self(
			$relative_path,
			$this->attachment_id,
			$this->size_name,
			$this->format
		);
	}

	/**
	 * Serialize request.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'relative_path' => $this->relative_path,
			'attachment_id' => $this->attachment_id,
			'size_name'     => $this->size_name,
			'format'        => $this->format,
		);
	}

	/**
	 * Normalize an optional string.
	 *
	 * @param string|null $value Value.
	 * @param bool        $lowercase Whether to lowercase.
	 * @return string|null
	 */
	private function normalize_optional_string( ?string $value, bool $lowercase = false ): ?string {
		if ( null === $value ) {
			return null;
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return null;
		}

		if ( $lowercase ) {
			$value = strtolower( $value );
		}

		return substr( $value, 0, 64 );
	}
}
