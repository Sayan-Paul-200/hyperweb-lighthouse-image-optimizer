<?php
/**
 * Derivative delete result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Carries remote derivative delete outcomes.
 */
final class DerivativeDeleteResult {

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	private $successful;

	/**
	 * Codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Deleted relative paths.
	 *
	 * @var string[]
	 */
	private $deleted_relative_paths;

	/**
	 * Create result.
	 *
	 * @param bool     $successful Success flag.
	 * @param string[] $codes Codes.
	 * @param string[] $messages Messages.
	 * @param string[] $deleted_relative_paths Deleted relative paths.
	 */
	public function __construct( bool $successful, array $codes = array(), array $messages = array(), array $deleted_relative_paths = array() ) {
		$this->successful             = $successful;
		$this->codes                  = array_values( array_unique( array_filter( array_map( 'strval', $codes ) ) ) );
		$this->messages               = array_values( array_unique( array_filter( array_map( 'strval', $messages ) ) ) );
		$this->deleted_relative_paths = array_values( array_unique( array_filter( array_map( 'strval', $deleted_relative_paths ) ) ) );
	}

	/**
	 * Build success result.
	 *
	 * @param string[] $deleted_relative_paths Deleted relative paths.
	 * @param string[] $codes Codes.
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function success( array $deleted_relative_paths = array(), array $codes = array(), array $messages = array() ): self {
		return new self( true, $codes, $messages, $deleted_relative_paths );
	}

	/**
	 * Build failure result.
	 *
	 * @param string[] $codes Codes.
	 * @param string[] $messages Messages.
	 * @param string[] $deleted_relative_paths Deleted relative paths.
	 * @return self
	 */
	public static function failure( array $codes = array(), array $messages = array(), array $deleted_relative_paths = array() ): self {
		return new self( false, $codes, $messages, $deleted_relative_paths );
	}

	/**
	 * Whether successful.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Get codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Get deleted relative paths.
	 *
	 * @return string[]
	 */
	public function deleted_relative_paths(): array {
		return $this->deleted_relative_paths;
	}
}
