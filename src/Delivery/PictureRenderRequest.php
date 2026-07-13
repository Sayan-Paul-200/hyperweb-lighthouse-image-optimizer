<?php
/**
 * Picture render request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one picture-rendering request.
 */
final class PictureRenderRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Original image HTML.
	 *
	 * @var string
	 */
	private $img_html;

	/**
	 * Built source sets.
	 *
	 * @var SourceSetBuildResult
	 */
	private $source_sets;

	/**
	 * Preferred format order.
	 *
	 * @var string[]
	 */
	private $format_preference;

	/**
	 * Preflight result codes gathered before picture rendering.
	 *
	 * @var string[]
	 */
	private $preflight_codes;

	/**
	 * Optional wrapper class.
	 *
	 * @var string
	 */
	private $wrapper_class;

	/**
	 * Create request.
	 *
	 * @param int                  $attachment_id Attachment ID.
	 * @param string               $img_html Original image HTML.
	 * @param SourceSetBuildResult $source_sets Built source sets.
	 * @param string[]             $format_preference Preferred format order.
	 * @param string[]             $preflight_codes Preflight result codes.
	 * @param string|null          $wrapper_class Optional wrapper class.
	 */
	public function __construct(
		int $attachment_id,
		string $img_html,
		SourceSetBuildResult $source_sets,
		array $format_preference = array( 'avif', 'webp' ),
		array $preflight_codes = array(),
		?string $wrapper_class = 'hwlio-picture'
	) {
		$this->attachment_id     = max( 0, $attachment_id );
		$this->img_html          = $img_html;
		$this->source_sets       = $source_sets;
		$this->format_preference = $this->normalize_list( $format_preference, array( 'avif', 'webp' ) );
		$this->preflight_codes   = $this->normalize_list( $preflight_codes );
		$this->wrapper_class     = null === $wrapper_class ? 'hwlio-picture' : trim( $wrapper_class );
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
	 * Get original image HTML.
	 *
	 * @return string
	 */
	public function img_html(): string {
		return $this->img_html;
	}

	/**
	 * Get source sets.
	 *
	 * @return SourceSetBuildResult
	 */
	public function source_sets(): SourceSetBuildResult {
		return $this->source_sets;
	}

	/**
	 * Get preferred format order.
	 *
	 * @return string[]
	 */
	public function format_preference(): array {
		return $this->format_preference;
	}

	/**
	 * Get wrapper class.
	 *
	 * @return string
	 */
	public function wrapper_class(): string {
		return $this->wrapper_class;
	}

	/**
	 * Get preflight result codes.
	 *
	 * @return string[]
	 */
	public function preflight_codes(): array {
		return $this->preflight_codes;
	}

	/**
	 * Serialize request.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id'     => $this->attachment_id,
			'img_html'          => $this->img_html,
			'source_sets'       => $this->source_sets->to_array(),
			'format_preference' => $this->format_preference,
			'preflight_codes'   => $this->preflight_codes,
			'wrapper_class'     => $this->wrapper_class,
		);
	}

	/**
	 * Normalize a list of formats.
	 *
	 * @param string[] $values Values.
	 * @param string[] $fallback Fallback values.
	 * @return string[]
	 */
	private function normalize_list( array $values, array $fallback = array() ): array {
		$normalized = array();

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$value = strtolower( trim( (string) $value ) );

			if ( '' !== $value && ! in_array( $value, $normalized, true ) ) {
				$normalized[] = $value;
			}
		}

		return array() !== $normalized ? $normalized : $fallback;
	}
}
