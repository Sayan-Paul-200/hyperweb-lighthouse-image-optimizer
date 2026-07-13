<?php
/**
 * Fake REST request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

/**
 * Minimal request object for controller tests.
 */
final class FakeRestRequest {

	/**
	 * Request params.
	 *
	 * @var array<string,mixed>
	 */
	private $params;

	/**
	 * Create the request.
	 *
	 * @param array<string,mixed> $params Request params.
	 */
	public function __construct( array $params = array() ) {
		$this->params = $params;
	}

	/**
	 * Get one request param.
	 *
	 * @param string $key Param name.
	 * @return mixed
	 */
	public function get_param( string $key ) {
		return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null;
	}

	/**
	 * Get all request params.
	 *
	 * @return array<string,mixed>
	 */
	public function get_params(): array {
		return $this->params;
	}
}
