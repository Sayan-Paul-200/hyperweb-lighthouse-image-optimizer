<?php
/**
 * Settings sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Normalizes settings according to the schema definitions.
 */
final class SettingsSanitizer {

	/**
	 * Settings definitions.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $definitions;

	/**
	 * Default values.
	 *
	 * @var array<string,mixed>
	 */
	private $defaults;

	/**
	 * Create a sanitizer from the canonical schema.
	 *
	 * @return self
	 */
	public static function for_schema(): self {
		return new self( SettingsSchema::definitions(), SettingsSchema::defaults() );
	}

	/**
	 * Create the sanitizer.
	 *
	 * @param array<string,array<string,mixed>> $definitions Setting definitions.
	 * @param array<string,mixed>               $defaults Default values.
	 */
	public function __construct( array $definitions, array $defaults ) {
		$this->definitions = $definitions;
		$this->defaults    = $defaults;
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array<mixed> $input Raw settings.
	 * @return SettingsResult
	 */
	public function sanitize( array $input ): SettingsResult {
		$settings = array();
		$codes    = array();

		foreach ( $this->definitions as $key => $definition ) {
			$default_value = $this->defaults[ $key ] ?? $definition['default'];
			$value         = array_key_exists( $key, $input ) ? $input[ $key ] : $default_value;

			$settings[ $key ] = $this->sanitize_value( $value, $definition, $default_value );
		}

		$settings['schema_version'] = SettingsSchema::SCHEMA_VERSION;

		if ( $this->has_unknown_keys( $input ) ) {
			$codes[] = SettingsResult::CODE_UNKNOWN_KEYS_DROPPED;
		}

		if ( $settings !== $input ) {
			$codes[] = SettingsResult::CODE_SANITIZED;
		}

		return new SettingsResult(
			$settings,
			true,
			$settings !== $input,
			$codes
		);
	}

	/**
	 * Sanitize one value according to a definition.
	 *
	 * @param mixed               $value Raw value.
	 * @param array<string,mixed> $definition Setting definition.
	 * @param mixed               $default_value Default value.
	 * @return mixed
	 */
	private function sanitize_value( $value, array $definition, $default_value ) {
		$type = (string) $definition['type'];

		if ( SettingsSchema::TYPE_BOOLEAN === $type ) {
			return $this->sanitize_boolean( $value, (bool) $default_value );
		}

		if ( SettingsSchema::TYPE_INTEGER === $type ) {
			return $this->sanitize_integer(
				$value,
				(int) $default_value,
				(int) $definition['minimum'],
				(int) $definition['maximum']
			);
		}

		if ( SettingsSchema::TYPE_FORMAT_LIST === $type ) {
			return $this->sanitize_format_list(
				$value,
				is_array( $default_value ) ? $default_value : array(),
				is_array( $definition['allowed_values'] ) ? $definition['allowed_values'] : array()
			);
		}

		return $default_value;
	}

	/**
	 * Sanitize a boolean setting.
	 *
	 * @param mixed $value Raw value.
	 * @param bool  $default_value Default value.
	 * @return bool
	 */
	private function sanitize_boolean( $value, bool $default_value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return 1 === $value;
		}

		if ( is_string( $value ) ) {
			$normalized = strtolower( trim( $value ) );

			if ( in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}

			if ( in_array( $normalized, array( '0', 'false', 'no', 'off', '' ), true ) ) {
				return false;
			}
		}

		return $default_value;
	}

	/**
	 * Sanitize and clamp an integer setting.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $default_value Default value.
	 * @param int   $minimum Minimum value.
	 * @param int   $maximum Maximum value.
	 * @return int
	 */
	private function sanitize_integer( $value, int $default_value, int $minimum, int $maximum ): int {
		if ( is_int( $value ) ) {
			$integer = $value;
		} elseif ( is_float( $value ) ) {
			$integer = (int) $value;
		} elseif ( is_string( $value ) && preg_match( '/^-?\d+$/', trim( $value ) ) ) {
			$integer = (int) trim( $value );
		} else {
			$integer = $default_value;
		}

		return max( $minimum, min( $maximum, $integer ) );
	}

	/**
	 * Sanitize a format-list setting.
	 *
	 * @param mixed    $value Raw value.
	 * @param string[] $default_value Default formats.
	 * @param string[] $allowed Allowed formats.
	 * @return string[]
	 */
	private function sanitize_format_list( $value, array $default_value, array $allowed ): array {
		if ( is_string( $value ) ) {
			$value = array( $value );
		}

		if ( ! is_array( $value ) ) {
			return array_values( $default_value );
		}

		$formats = array();

		foreach ( $value as $format ) {
			if ( ! is_string( $format ) ) {
				continue;
			}

			$format = strtolower( trim( $format ) );

			if ( in_array( $format, $allowed, true ) && ! in_array( $format, $formats, true ) ) {
				$formats[] = $format;
			}
		}

		return array() === $formats ? array_values( $default_value ) : $formats;
	}

	/**
	 * Determine whether the input contains unknown keys.
	 *
	 * @param array<mixed> $input Raw input.
	 * @return bool
	 */
	private function has_unknown_keys( array $input ): bool {
		foreach ( $input as $key => $value ) {
			unset( $value );

			if ( ! is_string( $key ) || ! array_key_exists( $key, $this->definitions ) ) {
				return true;
			}
		}

		return false;
	}
}
