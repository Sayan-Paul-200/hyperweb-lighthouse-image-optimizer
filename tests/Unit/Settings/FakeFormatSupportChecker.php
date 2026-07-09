<?php
/**
 * Fake format support checker.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\FormatSupportCheckerInterface;

/**
 * Provides deterministic format support responses.
 */
final class FakeFormatSupportChecker implements FormatSupportCheckerInterface {

	/**
	 * Format support map.
	 *
	 * @var array<string,bool|null>
	 */
	public $support = array();

	/**
	 * Formats checked.
	 *
	 * @var string[]
	 */
	public $checked = array();

	/**
	 * Create the fake checker.
	 *
	 * @param array<string,bool|null> $support Format support map.
	 */
	public function __construct( array $support = array() ) {
		$this->support = $support;
	}

	/**
	 * Determine whether a target format is supported.
	 *
	 * @param string $format Target format.
	 * @return bool|null
	 */
	public function supports( string $format ): ?bool {
		$this->checked[] = $format;

		return array_key_exists( $format, $this->support ) ? $this->support[ $format ] : null;
	}
}
