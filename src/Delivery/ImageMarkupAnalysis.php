<?php
/**
 * Image markup analysis result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries conservative facts about one image fragment.
 */
final class ImageMarkupAnalysis {

	/**
	 * Whether the fragment is a renderable standalone image.
	 *
	 * @var bool
	 */
	private $renderable_img;

	/**
	 * Whether the fragment is already picture markup.
	 *
	 * @var bool
	 */
	private $already_picture;

	/**
	 * Original src attribute value, if present.
	 *
	 * @var string|null
	 */
	private $src;

	/**
	 * Original sizes attribute value, if present.
	 *
	 * @var string|null
	 */
	private $sizes;

	/**
	 * Whether a width attribute is present.
	 *
	 * @var bool
	 */
	private $has_width_attribute;

	/**
	 * Whether a height attribute is present.
	 *
	 * @var bool
	 */
	private $has_height_attribute;

	/**
	 * Original width attribute value as a positive integer when valid.
	 *
	 * @var int|null
	 */
	private $width;

	/**
	 * Original height attribute value as a positive integer when valid.
	 *
	 * @var int|null
	 */
	private $height;

	/**
	 * Original loading attribute value, normalized when present.
	 *
	 * @var string|null
	 */
	private $loading;

	/**
	 * Original fetchpriority attribute value, normalized when present.
	 *
	 * @var string|null
	 */
	private $fetchpriority;

	/**
	 * Original decoding attribute value, normalized when present.
	 *
	 * @var string|null
	 */
	private $decoding;

	/**
	 * Create analysis.
	 *
	 * @param bool        $renderable_img Whether the fragment is renderable.
	 * @param bool        $already_picture Whether the fragment is already picture markup.
	 * @param string|null $src Src attribute value.
	 * @param string|null $sizes Sizes attribute value.
	 * @param bool        $has_width_attribute Whether a width attribute exists.
	 * @param bool        $has_height_attribute Whether a height attribute exists.
	 * @param int|null    $width Width attribute value when valid.
	 * @param int|null    $height Height attribute value when valid.
	 * @param string|null $loading Loading attribute value.
	 * @param string|null $fetchpriority Fetch priority attribute value.
	 * @param string|null $decoding Decoding attribute value.
	 */
	public function __construct(
		bool $renderable_img,
		bool $already_picture,
		?string $src = null,
		?string $sizes = null,
		bool $has_width_attribute = false,
		bool $has_height_attribute = false,
		?int $width = null,
		?int $height = null,
		?string $loading = null,
		?string $fetchpriority = null,
		?string $decoding = null
	) {
		$this->renderable_img       = $renderable_img;
		$this->already_picture      = $already_picture;
		$this->src                  = null === $src ? null : trim( $src );
		$this->sizes                = null === $sizes ? null : trim( $sizes );
		$this->has_width_attribute  = $has_width_attribute;
		$this->has_height_attribute = $has_height_attribute;
		$this->width                = $this->normalize_positive_int( $width );
		$this->height               = $this->normalize_positive_int( $height );
		$this->loading              = $this->normalize_optional_attribute( $loading );
		$this->fetchpriority        = $this->normalize_optional_attribute( $fetchpriority );
		$this->decoding             = $this->normalize_optional_attribute( $decoding );
	}

	/**
	 * Build a renderable image result.
	 *
	 * @param string|null $src Src attribute value.
	 * @param string|null $sizes Sizes attribute value.
	 * @param bool        $has_width_attribute Whether a width attribute exists.
	 * @param bool        $has_height_attribute Whether a height attribute exists.
	 * @param int|null    $width Width attribute value when valid.
	 * @param int|null    $height Height attribute value when valid.
	 * @param string|null $loading Loading attribute value.
	 * @param string|null $fetchpriority Fetch priority attribute value.
	 * @param string|null $decoding Decoding attribute value.
	 * @return self
	 */
	public static function renderable(
		?string $src = null,
		?string $sizes = null,
		bool $has_width_attribute = false,
		bool $has_height_attribute = false,
		?int $width = null,
		?int $height = null,
		?string $loading = null,
		?string $fetchpriority = null,
		?string $decoding = null
	): self {
		return new self(
			true,
			false,
			$src,
			$sizes,
			$has_width_attribute,
			$has_height_attribute,
			$width,
			$height,
			$loading,
			$fetchpriority,
			$decoding
		);
	}

