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
	 * @param string|null          $wrapper_class Optional wrapper class.
	 */
	public function __construct(
		int $attachment_id,
		string $img_html,
		SourceSetBuildResult $source_sets,
		array $format_preference = array( 'avif', 'webp' ),
		?string $wrapper_class = 'hwlio-picture'
	) {
		$this->attachment_id     = max( 0, $attachment_id );
		$this->img_html          = $img_html;
		$this->source_sets       = $source_sets;
		$this->format_preference = $this->normalize_formats( $format_preference );
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
			'wrapper_class'     => $this->wrapper_class,
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

		return array() !== $normalized ? $normalized : array( 'avif', 'webp' );
	}
}
