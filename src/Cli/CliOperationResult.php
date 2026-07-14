<?php
/**
 * CLI operation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Carries one normalized CLI operation payload and degraded state.
 */
final class CliOperationResult {

	/**
	 * Operation name.
	 *
	 * @var string
	 */
	private $operation;

	/**
	 * Whether the operation completed in a degraded state.
	 *
	 * @var bool
	 */
	private $degraded;

	/**
	 * Stable codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * User-safe messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Normalized payload.
	 *
	 * @var array<string,mixed>
	 */
	private $payload;

	/**
	 * Create the result.
	 *
	 * @param string              $operation Operation name.
	 * @param bool                $degraded Whether degraded.
	 * @param string[]            $codes Stable codes.
	 * @param string[]            $messages User-safe messages.
	 * @param array<string,mixed> $payload Normalized payload.
	 */
	public function __construct( string $operation, bool $degraded, array $codes, array $messages, array $payload ) {
		$this->operation = trim( $operation );
		$this->degraded  = $degraded;
		$this->codes     = $this->normalize_strings( $codes );
		$this->messages  = $this->normalize_strings( $messages );
		$this->payload   = $payload;
	}

	/**
	 * Build a successful result.
	 *
	 * @param string              $operation Operation name.
	 * @param array<string,mixed> $payload Payload.
	 * @param string[]            $codes Codes.
	 * @param string[]            $messages Messages.
	 * @return self
	 */
	public static function success( string $operation, array $payload, array $codes = array(), array $messages = array() ): self {
		return new self( $operation, false, $codes, $messages, $payload );
	}

	/**
	 * Build a degraded result.
	 *
	 * @param string              $operation Operation name.
	 * @param array<string,mixed> $payload Payload.
	 * @param string[]            $codes Codes.
	 * @param string[]            $messages Messages.
	 * @return self
	 */
	public static function degraded( string $operation, array $payload, array $codes = array(), array $messages = array() ): self {
		return new self( $operation, true, $codes, $messages, $payload );
	}

	/**
	 * Whether degraded.
	 *
	 * @return bool
	 */
	public function is_degraded(): bool {
		return $this->degraded;
	}

	/**
	 * Get operation name.
	 *
	 * @return string
	 */
	public function operation(): string {
		return $this->operation;
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
	 * Get payload.
	 *
	 * @return array<string,mixed>
	 */
	public function payload(): array {
		return $this->payload;
	}

	/**
	 * Serialize safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array_merge(
			array(
				'operation' => $this->operation,
				'degraded'  => $this->degraded,
				'codes'     => $this->codes,
				'messages'  => $this->messages,
			),
			$this->payload
		);
	}

	/**
	 * Normalize string lists.
	 *
	 * @param array<int,mixed> $values Raw values.
	 * @return string[]
	 */
	private function normalize_strings( array $values ): array {
		$normalized = array();

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$value = trim( (string) $value );

			if ( '' !== $value ) {
				$normalized[] = $value;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}
