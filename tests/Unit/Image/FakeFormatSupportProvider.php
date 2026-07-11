<?php
/**
 * Fake format support provider for image domain tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;

/**
 * Provides deterministic format support responses for conversion policy tests.
 */
final class FakeFormatSupportProvider implements FormatSupportProviderInterface {

	/**
	 * Support map.
	 *
	 * @var array<string,FormatSupportResult>
	 */
	public $support = array();

	/**
	 * Create the fake provider.
	 *
	 * @param array<string,FormatSupportResult> $support Format support map.
	 */
	public function __construct( array $support = array() ) {
		$this->support = $support;
	}

	/**
	 * Get support details for a format.
	 *
	 * @param string $format Target format.
	 * @return FormatSupportResult
	 */
	public function support_for( string $format ): FormatSupportResult {
		$format = strtolower( trim( $format ) );

		return $this->support[ $format ] ?? FormatSupportResult::unsupported(
			$format,
			null,
			null,
			null,
			'unsupported_in_test'
		);
	}

	/**
	 * Build a provider where both WebP and AVIF are supported.
	 *
	 * @return self
	 */
	public static function all_supported(): self {
		return new self(
			array(
				'webp' => FormatSupportResult::supported( 'webp', 'image/webp' ),
				'avif' => FormatSupportResult::supported( 'avif', 'image/avif' ),
			)
		);
	}

	/**
	 * Build a provider where only WebP is supported.
	 *
	 * @return self
	 */
	public static function webp_only(): self {
		return new self(
			array(
				'webp' => FormatSupportResult::supported( 'webp', 'image/webp' ),
			)
		);
	}
}
