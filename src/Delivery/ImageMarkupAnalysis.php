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
	 * Create analysis.
	 *
	 * @param bool        $renderable_img Whether the fragment is renderable.
	 * @param bool        $already_picture Whether the fragment is already picture markup.
	 * @param string|null $sizes Sizes attribute value.
	 */
	public function __construct( bool $renderable_img, bool $already_picture, ?string $sizes = null ) {
		$this->renderable_img  = $renderable_img;
		$this->already_picture = $already_picture;
		$this->sizes           = null === $sizes ? null : trim( $sizes );
	}

	/**
	 * Build a renderable image result.
	 *
	 * @param string|null $sizes Sizes attribute value.
	 * @return self
	 */
	public static function renderable( ?string $sizes = null ): self {
		return new self( true, false, $sizes );
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
	 * Serialize analysis.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'renderable_img'  => $this->renderable_img,
			'already_picture' => $this->already_picture,
			'sizes'           => $this->sizes,
		);
	}
}