	/**
	 * Build an already-picture result.
	 *
	 * @return self
	 */
	public static function already_picture(): self {
		return new self( false, true );
	}

	/**
	 * Build an invalid result.
	 *
	 * @return self
	 */
	public static function invalid(): self {
		return new self( false, false );
	}

	/**
	 * Whether the fragment is renderable.
	 *
	 * @return bool
	 */
	public function is_renderable_img(): bool {
		return $this->renderable_img;
	}

	/**
	 * Whether the fragment is already picture markup.
	 *
	 * @return bool
	 */
	public function is_picture(): bool {
		return $this->already_picture;
	}

	/**
	 * Get sizes attribute value.
	 *
	 * @return string|null
	 */
	public function sizes(): ?string {
		return $this->sizes;
	}

	/**
	 * Get src attribute value.
	 *
	 * @return string|null
	 */
	public function src(): ?string {
		return $this->src;
	}

	/**
	 * Whether a width attribute is present.
	 *
	 * @return bool
	 */
	public function has_width_attribute(): bool {
		return $this->has_width_attribute;
	}

	/**
	 * Whether a height attribute is present.
	 *
	 * @return bool
	 */
	public function has_height_attribute(): bool {
		return $this->has_height_attribute;
	}

	/**
	 * Get width when valid.
	 *
	 * @return int|null
	 */
	public function width(): ?int {
		return $this->width;
	}

	/**
	 * Get height when valid.
	 *
	 * @return int|null
	 */
	public function height(): ?int {
		return $this->height;
	}

	/**
	 * Whether a valid width value exists.
	 *
	 * @return bool
	 */
	public function has_valid_width(): bool {
		return null !== $this->width;
	}

	/**
	 * Whether a valid height value exists.
	 *
	 * @return bool
	 */
	public function has_valid_height(): bool {
		return null !== $this->height;
	}

	/**
	 * Get normalized loading attribute value.
	 *
	 * @return string|null
	 */
	public function loading(): ?string {
		return $this->loading;
	}

	/**
	 * Get normalized fetchpriority attribute value.
	 *
	 * @return string|null
	 */
	public function fetchpriority(): ?string {
		return $this->fetchpriority;
	}

	/**
	 * Get normalized decoding attribute value.
	 *
	 * @return string|null
	 */
	public function decoding(): ?string {
		return $this->decoding;
	}

	/**
	 * Whether the fallback image carries the conflicting lazy/high combination.
	 *
	 * @return bool
	 */
	public function has_loading_priority_conflict(): bool {
		return 'lazy' === $this->loading && 'high' === $this->fetchpriority;
	}

	/**
	 * Serialize analysis.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'renderable_img'  => $this->renderable_img,
			'already_picture' => $this->already_picture,
			'src'             => $this->src,
			'sizes'           => $this->sizes,
			'has_width'       => $this->has_width_attribute,
			'has_height'      => $this->has_height_attribute,
			'width'           => $this->width,
			'height'          => $this->height,
			'loading'         => $this->loading,
			'fetchpriority'   => $this->fetchpriority,
			'decoding'        => $this->decoding,
			'has_conflict'    => $this->has_loading_priority_conflict(),
		);
	}

	/**
	 * Normalize one optional attribute value for conservative comparisons.
	 *
	 * @param string|null $value Attribute value.
	 * @return string|null
	 */
	private function normalize_optional_attribute( ?string $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		$value = strtolower( trim( $value ) );

		return '' === $value ? null : $value;
	}

	/**
	 * Normalize one optional positive integer.
	 *
	 * @param int|null $value Value.
	 * @return int|null
	 */
	private function normalize_positive_int( ?int $value ): ?int {
		if ( null === $value || $value < 1 ) {
			return null;
		}

		return $value;
	}
}
