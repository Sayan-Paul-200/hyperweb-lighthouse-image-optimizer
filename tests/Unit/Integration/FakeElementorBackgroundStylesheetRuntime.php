<?php
/**
 * Fake Elementor background stylesheet runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundBreakpointMap;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetRuntimeInterface;

/**
 * Deterministic runtime seam for companion stylesheet tests.
 */
final class FakeElementorBackgroundStylesheetRuntime implements ElementorBackgroundStylesheetRuntimeInterface {

	/**
	 * Whether the request is frontend-eligible.
	 *
	 * @var bool
	 */
	public $frontend_request = true;

	/**
	 * Current singular document ID.
	 *
	 * @var int
	 */
	public $document_id = 0;

	/**
	 * Current breakpoint map.
	 *
	 * @var ElementorBackgroundBreakpointMap|null
	 */
	public $breakpoint_map;

	/**
	 * Enqueued stylesheet records.
	 *
	 * @var array<int,array<string,string>>
	 */
	public $enqueued = array();

	/**
	 * Whether the current request is a frontend request eligible for companion CSS.
	 *
	 * @return bool
	 */
	public function is_frontend_request(): bool {
		return $this->frontend_request;
	}

	/**
	 * Get the current singular frontend document ID.
	 *
	 * @return int
	 */
	public function current_singular_document_id(): int {
		return max( 0, $this->document_id );
	}

	/**
	 * Resolve the current Elementor breakpoint map when reliable.
	 *
	 * @return ElementorBackgroundBreakpointMap|null
	 */
	public function breakpoint_map(): ?ElementorBackgroundBreakpointMap {
		return $this->breakpoint_map instanceof ElementorBackgroundBreakpointMap ? $this->breakpoint_map : null;
	}

	/**
	 * Enqueue one stylesheet.
	 *
	 * @param string $handle Style handle.
	 * @param string $url Public URL.
	 * @param string $version Version string.
	 * @return void
	 */
	public function enqueue_stylesheet( string $handle, string $url, string $version ): void {
		$this->enqueued[] = array(
			'handle'  => $handle,
			'url'     => $url,
			'version' => $version,
		);
	}
}
