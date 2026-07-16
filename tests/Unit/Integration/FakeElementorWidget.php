<?php
/**
 * Fake Elementor widget for integration tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

/**
 * Minimal Elementor widget stand-in.
 */
final class FakeElementorWidget {

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Create widget.
	 *
	 * @param string $name Widget name.
	 */
	public function __construct( string $name ) {
		$this->name = $name;
	}

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}
}
