<?php
/**
 * Pre-allocation resource guard.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;

/**
 * Estimates memory requirements and pixel limits before loading large images.
 */
final class ResourceGuard {

	/**
	 * Available memory limit.
	 *
	 * @var MemoryLimit
	 */
	private $memory_limit;

	/**
	 * Maximum allowed pixel count.
	 *
	 * @var int
	 */
	private $max_pixel_count;

	/**
	 * Assumed channels per pixel (e.g. 4 for RGBA).
	 *
	 * @var int
	 */
	private $channels;

	/**
	 * Overhead multiplier for working buffers (e.g. 1.8 for GD/Imagick).
	 *
	 * @var float
	 */
	private $overhead_multiplier;

	/**
	 * Safety margin to preserve for PHP/WordPress (e.g. 0.8 to use max 80%).
	 *
	 * @var float
	 */
	private $safety_margin;

	/**
	 * Create a resource guard.
	 *
	 * @param MemoryLimit $memory_limit Parsed memory limit.
	 * @param int         $max_pixel_count Maximum pixel count.
	 * @param int         $channels Assumed channels per pixel.
	 * @param float       $overhead_multiplier Working buffer overhead multiplier.
	 * @param float       $safety_margin Allowed fraction of total memory (0.0 to 1.0).
	 */
	public function __construct(
		MemoryLimit $memory_limit,
		int $max_pixel_count = 40000000,
		int $channels = 4,
		float $overhead_multiplier = 1.8,
		float $safety_margin = 0.8
	) {
		$this->memory_limit        = $memory_limit;
		$this->max_pixel_count     = max( 1, $max_pixel_count );
		$this->channels            = max( 1, $channels );
		$this->overhead_multiplier = max( 1.0, $overhead_multiplier );
		$this->safety_margin       = max( 0.1, min( 1.0, $safety_margin ) );
	}

	/**
	 * Build a WordPress-backed resource guard.
	 *
	 * @param MemoryLimit $memory_limit Effective memory limit.
	 * @return self
	 */
	public static function for_wordpress( MemoryLimit $memory_limit ): self {
		$default_max_pixels = 40000000;
		$max_pixels         = $default_max_pixels;

		/**
		 * Filter the maximum allowed source image pixel count.
		 *
		 * @param int $max_pixels Default 40,000,000 (roughly 40 megapixels).
		 */
		if ( function_exists( 'apply_filters' ) ) {
			$max_pixels = (int) \apply_filters( 'hwlio_max_pixel_count', $default_max_pixels );
		}

		return new self(
			$memory_limit,
			$max_pixels,
			4,
			1.8,
			0.8
		);
	}

	/**
	 * Check whether the source image is safe to load.
	 *
	 * @param SourceImage $source Source image.
	 * @return ResourceGuardResult
	 */
	public function check( SourceImage $source ): ResourceGuardResult {
		$pixel_count = $source->width() * $source->height();

		$estimated_memory = (int) ceil( $pixel_count * $this->channels * $this->overhead_multiplier );

		$available_memory = null;
		if ( ! $this->memory_limit->is_unknown() && ! $this->memory_limit->is_unlimited() ) {
			$limit_bytes = $this->memory_limit->bytes();
			if ( null !== $limit_bytes ) {
				$available_memory = (int) floor( $limit_bytes * $this->safety_margin );
			}
		}

		if ( $pixel_count > $this->max_pixel_count ) {
			return ResourceGuardResult::denied(
				'pixel_limit_exceeded',
				'The source image exceeds the maximum allowed pixel count.',
				$pixel_count,
				$this->max_pixel_count,
				$estimated_memory,
				$available_memory
			);
		}

		if ( null !== $available_memory && $estimated_memory > $available_memory ) {
			return ResourceGuardResult::denied(
				'memory_estimate_exceeded',
				'The estimated memory required to process this image exceeds the safe limit.',
				$pixel_count,
				$this->max_pixel_count,
				$estimated_memory,
				$available_memory
			);
		}

		return ResourceGuardResult::allowed(
			$pixel_count,
			$this->max_pixel_count,
			$estimated_memory,
			$available_memory
		);
	}
}
