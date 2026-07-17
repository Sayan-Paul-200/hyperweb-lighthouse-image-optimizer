<?php
/**
 * Elementor widget matcher.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalyzerInterface;

/**
 * Classifies Elementor image fragments conservatively.
 */
final class ElementorWidgetMatcher {

	/**
	 * Supported frontend attachment-backed Elementor widget.
	 *
	 * @var string
	 */
	public const MATCH_SUPPORTED_ATTACHMENT_WIDGET = 'supported_attachment_widget';

	/**
	 * Explicitly excluded Elementor gallery or carousel fragment.
	 *
	 * @var string
	 */
	public const MATCH_EXCLUDED_GALLERY_OR_CAROUSEL = 'excluded_gallery_or_carousel';

	/**
	 * Editor or preview request where delivery must fail open.
	 *
	 * @var string
	 */
	public const MATCH_EDITOR_OR_PREVIEW = 'editor_or_preview';

	/**
	 * Unrecognized or uncertain fragment.
	 *
	 * @var string
	 */
	public const MATCH_UNRECOGNIZED = 'unrecognized';

	/**
	 * Static attachment-widget names supported by the narrow Elementor delivery bridge.
	 *
	 * @var string[]
	 */
	public const SUPPORTED_STATIC_WIDGET_NAMES = array(
		'image',
		'image-box',
		'call-to-action',
		'theme-site-logo',
	);

	/**
	 * Runtime seam.
	 *
	 * @var ElementorRuntimeInterface
	 */
	private $runtime;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Create matcher.
	 *
	 * @param ElementorRuntimeInterface    $runtime Runtime seam.
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 */
	public function __construct( ElementorRuntimeInterface $runtime, ImageMarkupAnalyzerInterface $analyzer ) {
		$this->runtime  = $runtime;
		$this->analyzer = $analyzer;
	}

	/**
	 * Match one standalone Elementor image fragment.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	public function match( string $html ): string {
		return $this->match_fragment( $html, '' );
	}

	/**
	 * Match one standalone Elementor image fragment with trusted widget context.
	 *
	 * @param string $html HTML fragment.
	 * @param string $widget_name Elementor widget name.
	 * @param bool   $attachment_backed Whether a trusted resolver has already confirmed attachment backing.
	 * @return string
	 */
	public function match_widget_fragment( string $html, string $widget_name, bool $attachment_backed = false ): string {
		return $this->match_fragment( $html, $widget_name, $attachment_backed );
	}

	/**
	 * Match one standalone Elementor image fragment.
	 *
	 * @param string $html HTML fragment.
	 * @param string $widget_name Elementor widget name, or empty for fragment-only matching.
	 * @param bool   $attachment_backed Whether a trusted resolver has already confirmed attachment backing.
	 * @return string
	 */
	private function match_fragment( string $html, string $widget_name, bool $attachment_backed = false ): string {
		if ( ! $this->runtime->is_available() ) {
			return self::MATCH_UNRECOGNIZED;
		}

		$analysis = $this->analyzer->analyze( $html );

		if ( ! $analysis->is_renderable_img() ) {
			return self::MATCH_UNRECOGNIZED;
		}

		$class_name = $this->normalize_class( $this->extract_attribute_value( $html, 'class' ) );

		if ( $this->is_gallery_or_carousel_markup( $class_name, $html ) ) {
			return self::MATCH_EXCLUDED_GALLERY_OR_CAROUSEL;
		}

		if ( ! $this->is_supported_attachment_widget_markup( $class_name, $html, $widget_name, $attachment_backed ) ) {
			return self::MATCH_UNRECOGNIZED;
		}

		if ( $this->runtime->is_editor_mode() || $this->runtime->is_preview_mode() ) {
			return self::MATCH_EDITOR_OR_PREVIEW;
		}

		return self::MATCH_SUPPORTED_ATTACHMENT_WIDGET;
	}

