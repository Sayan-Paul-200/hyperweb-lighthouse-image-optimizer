<?php
/**
 * Elementor background preload link.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\PreloadLinkInterface;

/**
 * Carries one deduplicated critical background preload tag payload.
 */
final class ElementorBackgroundPreloadLink implements PreloadLinkInterface {

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
	 * Device.
	 *
	 * @var string
	 */
	private $device;

	/**
	 * Format.
	 *
	 * @var string
	 */
	private $format;

	/**
	 * Href.
	 *
	 * @var string
	 */
	private $href;

	/**
	 * MIME type.
	 *
	 * @var string
	 */
	private $mime;

	/**
	 * Optional media query.
	 *
	 * @var string|null
	 */
	private $media;

	/**
	 * Create link.
	 *
	 * @param int         $document_id Document ID.
	 * @param string      $element_id Elementor element ID.
	 * @param string      $setting_group Setting group.
	 * @param string      $device Device scope.
	 * @param string      $format Format.
	 * @param string      $href Href.
	 * @param string      $mime MIME.
	 * @param string|null $media Optional media query.
	 */
	public function __construct(
		int $document_id,
		string $element_id,
		string $setting_group,
		string $device,
		string $format,
		string $href,
		string $mime,
		?string $media = null
	) {
		$this->document_id   = max( 0, $document_id );
		$this->element_id    = trim( $element_id );
		$this->setting_group = 'background_overlay' === trim( $setting_group ) ? 'background_overlay' : 'background';
		$this->device        = in_array( $device, array( 'desktop', 'tablet', 'mobile' ), true ) ? $device : 'desktop';
		$this->format        = strtolower( trim( $format ) );
		$this->href          = trim( $href );
		$this->mime          = strtolower( trim( $mime ) );
		$this->media         = $this->normalize_optional_string( $media );
	}

	/**
	 * Build a stable dedupe key.
	 *
	 * @return string
	 */
	public function key(): string {
		return sha1(
			implode(
				'|',
				array(
					$this->document_id,
					$this->element_id,
					$this->setting_group,
					$this->device,
					$this->format,
					$this->href,
					$this->mime,
					$this->media ?? '',
				)
			)
		);
	}

	/**
	 * Render final HTML.
	 *
	 * @return string
	 */
	public function html(): string {
		$media = null !== $this->media ? ' media="' . $this->escape_attr( $this->media ) . '"' : '';

		return sprintf(
			'<link rel="preload" as="image" href="%s" type="%s"%s>',
			$this->escape_url( $this->href ),
			$this->escape_attr( $this->mime ),
			$media
		);
	}

	/**
	 * Serialize the link.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'document_id'   => $this->document_id,
			'element_id'    => $this->element_id,
			'setting_group' => $this->setting_group,
			'device'        => $this->device,
			'format'        => $this->format,
			'href'          => $this->href,
			'mime'          => $this->mime,
			'media'         => $this->media,
			'key'           => $this->key(),
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
	 * Escape one attribute.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape one URL.
	 *
	 * @param string $value URL.
	 * @return string
	 */
	private function escape_url( string $value ): string {
		if ( function_exists( 'esc_url' ) ) {
			return esc_url( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
