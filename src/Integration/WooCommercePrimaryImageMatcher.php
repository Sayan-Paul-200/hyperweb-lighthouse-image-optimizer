<?php
/**
 * WooCommerce primary-image matcher.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\ImageMarkupAnalyzerInterface;

/**
 * Classifies WooCommerce image fragments conservatively.
 */
final class WooCommercePrimaryImageMatcher {

	/**
	 * Confirmed primary product image.
	 *
	 * @var string
	 */
	public const MATCH_PRIMARY = 'primary';

	/**
	 * Confirmed secondary gallery image.
	 *
	 * @var string
	 */
	public const MATCH_GALLERY_SECONDARY = 'gallery_secondary';

	/**
	 * Recognized WooCommerce commerce thumbnail.
	 *
	 * @var string
	 */
	public const MATCH_COMMERCE_THUMBNAIL = 'commerce_thumbnail';

	/**
	 * Variation-sensitive or otherwise uncertain WooCommerce image.
	 *
	 * @var string
	 */
	public const MATCH_VARIATION_OR_UNCERTAIN = 'variation_or_uncertain';

	/**
	 * Unrecognized or unsupported context.
	 *
	 * @var string
	 */
	public const MATCH_UNRECOGNIZED = 'unrecognized';

	/**
	 * Runtime seam.
	 *
	 * @var WooCommerceRuntimeInterface
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
	 * @param WooCommerceRuntimeInterface  $runtime Runtime seam.
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 */
	public function __construct( WooCommerceRuntimeInterface $runtime, ImageMarkupAnalyzerInterface $analyzer ) {
		$this->runtime  = $runtime;
		$this->analyzer = $analyzer;
	}

	/**
	 * Match one fragment context.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $context Markup context.
	 * @return string
	 */
	public function match( int $attachment_id, array $context ): string {
		if ( ! $this->runtime->is_available() ) {
			return self::MATCH_UNRECOGNIZED;
		}

		$html = isset( $context['html'] ) && is_string( $context['html'] ) ? $context['html'] : '';

		if ( '' !== $html ) {
			$analysis = $this->analyzer->analyze( $html );

			if ( ! $analysis->is_renderable_img() ) {
				return self::MATCH_UNRECOGNIZED;
			}
		}

		$class            = $this->normalize_class(
			isset( $context['class'] ) && is_string( $context['class'] )
				? $context['class']
				: $this->extract_attribute_value( $html, 'class' )
		);
		$src              = $this->normalize_url(
			isset( $context['src'] ) && is_string( $context['src'] )
				? $context['src']
				: $this->extract_attribute_value( $html, 'src' )
		);
		$recognized       = $this->is_recognized_woocommerce_markup( $class, $html );
		$primary_image_id = $this->runtime->current_product_primary_image_id();
		$gallery_ids      = $this->runtime->is_single_product_request() ? $this->runtime->current_product_gallery_image_ids() : array();
		$single_product   = $this->runtime->is_single_product_request();

		if ( $single_product && $attachment_id > 0 && $primary_image_id > 0 && $attachment_id === $primary_image_id ) {
			if ( $this->has_primary_class( $class ) ) {
				return self::MATCH_PRIMARY;
			}

			$primary_url = $this->normalize_url( $this->runtime->current_product_primary_image_url() );

			if ( '' !== $primary_url && '' !== $src && $src === $primary_url ) {
				return self::MATCH_PRIMARY;
			}

			return self::MATCH_VARIATION_OR_UNCERTAIN;
		}

		if ( $single_product && $attachment_id > 0 && in_array( $attachment_id, $gallery_ids, true ) ) {
			if ( $this->has_thumbnail_class( $class ) && $this->has_gallery_data_attributes( $html ) ) {
				return self::MATCH_GALLERY_SECONDARY;
			}

			return self::MATCH_VARIATION_OR_UNCERTAIN;
		}

		if ( $recognized ) {
			if ( $single_product && ( $this->has_primary_class( $class ) || $this->has_gallery_data_attributes( $html ) ) ) {
				return self::MATCH_VARIATION_OR_UNCERTAIN;
			}

			return self::MATCH_COMMERCE_THUMBNAIL;
		}

		return self::MATCH_UNRECOGNIZED;
	}

	/**
	 * Whether the current request has a valid primary product image candidate.
	 *
	 * @return bool
	 */
	public function has_current_primary_image(): bool {
		return $this->runtime->is_available()
			&& $this->runtime->is_single_product_request()
			&& $this->runtime->current_product_primary_image_id() > 0;
	}

	/**
	 * Get the current primary image attachment ID.
	 *
	 * @return int
	 */
	public function current_primary_image_id(): int {
		return $this->has_current_primary_image() ? $this->runtime->current_product_primary_image_id() : 0;
	}

	/**
	 * Determine whether one fragment is recognized WooCommerce image markup.
	 *
	 * @param string $class_name Normalized class attribute.
	 * @param string $html Original HTML fragment.
	 * @return bool
	 */
	private function is_recognized_woocommerce_markup( string $class_name, string $html ): bool {
		if (
			false !== strpos( $class_name, 'attachment-woocommerce_' )
			|| false !== strpos( $class_name, 'size-woocommerce_' )
		) {
			return true;
		}

		foreach ( array( 'data-caption', 'data-src', 'data-large_image', 'data-large_image_width', 'data-large_image_height', 'data-thumb' ) as $attribute ) {
			if ( $this->has_attribute( $html, $attribute ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether one class string carries the visible primary image signature.
	 *
	 * @param string $class_name Normalized class string.
	 * @return bool
	 */
	private function has_primary_class( string $class_name ): bool {
		return false !== strpos( $class_name, 'attachment-woocommerce_single' )
			|| false !== strpos( $class_name, 'size-woocommerce_single' );
	}

	/**
	 * Whether one class string carries a known thumbnail/gallery signature.
	 *
	 * @param string $class_name Normalized class string.
	 * @return bool
	 */
	private function has_thumbnail_class( string $class_name ): bool {
		return false !== strpos( $class_name, 'attachment-woocommerce_thumbnail' )
			|| false !== strpos( $class_name, 'size-woocommerce_thumbnail' );
	}

	/**
	 * Determine whether gallery/lightbox data attributes are present.
	 *
	 * @param string $html Markup fragment.
	 * @return bool
	 */
	private function has_gallery_data_attributes( string $html ): bool {
		foreach ( array( 'data-caption', 'data-src', 'data-large_image', 'data-large_image_width', 'data-large_image_height' ) as $attribute ) {
			if ( ! $this->has_attribute( $html, $attribute ) ) {
				return false;
			}
		}

		return true;
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
	 * Normalize one URL for exact comparisons.
	 *
	 * @param string|null $url URL.
	 * @return string
	 */
	private function normalize_url( ?string $url ): string {
		if ( null === $url ) {
			return '';
		}

		return trim( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );
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
	 * Determine whether one raw attribute exists in the fragment.
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
