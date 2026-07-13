<?php
/**
 * Format source set value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one modern-format responsive source set.
 */
final class FormatSourceSet {

	/**
	 * Format.
	 *
	 * @var string
	 */
	private $format;

	/**
	 * MIME type.
	 *
	 * @var string
	 */
	private $mime;

	/**
	 * Width-keyed sources.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $sources;

	/**
	 * Serialized srcset string.
	 *
	 * @var string
	 */
	private $srcset;

	/**
	 * Create source set.
	 *
	 * @param string                  $format Format.
	 * @param string                  $mime MIME type.
	 * @param array<int|string,mixed> $sources Sources.
	 * @param string|null             $srcset Optional prebuilt srcset.
	 */
	public function __construct( string $format, string $mime, array $sources, ?string $srcset = null ) {
		$this->format  = strtolower( trim( $format ) );
		$this->mime    = strtolower( trim( $mime ) );
		$this->sources = $this->normalize_sources( $sources );
		$this->srcset  = null === $srcset ? $this->build_srcset( $this->sources ) : trim( $srcset );
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
	 * Get MIME type.
	 *
	 * @return string
	 */
	public function mime(): string {
		return $this->mime;
	}

	/**
	 * Get width-keyed sources.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function sources(): array {
		return $this->sources;
	}

	/**
	 * Get serialized srcset.
	 *
	 * @return string
	 */
	public function srcset(): string {
		return $this->srcset;
	}

	/**
	 * Whether any sources exist.
	 *
	 * @return bool
	 */
	public function has_sources(): bool {
		return array() !== $this->sources;
	}

	/**
	 * Serialize source set.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'format'  => $this->format,
			'mime'    => $this->mime,
			'sources' => $this->sources,
			'srcset'  => $this->srcset,
		);
	}

	/**
	 * Normalize source candidates.
	 *
	 * @param array<int|string,mixed> $sources Sources.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_sources( array $sources ): array {
		$normalized = array();

		foreach ( $sources as $source ) {
			if ( ! is_array( $source ) ) {
				continue;
			}

			$url        = isset( $source['url'] ) && is_scalar( $source['url'] ) ? trim( (string) $source['url'] ) : '';
			$descriptor = isset( $source['descriptor'] ) && is_scalar( $source['descriptor'] ) ? trim( (string) $source['descriptor'] ) : '';
			$value      = isset( $source['value'] ) && is_numeric( $source['value'] ) ? (int) $source['value'] : 0;

			if ( '' === $url || 'w' !== $descriptor || $value < 1 || isset( $normalized[ $value ] ) ) {
				continue;
			}

			$normalized[ $value ] = array(
				'url'        => $url,
				'descriptor' => 'w',
				'value'      => $value,
			);
		}

		return $normalized;
	}

	/**
	 * Build a serialized srcset string.
	 *
	 * @param array<int,array<string,mixed>> $sources Sources.
	 * @return string
	 */
	private function build_srcset( array $sources ): string {
		$parts = array();

		foreach ( $sources as $source ) {
			$parts[] = str_replace( ' ', '%20', (string) $source['url'] ) . ' ' . (int) $source['value'] . 'w';
		}

		return implode( ', ', $parts );
	}
}
