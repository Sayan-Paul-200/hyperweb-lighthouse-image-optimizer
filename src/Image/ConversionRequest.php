<?php
/**
 * Conversion request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries the immutable inputs needed for one conversion attempt.
 */
final class ConversionRequest {

	/**
	 * Source image.
	 *
	 * @var SourceImage
	 */
	private $source;

	/**
	 * Destination path.
	 *
	 * @var DestinationPath
	 */
	private $destination;

	/**
	 * Conversion quality.
	 *
	 * @var int
	 */
	private $quality;

	/**
	 * Minimum savings percent.
	 *
	 * @var float
	 */
	private $minimum_savings_percent;

	/**
	 * Whether an existing plugin-owned destination may be replaced.
	 *
	 * @var bool
	 */
	private $replace_existing;

	/**
	 * Create request.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @param int             $quality Quality.
	 * @param float           $minimum_savings_percent Minimum savings percent.
	 * @param bool            $replace_existing Whether an existing destination may be replaced.
	 */
	public function __construct(
		SourceImage $source,
		DestinationPath $destination,
		int $quality,
		float $minimum_savings_percent,
		bool $replace_existing = false
	) {
		$this->source                  = $source;
		$this->destination             = $destination;
		$this->quality                 = max( 1, min( 100, $quality ) );
		$this->minimum_savings_percent = max( 0.0, min( 100.0, $minimum_savings_percent ) );
		$this->replace_existing        = $replace_existing;
	}

	/**
	 * Get source image.
	 *
	 * @return SourceImage
	 */
	public function source(): SourceImage {
		return $this->source;
	}

	/**
	 * Get destination path.
	 *
	 * @return DestinationPath
	 */
	public function destination(): DestinationPath {
		return $this->destination;
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
	 * Get minimum savings percent.
	 *
	 * @return float
	 */
	public function minimum_savings_percent(): float {
		return $this->minimum_savings_percent;
	}

	/**
	 * Whether an existing destination may be replaced.
	 *
	 * @return bool
	 */
	public function replace_existing(): bool {
		return $this->replace_existing;
	}
}
