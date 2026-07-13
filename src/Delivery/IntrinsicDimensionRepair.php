<?php
/**
 * Intrinsic dimension repair.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Repairs missing intrinsic dimensions on certain attachment-backed IMG fragments.
 */
final class IntrinsicDimensionRepair {

	/**
	 * Size resolver.
	 *
	 * @var AttachmentSizeResolver
	 */
	private $resolver;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Create repair service.
	 *
	 * @param AttachmentSizeResolver       $resolver Size resolver.
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 */
	public function __construct( AttachmentSizeResolver $resolver, ImageMarkupAnalyzerInterface $analyzer ) {
		$this->resolver = $resolver;
		$this->analyzer = $analyzer;
	}

	/**
	 * Repair one fallback IMG fragment when dimensions are certain.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $html Fallback HTML.
	 * @param array<string,mixed> $image_meta Attachment image metadata.
	 * @param int|null            $known_width Known runtime width.
	 * @return IntrinsicDimensionRepairResult
	 */
	public function repair( int $attachment_id, string $html, array $image_meta, ?int $known_width = null ): IntrinsicDimensionRepairResult {
		if ( $attachment_id < 1 ) {
			return IntrinsicDimensionRepairResult::unchanged( $html );
		}

		$analysis = $this->analyzer->analyze( $html );

		if ( ! $analysis->is_renderable_img() ) {
			return IntrinsicDimensionRepairResult::unchanged( $html );
		}

		if ( $analysis->has_valid_width() && $analysis->has_valid_height() ) {
			return IntrinsicDimensionRepairResult::unchanged( $html );
		}

		if (
			( $analysis->has_width_attribute() && ! $analysis->has_valid_width() )
			|| ( $analysis->has_height_attribute() && ! $analysis->has_valid_height() )
		) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		$candidate = $this->resolver->resolve_from_analysis( $analysis, $image_meta, $known_width );

		if ( ! is_array( $candidate ) ) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		$target_width  = (int) $candidate['width'];
		$target_height = (int) $candidate['height'];

		if ( $target_width < 1 || $target_height < 1 ) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		$missing_width  = ! $analysis->has_width_attribute();
		$missing_height = ! $analysis->has_height_attribute();

		if ( ! $missing_width && ! $missing_height ) {
			return IntrinsicDimensionRepairResult::unchanged( $html );
		}

		if ( ! $missing_width && $analysis->width() !== $target_width ) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		if ( ! $missing_height && $analysis->height() !== $target_height ) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		$updated = $html;

		if ( $missing_width ) {
			$updated = $this->set_attribute( $updated, 'width', (string) $target_width );
		}

		if ( $missing_height ) {
			$updated = $this->set_attribute( $updated, 'height', (string) $target_height );
		}

		if ( $updated === $html ) {
			return IntrinsicDimensionRepairResult::unchanged(
				$html,
				array( PictureRenderResult::CODE_INTRINSIC_DIMENSIONS_UNCERTAIN )
			);
		}

		return IntrinsicDimensionRepairResult::repaired( $updated );
	}

	/**
	 * Set or add one IMG attribute conservatively.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function set_attribute( string $html, string $attribute, string $value ): string {
		$replacement = ' ' . $attribute . '="' . $this->escape_attr( $value ) . '"';
		$pattern     = sprintf(
			'/(\s+)%s\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
			preg_quote( $attribute, '/' )
		);
		$updated     = preg_replace( $pattern, $replacement, $html, 1, $count );

		if ( ! is_string( $updated ) ) {
			return $html;
		}

		if ( $count > 0 ) {
			return $updated;
		}

		$inserted = preg_replace( '/\s*\/?>$/', $replacement . '$0', $html, 1 );

		return is_string( $inserted ) ? $inserted : $html;
	}

	/**
	 * Escape one generated attribute value.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
