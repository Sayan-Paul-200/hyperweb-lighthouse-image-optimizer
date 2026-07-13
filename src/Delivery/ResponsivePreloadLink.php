<?php
/**
 * Responsive preload link.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one deduplicated responsive preload tag payload.
 */
final class ResponsivePreloadLink implements PreloadLinkInterface {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

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
	 * Image srcset.
	 *
	 * @var string
	 */
	private $imagesrcset;

	/**
	 * Image sizes.
	 *
	 * @var string
	 */
	private $imagesizes;

	/**
	 * Create preload link.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $format Format.
	 * @param string $href Href.
	 * @param string $mime MIME type.
	 * @param string $imagesrcset Image srcset.
	 * @param string $imagesizes Image sizes.
	 */
	public function __construct( int $attachment_id, string $format, string $href, string $mime, string $imagesrcset, string $imagesizes ) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->format        = strtolower( trim( $format ) );
		$this->href          = trim( $href );
		$this->mime          = strtolower( trim( $mime ) );
		$this->imagesrcset   = trim( $imagesrcset );
		$this->imagesizes    = trim( $imagesizes );
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get format.
	 *
	 * @return string
	 */
	public function format(): string {
		return $this->format;
	}

	/**
	 * Get href.
	 *
	 * @return string
	 */
	public function href(): string {
		return $this->href;
	}

	/**
	 * Get MIME type.
	 *
	 * @return string
	 */
	public function mime(): string {
		return $this->mime;
	}

	/**
	 * Get responsive srcset.
	 *
	 * @return string
	 */
	public function imagesrcset(): string {
		return $this->imagesrcset;
	}

	/**
	 * Get responsive sizes.
	 *
	 * @return string
	 */
	public function imagesizes(): string {
		return $this->imagesizes;
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
					$this->attachment_id,
					$this->format,
					$this->href,
					$this->imagesrcset,
					$this->imagesizes,
				)
			)
		);
	}

	/**
	 * Render HTML.
	 *
	 * @return string
	 */
	public function html(): string {
		return sprintf(
			'<link rel="preload" as="image" href="%s" type="%s" imagesrcset="%s" imagesizes="%s">',
			$this->escape_url( $this->href ),
			$this->escape_attr( $this->mime ),
			$this->escape_attr( $this->imagesrcset ),
			$this->escape_attr( $this->imagesizes )
		);
	}

	/**
	 * Serialize link.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'format'        => $this->format,
			'href'          => $this->href,
			'mime'          => $this->mime,
			'imagesrcset'   => $this->imagesrcset,
			'imagesizes'    => $this->imagesizes,
			'key'           => $this->key(),
		);
	}

	/**
	 * Escape one attribute value.
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
	 * Escape one URL value.
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
