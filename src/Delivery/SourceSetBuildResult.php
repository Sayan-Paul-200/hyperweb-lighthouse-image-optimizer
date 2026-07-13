<?php
/**
 * Source set build result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reports the outcome of one responsive source-set build.
 */
final class SourceSetBuildResult {

	public const CODE_BUILT                      = 'built';
	public const CODE_NO_CANDIDATES              = 'no_candidates';
	public const CODE_INVALID_IMAGE_META         = 'invalid_image_meta';
	public const CODE_MANIFEST_EMPTY             = 'manifest_empty';
	public const CODE_PARTIAL_CANDIDATES_OMITTED = 'partial_candidates_omitted';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Format results keyed by format.
	 *
	 * @var array<string,FormatSourceSet>
	 */
	private $formats;

	/**
	 * Result codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Create result.
	 *
	 * @param int                           $attachment_id Attachment ID.
	 * @param array<string,FormatSourceSet> $formats Format results.
	 * @param string[]                      $codes Codes.
	 */
	public function __construct( int $attachment_id, array $formats, array $codes = array() ) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->formats       = $this->normalize_formats( $formats );
		$this->codes         = $this->normalize_codes( $codes );
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
	 * Get format results.
	 *
	 * @return array<string,FormatSourceSet>
	 */
	public function formats(): array {
		return $this->formats;
	}

	/**
	 * Get a format result.
	 *
	 * @param string $format Format.
	 * @return FormatSourceSet|null
	 */
	public function format( string $format ): ?FormatSourceSet {
		$format = strtolower( trim( $format ) );

		return $this->formats[ $format ] ?? null;
	}

	/**
	 * Get result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Whether a code exists.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( strtolower( trim( $code ) ), $this->codes, true );
	}

	/**
	 * Serialize result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		$formats = array();

		foreach ( $this->formats as $format => $source_set ) {
			$formats[ $format ] = $source_set->to_array();
		}

		return array(
			'attachment_id' => $this->attachment_id,
			'formats'       => $formats,
			'codes'         => $this->codes,
		);
	}

	/**
	 * Normalize format results.
	 *
	 * @param array<string,FormatSourceSet> $formats Formats.
	 * @return array<string,FormatSourceSet>
	 */
	private function normalize_formats( array $formats ): array {
		$normalized = array();

		foreach ( $formats as $format => $source_set ) {
			if ( ! $source_set instanceof FormatSourceSet ) {
				continue;
			}

			$key = is_string( $format ) && '' !== trim( $format )
				? strtolower( trim( $format ) )
				: $source_set->format();

			if ( '' === $key || isset( $normalized[ $key ] ) || ! $source_set->has_sources() ) {
				continue;
			}

			$normalized[ $key ] = $source_set;
		}

		return $normalized;
	}

	/**
	 * Normalize result codes.
	 *
	 * @param string[] $codes Codes.
	 * @return string[]
	 */
	private function normalize_codes( array $codes ): array {
		$normalized = array();

		foreach ( $codes as $code ) {
			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = strtolower( trim( (string) $code ) );
			$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
			$code = trim( $code, '_' );

			if ( '' !== $code && ! in_array( $code, $normalized, true ) ) {
				$normalized[] = $code;
			}
		}

		return $normalized;
	}
}
