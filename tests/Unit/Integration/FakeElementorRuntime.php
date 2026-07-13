<?php
/**
 * Fake Elementor runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorRuntimeInterface;

/**
 * Deterministic Elementor runtime seam for integration tests.
 */
final class FakeElementorRuntime implements ElementorRuntimeInterface {

	/**
	 * Whether Elementor is available.
	 *
	 * @var bool
	 */
	public $available = true;

	/**
	 * Whether the request is in editor mode.
	 *
	 * @var bool
	 */
	public $editor_mode = false;

	/**
	 * Whether the request is in preview mode.
	 *
	 * @var bool
	 */
	public $preview_mode = false;

	/**
	 * Whether Elementor runtime is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->available;
	}

	/**
	 * Whether the request is in editor mode.
	 *
	 * @return bool
	 */
	public function is_editor_mode(): bool {
		return $this->editor_mode;
	}

	/**
	 * Whether the request is in preview mode.
	 *
	 * @return bool
	 */
	public function is_preview_mode(): bool {
		return $this->preview_mode;
	}
}
