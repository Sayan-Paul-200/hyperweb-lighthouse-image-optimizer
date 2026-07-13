<?php
/**
 * Status refresh request result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Carries one dashboard statistics-recalculation request result.
 */
final class StatusRefreshRequestResult {

	/**
	 * Action name.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $result_code;

	/**
	 * Whether the request succeeded.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Whether recalculation is pending after the request.
	 *
	 * @var bool
	 */
	private $pending;

	/**
	 * Create the result.
	 *
	 * @param string $action Action name.
	 * @param string $result_code Result code.
	 * @param bool   $successful Whether the request succeeded.
	 * @param bool   $pending Whether recalculation is pending.
	 */
	public function __construct( string $action, string $result_code, bool $successful, bool $pending ) {
		$this->action      = $action;
		$this->result_code = $result_code;
		$this->successful  = $successful;
		$this->pending     = $pending;
	}

	/**
	 * Build a queued result.
	 *
	 * @return self
	 */
	public static function queued(): self {
		return new self( 'recalculate', 'queued', true, true );
	}

	/**
	 * Build an already-pending result.
	 *
	 * @return self
	 */
	public static function already_pending(): self {
		return new self( 'recalculate', 'already_pending', true, true );
	}

	/**
	 * Build an unavailable result.
	 *
	 * @return self
	 */
	public static function unavailable(): self {
		return new self( 'recalculate', 'unavailable', false, false );
	}

	/**
	 * Whether the request succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Convert the result to one response payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'action'      => $this->action,
			'result_code' => $this->result_code,
			'pending'     => $this->pending,
		);
	}
}
