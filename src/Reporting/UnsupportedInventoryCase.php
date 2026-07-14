<?php
/**
 * Unsupported inventory case.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one user-safe unsupported or uncertain page-inventory observation.
 */
final class UnsupportedInventoryCase {

	public const SOURCE_CORE_CONTENT        = 'core_content';
	public const SOURCE_ELEMENTOR_BACKGROUND = 'elementor_background';

	/**
	 * Stable code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Source family.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Safe details.
	 *
	 * @var array<string,mixed>
	 */
	private $details;

	/**
	 * Create case.
	 *
	 * @param string              $code Stable code.
	 * @param string              $source Source family.
	 * @param string              $label Short label.
	 * @param string              $message User-safe message.
	 * @param array<string,mixed> $details Safe details.
	 */
	public function __construct( string $code, string $source, string $label, string $message, array $details = array() ) {
		$this->code    = $this->normalize_key( $code, 'inventory_unknown' );
		$this->source  = $this->normalize_source( $source );
		$this->label   = '' === trim( $label ) ? 'Unsupported inventory case' : trim( $label );
		$this->message = trim( $message );
		$this->details = $this->sanitize_details( $details );
	}

	/**
	 * Serialize case.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code'    => $this->code,
			'source'  => $this->source,
			'label'   => $this->label,
			'message' => $this->message,
			'details' => $this->details,
		);
	}

	/**
	 * Normalize one source family.
	 *
	 * @param string $source Raw source.
	 * @return string
	 */
	private function normalize_source( string $source ): string {
		$source = $this->normalize_key( $source, self::SOURCE_CORE_CONTENT );

		if ( ! in_array( $source, array( self::SOURCE_CORE_CONTENT, self::SOURCE_ELEMENTOR_BACKGROUND ), true ) ) {
			return self::SOURCE_CORE_CONTENT;
		}

		return $source;
	}

	/**
	 * Sanitize one details payload conservatively.
	 *
	 * @param array<string,mixed> $details Raw details.
	 * @return array<string,mixed>
	 */
	private function sanitize_details( array $details ): array {
		$sanitized = array();

		foreach ( $details as $key => $value ) {
			$key = $this->normalize_key( (string) $key, '' );

			if ( '' === $key ) {
				continue;
			}

			if ( is_bool( $value ) || is_int( $value ) ) {
				$sanitized[ $key ] = $value;
				continue;
			}

			if ( is_string( $value ) ) {
				$sanitized[ $key ] = $this->truncate( trim( preg_replace( '/\s+/', ' ', $value ) ?? '' ) );
				continue;
			}

			if ( null === $value ) {
				$sanitized[ $key ] = null;
			}
		}

		return $sanitized;
	}

	/**
	 * Normalize machine-readable keys.
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
	 * Truncate one string.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function truncate( string $value ): string {
		return strlen( $value ) > 255 ? substr( $value, 0, 252 ) . '...' : $value;
	}
}
