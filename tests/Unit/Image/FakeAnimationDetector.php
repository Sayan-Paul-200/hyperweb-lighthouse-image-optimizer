<?php
/**
 * Fake animation detector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\AnimationDetectorInterface;
use HyperWeb\LighthouseImageOptimizer\Image\AnimationStatus;

/**
 * Provides deterministic animation status for validator tests.
 */
final class FakeAnimationDetector implements AnimationDetectorInterface {

	/**
	 * Statuses keyed by normalized path.
	 *
	 * @var array<string,AnimationStatus>
	 */
	public $statuses = array();

	/**
	 * Calls.
	 *
	 * @var array<int,array{path:string,mime_type:string}>
	 */
	public $calls = array();

	/**
	 * Set a status for a path.
	 *
	 * @param string          $path Path.
	 * @param AnimationStatus $status Status.
	 * @return void
	 */
	public function set_status( string $path, AnimationStatus $status ): void {
		$this->statuses[ $this->normalize( $path ) ] = $status;
	}

	/**
	 * Detect animation state.
	 *
	 * @param string $absolute_path Absolute path.
	 * @param string $mime_type MIME type.
	 * @return AnimationStatus
	 */
	public function detect( string $absolute_path, string $mime_type ): AnimationStatus {
		$path          = $this->normalize( $absolute_path );
		$this->calls[] = array(
			'path'      => $path,
			'mime_type' => $mime_type,
		);

		return $this->statuses[ $path ] ?? AnimationStatus::not_applicable( $mime_type );
	}

	/**
	 * Normalize path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize( string $path ): string {
		return str_replace( '\\', '/', $path );
	}
}
