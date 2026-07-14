<?php
/**
 * PageSpeed HTTP response value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one normalized HTTP response for the PSI client.
 */
final class PageSpeedHttpResponse {

	/**
	 * Whether the request transport succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Status code.
	 *
	 * @var int
	 */
	private $status_code;

	/**
	 * Response body.
	 *
	 * @var string
	 */
	private $body;

	/**
	 * Optional transport error code.
	 *
	 * @var string
	 */
	private $error_code;

	/**
	 * Optional transport error message.
	 *
	 * @var string
	 */
	private $error_message;

	/**
	 * Create the response.
	 *
	 * @param bool   $successful Whether the request transport succeeded.
	 * @param int    $status_code Status code.
	 * @param string $body Response body.
	 * @param string $error_code Optional error code.
	 * @param string $error_message Optional error message.
	 */
	public function __construct(
		bool $successful,
		int $status_code,
		string $body = '',
		string $error_code = '',
		string $error_message = ''
	) {
		$this->successful    = $successful;
		$this->status_code   = max( 0, $status_code );
		$this->body          = $body;
		$this->error_code    = trim( $error_code );
		$this->error_message = trim( $error_message );
	}

	/**
	 * Whether the request transport succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get the status code.
	 *
	 * @return int
	 */
	public function status_code(): int {
		return $this->status_code;
	}

	/**
	 * Get the body.
	 *
	 * @return string
	 */
	public function body(): string {
		return $this->body;
	}

	/**
	 * Get the error code.
	 *
	 * @return string
	 */
	public function error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get the error message.
	 *
	 * @return string
	 */
	public function error_message(): string {
		return $this->error_message;
	}
}
