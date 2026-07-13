<?php
/**
 * Supported Elementor background-image source.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one supported structured Elementor background mapping.
 */
final class ElementorBackgroundSource {

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
	 * Elementor element type.
	 *
	 * @var string
	 */
	private $element_type;

	/**
	 * Elementor widget type when present.
	 *
	 * @var string|null
	 */
	private $widget_type;

	/**
	 * Background setting group.
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
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Public URL when present.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * Stable setting key.
	 *
	 * @var string
	 */
	private $setting_key;

	/**
	 * Create one supported background source.
	 *
	 * @param int         $document_id Document ID.
	 * @param string      $element_id Elementor element ID.
	 * @param string      $element_type Elementor element type.
	 * @param string|null $widget_type Elementor widget type.
	 * @param string      $setting_group Setting group.
	 * @param string      $device Device scope.
	 * @param int         $attachment_id Attachment ID.
	 * @param string|null $url Public URL.
	 * @param string      $setting_key Setting key.
	 */
	public function __construct(
		int $document_id,
		string $element_id,
		string $element_type,
		?string $widget_type,
		string $setting_group,
		string $device,
		int $attachment_id,
		?string $url,
		string $setting_key
	) {
		$this->document_id   = max( 0, $document_id );
		$this->element_id    = trim( $element_id );
		$this->element_type  = trim( $element_type );
		$this->widget_type   = $this->normalize_optional_string( $widget_type );
		$this->setting_group = in_array( $setting_group, array( 'background', 'background_overlay' ), true ) ? $setting_group : 'background';
		$this->device        = in_array( $device, array( 'desktop', 'tablet', 'mobile' ), true ) ? $device : 'desktop';
		$this->attachment_id = max( 0, $attachment_id );
		$this->url           = $this->normalize_url( $url );
		$this->setting_key   = trim( $setting_key );
	}

	/**
	 * Serialize one supported source.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'document_id'   => $this->document_id,
			'element_id'    => $this->element_id,
			'element_type'  => $this->element_type,
			'widget_type'   => $this->widget_type,
			'setting_group' => $this->setting_group,
			'device'        => $this->device,
			'attachment_id' => $this->attachment_id,
			'url'           => $this->url,
			'setting_key'   => $this->setting_key,
		);
	}

	/**
	 * Normalize one optional string.
	 *
	 * @param string|null $value Raw value.
	 * @return string|null
	 */
	private function normalize_optional_string( ?string $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		return '' === $value ? null : $value;
	}

	/**
	 * Normalize one public URL.
	 *
	 * @param string|null $url Raw URL.
	 * @return string|null
	 */
	private function normalize_url( ?string $url ): ?string {
		if ( ! is_string( $url ) ) {
			return null;
		}

		$url = trim( $url );

		if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return null;
		}

		return $url;
	}
}
