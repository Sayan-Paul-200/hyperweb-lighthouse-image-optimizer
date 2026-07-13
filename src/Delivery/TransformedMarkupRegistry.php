<?php
/**
 * Request-local transformed markup registry.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Tracks markup signatures already transformed during the current request.
 */
final class TransformedMarkupRegistry {

	/**
	 * Recorded signatures.
	 *
	 * @var array<string,bool>
	 */
	private $signatures = array();

	/**
	 * Whether one attachment/html signature has already been seen.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $html Exact HTML.
	 * @return bool
	 */
	public function has( int $attachment_id, string $html ): bool {
		return isset( $this->signatures[ $this->signature( $attachment_id, $html ) ] );
	}

	/**
	 * Record one attachment/html signature.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $html Exact HTML.
	 * @return void
	 */
	public function record( int $attachment_id, string $html ): void {
		$this->signatures[ $this->signature( $attachment_id, $html ) ] = true;
	}

	/**
	 * Build a stable request-local signature.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $html Exact HTML.
	 * @return string
	 */
	private function signature( int $attachment_id, string $html ): string {
		return max( 0, $attachment_id ) . ':' . sha1( $html );
	}
}
