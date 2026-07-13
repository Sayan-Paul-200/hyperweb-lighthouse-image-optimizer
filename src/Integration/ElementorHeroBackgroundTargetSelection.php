<?php
/**
 * Elementor hero background target selection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one normalized Elementor background target selection.
 */
final class ElementorHeroBackgroundTargetSelection {

	/**
	 * Elementor element ID.
	 *
	 * @var string
	 */
	private $element_id;

	/**
	 * Supported setting group.
	 *
	 * @var string
	 */
	private $setting_group;

	/**
	 * Create selection.
	 *
	 * @param string $element_id Elementor element ID.
	 * @param string $setting_group Supported setting group.
	 */
	public function __construct( string $element_id, string $setting_group ) {
		$this->element_id    = trim( $element_id );
		$this->setting_group = 'background_overlay' === trim( $setting_group ) ? 'background_overlay' : 'background';
	}

	/**
	 * Build a selection from a stored array payload.
	 *
	 * @param mixed $payload Stored payload.
	 * @return self|null
	 */
	public static function from_array( $payload ): ?self {
		if ( ! is_array( $payload ) ) {
			return null;
		}

		$element_id    = isset( $payload['element_id'] ) && is_scalar( $payload['element_id'] )
			? trim( (string) $payload['element_id'] )
			: '';
		$setting_group = isset( $payload['setting_group'] ) && is_scalar( $payload['setting_group'] )
			? trim( (string) $payload['setting_group'] )
			: '';

		if ( '' === $element_id || ! preg_match( '/^[A-Za-z0-9_-]+$/', $element_id ) ) {
			return null;
		}

		if ( ! in_array( $setting_group, array( 'background', 'background_overlay' ), true ) ) {
			return null;
		}

		return new self( $element_id, $setting_group );
	}

	/**
	 * Build a selection from an encoded form value.
	 *
	 * @param string $value Encoded value.
	 * @return self|null
	 */
	public static function from_encoded_value( string $value ): ?self {
		$parts = explode( '|', trim( $value ), 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		return self::from_array(
			array(
				'element_id'    => $parts[0],
				'setting_group' => $parts[1],
			)
		);
	}

	/**
	 * Get element ID.
	 *
	 * @return string
	 */
	public function element_id(): string {
		return $this->element_id;
	}

	/**
	 * Get setting group.
	 *
	 * @return string
	 */
	public function setting_group(): string {
		return $this->setting_group;
	}

	/**
	 * Build the stable selection key.
	 *
	 * @return string
	 */
	public function key(): string {
		return $this->element_id . '|' . $this->setting_group;
	}

	/**
	 * Build the encoded form value.
	 *
	 * @return string
	 */
	public function encoded_value(): string {
		return $this->key();
	}

	/**
	 * Serialize the selection.
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'element_id'    => $this->element_id,
			'setting_group' => $this->setting_group,
		);
	}
}
