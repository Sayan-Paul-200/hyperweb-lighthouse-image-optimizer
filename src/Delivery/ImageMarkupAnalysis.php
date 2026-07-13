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
	 * Original sizes attribute value, if present.
	 *
	 * @var string|null
	 */
	private $sizes;

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
	 * @param string|null $sizes Sizes attribute value.
	 * @param string|null $loading Loading attribute value.
	 * @param string|null $fetchpriority Fetch priority attribute value.
	 * @param string|null $decoding Decoding attribute value.
	 */
	public function __construct(
		bool $renderable_img,
		bool $already_picture,
		?string $sizes = null,
		?string $loading = null,
		?string $fetchpriority = null,
		?string $decoding = null
	) {
		$this->renderable_img  = $renderable_img;
		$this->already_picture = $already_picture;
		$this->sizes           = null === $sizes ? null : trim( $sizes );
		$this->loading         = $this->normalize_optional_attribute( $loading );
		$this->fetchpriority   = $this->normalize_optional_attribute( $fetchpriority );
		$this->decoding        = $this->normalize_optional_attribute( $decoding );
	}

	/**
	 * Build a renderable image result.
	 *
	 * @param string|null $sizes Sizes attribute value.
	 * @param string|null $loading Loading attribute value.
	 * @param string|null $fetchpriority Fetch priority attribute value.
	 * @param string|null $decoding Decoding attribute value.
	 * @return self
	 */
	public static function renderable(
		?string $sizes = null,
		?string $loading = null,
		?string $fetchpriority = null,
		?string $decoding = null
	): self {
		return new self( true, false, $sizes, $loading, $fetchpriority, $decoding );
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
			'sizes'           => $this->sizes,
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
}
