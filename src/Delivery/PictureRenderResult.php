<?php
/**
 * Picture render result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reports the outcome of one picture-rendering request.
 */
final class PictureRenderResult {

	public const CODE_RENDERED                = 'rendered';
	public const CODE_ALREADY_PICTURE         = 'already_picture';
	public const CODE_INVALID_MARKUP          = 'invalid_markup';
	public const CODE_NO_SOURCES              = 'no_sources';
	public const CODE_PARTIAL_SOURCES_OMITTED = 'partial_sources_omitted';

	/**
	 * Whether a picture was rendered.
	 *
	 * @var bool
	 */
	private $rendered;

	/**
	 * Final HTML.
	 *
	 * @var string
	 */
	private $html;

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Rendered formats.
	 *
	 * @var string[]
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
	 * @param bool     $rendered Whether a picture was rendered.
	 * @param string   $html Final HTML.
	 * @param int      $attachment_id Attachment ID.
	 * @param string[] $formats Rendered formats.
	 * @param string[] $codes Result codes.
	 */
	public function __construct( bool $rendered, string $html, int $attachment_id, array $formats, array $codes ) {
		$this->rendered      = $rendered;
		$this->html          = $html;
		$this->attachment_id = max( 0, $attachment_id );
		$this->formats       = $this->normalize_formats( $formats );
		$this->codes         = $this->normalize_codes( $codes );
	}

	/**
	 * Build a rendered result.
	 *
	 * @param PictureRenderRequest $request Request.
	 * @param string               $html Rendered HTML.
	 * @param string[]             $formats Rendered formats.
	 * @param string[]             $codes Additional result codes.
	 * @return self
	 */
	public static function rendered( PictureRenderRequest $request, string $html, array $formats, array $codes = array() ): self {
		array_unshift( $codes, self::CODE_RENDERED );

		return new self( true, $html, $request->attachment_id(), $formats, $codes );
	}

	/**
	 * Build an unchanged result.
	 *
	 * @param PictureRenderRequest $request Request.
	 * @param string[]             $codes Result codes.
	 * @return self
	 */
	public static function unchanged( PictureRenderRequest $request, array $codes ): self {
		return new self( false, $request->img_html(), $request->attachment_id(), array(), $codes );
	}

	/**
	 * Whether a picture was rendered.
	 *
	 * @return bool
	 */
	public function is_rendered(): bool {
		return $this->rendered;
	}

	/**
	 * Get final HTML.
	 *
	 * @return string
	 */
	public function html(): string {
		return $this->html;
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
	 * Get rendered formats.
	 *
	 * @return string[]
	 */
	public function formats(): array {
		return $this->formats;
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
		return array(
			'rendered'      => $this->rendered,
			'html'          => $this->html,
			'attachment_id' => $this->attachment_id,
			'formats'       => $this->formats,
			'codes'         => $this->codes,
		);
	}

	/**
	 * Normalize a list of formats.
	 *
	 * @param string[] $formats Formats.
	 * @return string[]
	 */
	private function normalize_formats( array $formats ): array {
		$normalized = array();

		foreach ( $formats as $format ) {
			if ( ! is_scalar( $format ) ) {
				continue;
			}

			$format = strtolower( trim( (string) $format ) );

			if ( '' !== $format && ! in_array( $format, $normalized, true ) ) {
				$normalized[] = $format;
			}
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
