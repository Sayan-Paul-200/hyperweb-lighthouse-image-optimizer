<?php
/**
 * Conversion savings value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries byte and percent savings for a conversion result.
 */
final class ConversionSavings {

	/**
	 * Source byte size.
	 *
	 * @var int
	 */
	private $source_bytes;

	/**
	 * Output byte size.
	 *
	 * @var int|null
	 */
	private $output_bytes;

	/**
	 * Savings in bytes.
	 *
	 * @var int|null
	 */
	private $savings_bytes;

	/**
	 * Savings percent.
	 *
	 * @var float|null
	 */
	private $savings_percent;

	/**
	 * Minimum required savings percent.
	 *
	 * @var float|null
	 */
	private $minimum_savings_percent;

	/**
	 * Whether the minimum savings threshold is met.
	 *
	 * @var bool|null
	 */
	private $meets_minimum;

	/**
	 * Create savings.
	 *
	 * @param int        $source_bytes Source bytes.
	 * @param int|null   $output_bytes Output bytes.
	 * @param float|null $minimum_savings_percent Minimum savings percent.
	 */
	public function __construct( int $source_bytes, ?int $output_bytes, ?float $minimum_savings_percent = null ) {
		$this->source_bytes            = max( 0, $source_bytes );
		$this->output_bytes            = null === $output_bytes ? null : max( 0, $output_bytes );
		$this->minimum_savings_percent = null === $minimum_savings_percent ? null : max( 0.0, min( 100.0, $minimum_savings_percent ) );
		$this->savings_bytes           = null === $this->output_bytes ? null : $this->source_bytes - $this->output_bytes;
		$this->savings_percent         = $this->calculate_percent();
		$this->meets_minimum           = $this->calculate_meets_minimum();
	}

	/**
	 * Build savings from source and output objects.
	 *
	 * @param SourceImage      $source Source image.
	 * @param ConversionOutput $output Output metadata.
	 * @param float|null       $minimum_savings_percent Minimum savings percent.
	 * @return self
	 */
	public static function from_source_and_output(
		SourceImage $source,
		ConversionOutput $output,
		?float $minimum_savings_percent = null
	): self {
		return new self( $source->bytes(), $output->bytes(), $minimum_savings_percent );
	}

	/**
	 * Get source bytes.
	 *
	 * @return int
	 */
	public function source_bytes(): int {
		return $this->source_bytes;
	}

	/**
	 * Get output bytes.
	 *
	 * @return int|null
	 */
	public function output_bytes(): ?int {
		return $this->output_bytes;
	}

	/**
	 * Get savings bytes.
	 *
	 * @return int|null
	 */
	public function savings_bytes(): ?int {
		return $this->savings_bytes;
	}

	/**
	 * Get savings percent.
	 *
	 * @return float|null
	 */
	public function savings_percent(): ?float {
		return $this->savings_percent;
	}

	/**
	 * Get minimum savings percent.
	 *
	 * @return float|null
	 */
	public function minimum_savings_percent(): ?float {
		return $this->minimum_savings_percent;
	}

	/**
	 * Whether minimum savings is met.
	 *
	 * @return bool|null
	 */
	public function meets_minimum(): ?bool {
		return $this->meets_minimum;
	}

	/**
	 * Serialize savings.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'source_bytes'            => $this->source_bytes,
			'output_bytes'            => $this->output_bytes,
			'savings_bytes'           => $this->savings_bytes,
			'savings_percent'         => $this->savings_percent,
			'minimum_savings_percent' => $this->minimum_savings_percent,
			'meets_minimum'           => $this->meets_minimum,
		);
	}

	/**
	 * Calculate savings percent.
	 *
	 * @return float|null
	 */
	private function calculate_percent(): ?float {
		if ( null === $this->savings_bytes || 0 === $this->source_bytes ) {
			return null;
		}

		return round( ( $this->savings_bytes / $this->source_bytes ) * 100, 2 );
	}

	/**
	 * Calculate minimum threshold status.
	 *
	 * @return bool|null
	 */
	private function calculate_meets_minimum(): ?bool {
		if ( null === $this->minimum_savings_percent || null === $this->savings_percent ) {
			return null;
		}

		return $this->savings_percent >= $this->minimum_savings_percent;
	}
}