	/**
	 * Determine whether one fragment is a known-fragile gallery or carousel surface.
	 *
	 * @param string $class_name Normalized class string.
	 * @param string $html Original markup.
	 * @return bool
	 */
	private function is_gallery_or_carousel_markup( string $class_name, string $html ): bool {
		if (
			false !== strpos( $class_name, 'e-gallery-image' )
			|| false !== strpos( $class_name, 'elementor-gallery-item__image' )
			|| false !== strpos( $class_name, 'swiper-slide-image' )
		) {
			return true;
		}

		return $this->has_attribute( $html, 'data-swiper-slide-index' );
	}

	/**
	 * Determine whether one fragment is a supported attachment-backed Elementor widget image.
	 *
	 * @param string $class_name Normalized class string.
	 * @param string $html Original markup.
	 * @param string $widget_name Elementor widget name, or empty for fragment-only matching.
	 * @param bool   $attachment_backed Whether a trusted resolver has already confirmed attachment backing.
	 * @return bool
	 */
	private function is_supported_attachment_widget_markup( string $class_name, string $html, string $widget_name = '', bool $attachment_backed = false ): bool {
		if ( ! $attachment_backed && ! $this->has_attachment_marker( $class_name, $html ) ) {
			return false;
		}

		if ( $this->is_supported_static_widget_name( $widget_name ) ) {
			return true;
		}

		if (
			false !== strpos( $class_name, 'elementor-animation-' )
			|| $this->has_attribute( $html, 'data-elementor-open-lightbox' )
			|| $this->has_attribute( $html, 'data-elementor-lightbox-slideshow' )
			|| $this->has_attribute( $html, 'data-e-action-hash' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determine whether one Elementor widget name is in the static-image allowlist.
	 *
	 * @param string $widget_name Widget name.
	 * @return bool
	 */
	private function is_supported_static_widget_name( string $widget_name ): bool {
		return in_array(
			strtolower( trim( $widget_name ) ),
			self::SUPPORTED_STATIC_WIDGET_NAMES,
			true
		);
	}

	/**
	 * Determine whether one fragment carries a trusted attachment marker.
	 *
	 * @param string $class_name Normalized class string.
	 * @param string $html Original markup.
	 * @return bool
	 */
	private function has_attachment_marker( string $class_name, string $html ): bool {
		if ( 1 === preg_match( '/\bwp-image-\d+\b/', $class_name ) ) {
			return true;
		}

		foreach ( array( 'data-id', 'data-attachment-id' ) as $attribute ) {
			$value = $this->extract_attribute_value( $html, $attribute );

			if ( null !== $value && is_numeric( $value ) && (int) $value > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize one class string.
	 *
	 * @param string|null $class_name Class string.
	 * @return string
	 */
	private function normalize_class( ?string $class_name ): string {
		if ( null === $class_name ) {
			return '';
		}

		return strtolower( trim( preg_replace( '/\s+/', ' ', $class_name ) ?? '' ) );
	}

	/**
	 * Extract one raw attribute value from the original HTML fragment.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function extract_attribute_value( string $html, string $attribute ): ?string {
		if ( '' === $html ) {
			return null;
		}

		$pattern = sprintf(
			'/\b%s\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
			preg_quote( $attribute, '/' )
		);

		if ( 1 !== preg_match( $pattern, $html, $matches ) ) {
			return null;
		}

		foreach ( array( 1, 2, 3 ) as $index ) {
			if ( array_key_exists( $index, $matches ) && '' !== $matches[ $index ] ) {
				return (string) $matches[ $index ];
			}
		}

		return null;
	}

	/**
	 * Determine whether one attribute exists in the fragment.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return bool
	 */
	private function has_attribute( string $html, string $attribute ): bool {
		if ( '' === $html ) {
			return false;
		}

		return 1 === preg_match(
			sprintf(
				'/\b%s\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
				preg_quote( $attribute, '/' )
			),
			$html
		);
	}
}
