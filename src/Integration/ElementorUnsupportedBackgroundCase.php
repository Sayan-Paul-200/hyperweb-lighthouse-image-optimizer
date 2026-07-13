<?php
/**
 * Unsupported Elementor background-discovery case.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one user-safe unsupported background-discovery observation.
 */
final class ElementorUnsupportedBackgroundCase {

	public const CODE_UNSUPPORTED_CSS_URL          = 'unsupported_css_url';
	public const CODE_UNSUPPORTED_BACKGROUND_MODE  = 'unsupported_background_mode';
	public const CODE_UNSUPPORTED_BACKGROUND_VALUE = 'unsupported_background_value';
	public const CODE_INVALID_DOCUMENT_DATA        = 'invalid_document_data';

	/**
	 * Stable code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $document_id;

	/**
	 * Elementor element ID.
	 *
	 * @var string
	 */
	private $element_id;

	/**
	 * Setting group.
	 *
	 * @var string
	 */
	private $setting_group;

	/**
	 * Device scope.
	 *
	 * @var string
	 */
	private $device;

	/**
	 * Stable setting key.
	 *
	 * @var string
	 */
	private $setting_key;

	/**
	 * Safe value hint.
	 *
	 * @var string|null
	 */
	private $value_hint;

	/**
	 * Create one unsupported case.
	 *
	 * @param string      $code Stable code.
	 * @param int         $document_id Document ID.
	 * @param string      $element_id Elementor element ID.
	 * @param string      $setting_group Setting group.
	 * @param string      $device Device scope.
	 * @param string      $setting_key Setting key.
	 * @param string|null $value_hint Safe value hint.
	 */
	public function __construct(
		string $code,
		int $document_id,
		string $element_id,
		string $setting_group,
		string $device,
		string $setting_key,
		?string $value_hint = null
	) {
		$this->code          = $this->normalize_code( $code );
		$this->document_id   = max( 0, $document_id );
		$this->element_id    = trim( $element_id );
		$this->setting_group = trim( $setting_group );
		$this->device        = in_array( $device, array( 'desktop', 'tablet', 'mobile' ), true ) ? $device : 'desktop';
		$this->setting_key   = trim( $setting_key );
		$this->value_hint    = $this->sanitize_hint( $value_hint );
	}

	/**
	 * Get the stable code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Serialize one unsupported case.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code'          => $this->code,
			'document_id'   => $this->document_id,
			'element_id'    => $this->element_id,
			'setting_group' => $this->setting_group,
			'device'        => $this->device,
			'setting_key'   => $this->setting_key,
			'value_hint'    => $this->value_hint,
		);
	}

	/**
	 * Normalize one unsupported-case code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );

		if ( ! in_array( $code, $this->allowed_codes(), true ) ) {
			return self::CODE_UNSUPPORTED_BACKGROUND_VALUE;
		}

		return $code;
	}

	/**
	 * Get allowed stable codes.
	 *
	 * @return string[]
	 */
	private function allowed_codes(): array {
		return array(
			self::CODE_UNSUPPORTED_CSS_URL,
			self::CODE_UNSUPPORTED_BACKGROUND_MODE,
			self::CODE_UNSUPPORTED_BACKGROUND_VALUE,
			self::CODE_INVALID_DOCUMENT_DATA,
		);
	}

	/**
	 * Sanitize one safe value hint.
	 *
	 * @param string|null $hint Raw hint.
	 * @return string|null
	 */
	private function sanitize_hint( ?string $hint ): ?string {
		if ( ! is_string( $hint ) ) {
			return null;
		}

		$hint = trim( preg_replace( '/\s+/', ' ', $hint ) ?? '' );

		if ( '' === $hint ) {
			return null;
		}

		if ( strlen( $hint ) > 255 ) {
			$hint = substr( $hint, 0, 252 ) . '...';
		}

		return $hint;
	}
}
