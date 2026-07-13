<?php
/**
 * Conflict result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Conflict;

/**
 * Represents one capability-first compatibility warning.
 */
final class ConflictResult {

	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_ERROR   = 'error';

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Severity.
	 *
	 * @var string
	 */
	private $severity;

	/**
	 * Capability key.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * User-facing label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * User-facing message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Evidence plugin display names.
	 *
	 * @var string[]
	 */
	private $evidence_plugins;

	/**
	 * Recommended setting keys.
	 *
	 * @var string[]
	 */
	private $setting_keys;

	/**
	 * Create a conflict result.
	 *
	 * @param string   $code Stable code.
	 * @param string   $severity Severity.
	 * @param string   $capability Capability key.
	 * @param string   $label User-facing label.
	 * @param string   $message User-facing message.
	 * @param string[] $evidence_plugins Evidence plugin names.
	 * @param string[] $setting_keys Setting keys.
	 */
	public function __construct(
		string $code,
		string $severity,
		string $capability,
		string $label,
		string $message,
		array $evidence_plugins,
		array $setting_keys
	) {
		$this->code             = $this->normalize_key( $code, 'overlap_unknown' );
		$this->severity         = self::normalize_severity( $severity );
		$this->capability       = $this->normalize_key( $capability, 'unknown' );
		$this->label            = '' === trim( $label ) ? 'Compatibility warning' : trim( $label );
		$this->message          = trim( $message );
		$this->evidence_plugins = $this->normalize_string_list( $evidence_plugins );
		$this->setting_keys     = $this->normalize_setting_keys( $setting_keys );
	}

	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get severity.
	 *
	 * @return string
	 */
	public function severity(): string {
		return $this->severity;
	}

	/**
	 * Get capability.
	 *
	 * @return string
	 */
	public function capability(): string {
		return $this->capability;
	}

	/**
	 * Get label.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Get message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get evidence plugin names.
	 *
	 * @return string[]
	 */
	public function evidence_plugins(): array {
		return $this->evidence_plugins;
	}

	/**
	 * Get recommended setting keys.
	 *
	 * @return string[]
	 */
	public function setting_keys(): array {
		return $this->setting_keys;
	}

	/**
	 * Serialize for admin/REST use.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code'             => $this->code,
			'severity'         => $this->severity,
			'capability'       => $this->capability,
			'label'            => $this->label,
			'message'          => $this->message,
			'evidence_plugins' => $this->evidence_plugins,
			'setting_keys'     => $this->setting_keys,
		);
	}

	/**
	 * Normalize severity.
	 *
	 * @param string $severity Severity.
	 * @return string
	 */
	private static function normalize_severity( string $severity ): string {
		$severity = strtolower( trim( $severity ) );

		return in_array( $severity, array( self::SEVERITY_WARNING, self::SEVERITY_ERROR ), true )
			? $severity
			: self::SEVERITY_WARNING;
	}

	/**
	 * Normalize one machine-readable key.
	 *
	 * @param string $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function normalize_key( string $value, string $fallback ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? $fallback : substr( $value, 0, 64 );
	}

	/**
	 * Normalize one list of display strings.
	 *
	 * @param string[] $values Values.
	 * @return string[]
	 */
	private function normalize_string_list( array $values ): array {
		$normalized = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$value = trim( $value );

			if ( '' !== $value ) {
				$normalized[] = $value;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalize one list of setting keys.
	 *
	 * @param string[] $values Values.
	 * @return string[]
	 */
	private function normalize_setting_keys( array $values ): array {
		$normalized = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$key = $this->normalize_key( $value, '' );

			if ( '' !== $key ) {
				$normalized[] = $key;
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}
