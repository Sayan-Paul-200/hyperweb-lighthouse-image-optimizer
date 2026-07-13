<?php
/**
 * Elementor oversized selection diagnostic result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one advisory oversized-selection outcome.
 */
final class ElementorOversizedSelectionResult {

	public const CODE_OVERSIZED_FULL_SELECTION_DETECTED = 'oversized_full_selection_detected';
	public const CODE_OVERSIZED_SELECTION_NOT_DETECTED  = 'oversized_selection_not_detected';
	public const CODE_OVERSIZED_SELECTION_UNCERTAIN     = 'oversized_selection_uncertain';
	public const CODE_UNSUPPORTED_ELEMENTOR_CONTEXT     = 'unsupported_elementor_context';

	/**
	 * Whether the fragment is inside reportable Elementor scope.
	 *
	 * @var bool
	 */
	private $reportable;

	/**
	 * Whether an advisory finding exists.
	 *
	 * @var bool
	 */
	private $finding;

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-safe advisory message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Safe scalar details.
	 *
	 * @var array<string,mixed>
	 */
	private $details;

	/**
	 * Create the result.
	 *
	 * @param bool                $reportable Whether the fragment is reportable.
	 * @param bool                $finding Whether an advisory finding exists.
	 * @param string              $code Stable result code.
	 * @param string              $message User-safe advisory message.
	 * @param array<string,mixed> $details Safe scalar details.
	 */
	public function __construct( bool $reportable, bool $finding, string $code, string $message, array $details = array() ) {
		$this->reportable = $reportable;
		$this->finding    = $finding;
		$this->code       = $this->normalize_code( $code );
		$this->message    = trim( $message );
		$this->details    = $this->sanitize_details( $details );
	}

	/**
	 * Build a detected oversized-selection finding.
	 *
	 * @param array<string,mixed> $details Safe scalar details.
	 * @return self
	 */
	public static function finding( array $details = array() ): self {
		return new self(
			true,
			true,
			self::CODE_OVERSIZED_FULL_SELECTION_DETECTED,
			'This Elementor widget selects the full image even though the rendered slot is much smaller.',
			$details
		);
	}

	/**
	 * Build a reportable no-finding result.
	 *
	 * @param array<string,mixed> $details Safe scalar details.
	 * @return self
	 */
	public static function not_detected( array $details = array() ): self {
		return new self(
			true,
			false,
			self::CODE_OVERSIZED_SELECTION_NOT_DETECTED,
			'No oversized full-image selection was detected for this Elementor widget.',
			$details
		);
	}

	/**
	 * Build a reportable uncertain result.
	 *
	 * @param array<string,mixed> $details Safe scalar details.
	 * @return self
	 */
	public static function uncertain( array $details = array() ): self {
		return new self(
			true,
			false,
			self::CODE_OVERSIZED_SELECTION_UNCERTAIN,
			'This Elementor widget could not be evaluated for oversized full-image selection with reliable evidence.',
			$details
		);
	}

	/**
	 * Build an unsupported-context result.
	 *
	 * @param array<string,mixed> $details Safe scalar details.
	 * @return self
	 */
	public static function unsupported( array $details = array() ): self {
		return new self(
			false,
			false,
			self::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT,
			'This Elementor image fragment is outside the advisory scope for oversized full-image diagnostics.',
			$details
		);
	}

	/**
	 * Whether the fragment is inside reportable scope.
	 *
	 * @return bool
	 */
	public function is_reportable(): bool {
		return $this->reportable;
	}

	/**
	 * Whether an advisory finding exists.
	 *
	 * @return bool
	 */
	public function has_finding(): bool {
		return $this->finding;
	}

	/**
	 * Get the stable result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get the user-safe message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get safe scalar details.
	 *
	 * @return array<string,mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Serialize the result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'reportable' => $this->reportable,
			'finding'    => $this->finding,
			'code'       => $this->code,
			'message'    => $this->message,
			'details'    => $this->details,
		);
	}

	/**
	 * Normalize one result code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );

		if ( ! in_array( $code, $this->allowed_codes(), true ) ) {
			return self::CODE_OVERSIZED_SELECTION_UNCERTAIN;
		}

		return $code;
	}

	/**
	 * Get allowed stable result codes.
	 *
	 * @return string[]
	 */
	private function allowed_codes(): array {
		return array(
			self::CODE_OVERSIZED_FULL_SELECTION_DETECTED,
			self::CODE_OVERSIZED_SELECTION_NOT_DETECTED,
			self::CODE_OVERSIZED_SELECTION_UNCERTAIN,
			self::CODE_UNSUPPORTED_ELEMENTOR_CONTEXT,
		);
	}

	/**
	 * Sanitize nested detail values down to public-safe scalars.
	 *
	 * @param array<string,mixed> $details Raw details.
	 * @return array<string,mixed>
	 */
	private function sanitize_details( array $details ): array {
		$sanitized = array();

		foreach ( $details as $key => $value ) {
			if ( ! is_string( $key ) || '' === trim( $key ) ) {
				continue;
			}

			$sanitized[ $key ] = $this->sanitize_value( $value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize one detail value recursively.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private function sanitize_value( $value ) {
		if ( is_null( $value ) || is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return trim( $value );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();

			foreach ( $value as $key => $item ) {
				$sanitized[ $key ] = $this->sanitize_value( $item );
			}

			return $sanitized;
		}

		return null;
	}
}
