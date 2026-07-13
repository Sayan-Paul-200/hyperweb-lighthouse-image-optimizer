<?php
/**
 * Fake uploads URL runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\UploadsRuntimeInterface;

/**
 * Fake runtime for delivery resolver tests.
 */
final class FakeUploadsUrlRuntime implements UploadsRuntimeInterface {

	/**
	 * Base URL to return.
	 *
	 * @var string|null
	 */
	public $base_url = 'https://example.test/wp-content/uploads';

	/**
	 * Base directory to return.
	 *
	 * @var string|null
	 */
	public $base_dir = 'C:/site/wp-content/uploads';

	/**
	 * Base URL requests.
	 *
	 * @var DerivativeUrlRequest[]
	 */
	public $base_url_requests = array();

	/**
	 * Base directory read count.
	 *
	 * @var int
	 */
	public $base_dir_reads = 0;

	/**
	 * Filter requests.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $filter_requests = array();

	/**
	 * Optional filter callback.
	 *
	 * @var callable|null
	 */
	public $filter_callback;

	/**
	 * Read the current uploads base URL.
	 *
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string|null
	 */
	public function uploads_base_url( DerivativeUrlRequest $request ): ?string {
		$this->base_url_requests[] = $request;

		return $this->base_url;
	}

	/**
	 * Read the current uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string {
		++$this->base_dir_reads;

		return $this->base_dir;
	}

	/**
	 * Allow runtime filters to rewrite a resolved derivative URL.
	 *
	 * @param string               $url Resolved derivative URL.
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string
	 */
	public function filter_derivative_url( string $url, DerivativeUrlRequest $request ): string {
		$this->filter_requests[] = array(
			'url'     => $url,
			'request' => $request,
		);

		if ( is_callable( $this->filter_callback ) ) {
			return (string) call_user_func( $this->filter_callback, $url, $request );
		}

		return $url;
	}
}
