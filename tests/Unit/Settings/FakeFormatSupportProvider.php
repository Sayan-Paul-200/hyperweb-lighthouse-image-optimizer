<?php
/**
 * Fake format support provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;

/**
 * Provides deterministic canonical support responses.
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

		return $this->support[ $format ] ?? FormatSupportResult::unknown(
			$format,
			null,
			null,
			null,
			'unknown_in_test'
		);
	}
}
