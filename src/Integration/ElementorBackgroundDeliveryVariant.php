<?php
/**
 * Elementor background delivery variant.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries one safe device-scoped background delivery variant.
 */
final class ElementorBackgroundDeliveryVariant {

	/**
	 * Device scope.
	 *
	 * @var string
	 */
	private $device;

	/**
	 * Original local source URL.
	 *
	 * @var string
	 */
	private $original_url;

	/**
	 * Original source MIME.
	 *
	 * @var string
	 */
	private $original_mime;

	/**
	 * Preferred modern format candidates.
	 *
	 * @var array<int,array<string,string>>
	 */
	private $format_candidates;

	/**
	 * Optional media query.
	 *
	 * @var string|null
	 */
	private $media_query;

	/**
	 * Create variant.
	 *
	 * @param string                          $device Device scope.
	 * @param string                          $original_url Original local source URL.
	 * @param string                          $original_mime Original source MIME.
	 * @param array<int,array<string,string>> $format_candidates Preferred format candidates.
	 * @param string|null                     $media_query Optional media query.
	 */
	public function __construct(
		string $device,
		string $original_url,
		string $original_mime,
		array $format_candidates,
		?string $media_query = null
	) {
		$this->device            = in_array( $device, array( 'desktop', 'tablet', 'mobile' ), true ) ? $device : 'desktop';
		$this->original_url      = trim( $original_url );
		$this->original_mime     = strtolower( trim( $original_mime ) );
		$this->format_candidates = $this->normalize_candidates( $format_candidates );
		$this->media_query       = $this->normalize_optional_string( $media_query );
	}

	/**
	 * Get device scope.
	 *
	 * @return string
	 */
	public function device(): string {
		return $this->device;
	}

	/**
	 * Get original URL.
	 *
	 * @return string
	 */
	public function original_url(): string {
		return $this->original_url;
	}

	/**
	 * Get original MIME.
	 *
	 * @return string
	 */
	public function original_mime(): string {
		return $this->original_mime;
	}

	/**
	 * Get preferred format candidates.
	 *
	 * @return array<int,array<string,string>>
	 */
	public function format_candidates(): array {
		return $this->format_candidates;
	}

	/**
	 * Get the highest-preference ready modern candidate when present.
	 *
	 * @return array<string,string>|null
	 */
	public function preferred_candidate(): ?array {
		return isset( $this->format_candidates[0] ) ? $this->format_candidates[0] : null;
	}

	/**
	 * Get media query when present.
	 *
	 * @return string|null
	 */
	public function media_query(): ?string {
		return $this->media_query;
	}

	/**
	 * Whether the variant has at least one modern format candidate.
	 *
	 * @return bool
	 */
	public function has_format_candidates(): bool {
		return array() !== $this->format_candidates;
	}

	/**
	 * Serialize the variant.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'device'            => $this->device,
			'original_url'      => $this->original_url,
			'original_mime'     => $this->original_mime,
			'format_candidates' => $this->format_candidates,
			'media_query'       => $this->media_query,
		);
	}

	/**
	 * Normalize format candidates.
	 *
	 * @param array<int,array<string,string>> $candidates Raw candidates.
	 * @return array<int,array<string,string>>
	 */
	private function normalize_candidates( array $candidates ): array {
		$normalized = array();

		foreach ( $candidates as $candidate ) {
			if (
				! is_array( $candidate )
				|| empty( $candidate['format'] )
				|| empty( $candidate['mime'] )
				|| empty( $candidate['url'] )
			) {
				continue;
			}

			$normalized[] = array(
				'format' => strtolower( trim( (string) $candidate['format'] ) ),
				'mime'   => strtolower( trim( (string) $candidate['mime'] ) ),
				'url'    => trim( (string) $candidate['url'] ),
			);
		}

		return array_values( $normalized );
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
}
