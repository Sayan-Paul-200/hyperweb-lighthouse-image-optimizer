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
	 * Create request.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @param int             $quality Quality.
	 * @param float           $minimum_savings_percent Minimum savings percent.
	 */
	public function __construct(
		SourceImage $source,
		DestinationPath $destination,
		int $quality,
		float $minimum_savings_percent
	) {
		$this->source                  = $source;
		$this->destination             = $destination;
		$this->quality                 = max( 1, min( 100, $quality ) );
		$this->minimum_savings_percent = max( 0.0, min( 100.0, $minimum_savings_percent ) );
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
}
