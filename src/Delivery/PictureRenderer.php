<?php
/**
 * Picture renderer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Renders safe modern-format picture markup around an existing image node.
 */
final class PictureRenderer {

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Build a WordPress-backed renderer.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( new WordPressImageMarkupAnalyzer() );
	}

	/**
	 * Create renderer.
	 *
	 * @param ImageMarkupAnalyzerInterface $analyzer Markup analyzer.
	 */
	public function __construct( ImageMarkupAnalyzerInterface $analyzer ) {
		$this->analyzer = $analyzer;
	}

	/**
	 * Render picture markup around an existing image tag.
	 *
	 * @param PictureRenderRequest $request Render request.
	 * @return PictureRenderResult
	 */
	public function render( PictureRenderRequest $request ): PictureRenderResult {
		$analysis = $this->analyzer->analyze( $request->img_html() );

		if ( $analysis->is_picture() ) {
			return PictureRenderResult::unchanged( $request, array( PictureRenderResult::CODE_ALREADY_PICTURE ) );
		}

		if ( ! $analysis->is_renderable_img() ) {
			return PictureRenderResult::unchanged( $request, array( PictureRenderResult::CODE_INVALID_MARKUP ) );
		}

		if ( $analysis->has_loading_priority_conflict() ) {
			return PictureRenderResult::unchanged( $request, array( PictureRenderResult::CODE_CONFLICTING_LOADING_ATTRIBUTES ) );
		}

		$available_formats = $this->ordered_formats( $request->source_sets(), $request->format_preference() );
		$codes             = $this->base_codes( $request->source_sets() );

		if ( array() === $available_formats ) {
			array_unshift( $codes, PictureRenderResult::CODE_NO_SOURCES );

			return PictureRenderResult::unchanged( $request, $codes );
		}

		$filtered_formats = $this->filtered_formats( $request, $available_formats );

		if ( count( $filtered_formats ) < count( $available_formats ) && ! in_array( PictureRenderResult::CODE_PARTIAL_SOURCES_OMITTED, $codes, true ) ) {
			$codes[] = PictureRenderResult::CODE_PARTIAL_SOURCES_OMITTED;
		}

		if ( array() === $filtered_formats ) {
			array_unshift( $codes, PictureRenderResult::CODE_NO_SOURCES );

			return PictureRenderResult::unchanged( $request, $codes );
		}

		return PictureRenderResult::rendered(
			$request,
			$this->picture_markup( $request, $analysis, $filtered_formats ),
			array_keys( $filtered_formats ),
			$codes
		);
	}

	/**
	 * Get ordered format source sets for a request.
	 *
	 * @param SourceSetBuildResult $source_sets Source sets.
	 * @param string[]             $preference Preferred format order.
	 * @return array<string,FormatSourceSet>
	 */
	private function ordered_formats( SourceSetBuildResult $source_sets, array $preference ): array {
		$ordered = array();

		foreach ( $preference as $format ) {
			$source_set = $source_sets->format( $format );

			if ( $source_set instanceof FormatSourceSet ) {
				$ordered[ $source_set->format() ] = $source_set;
			}
		}

		return $ordered;
	}

	/**
	 * Build base result codes from source-set status.
	 *
	 * @param SourceSetBuildResult $source_sets Source sets.
	 * @return string[]
	 */
	private function base_codes( SourceSetBuildResult $source_sets ): array {
		$codes = array();

		if ( $source_sets->has_code( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED ) ) {
			$codes[] = PictureRenderResult::CODE_PARTIAL_SOURCES_OMITTED;
		}

		return $codes;
	}

	/**
	 * Allow filters to rewrite picture sources conservatively.
	 *
	 * @param PictureRenderRequest          $request Render request.
	 * @param array<string,FormatSourceSet> $formats Ordered formats.
	 * @return array<string,FormatSourceSet>
	 */
	private function filtered_formats( PictureRenderRequest $request, array $formats ): array {
		$payload = array();

		foreach ( $formats as $format => $source_set ) {
			$payload[ $format ] = $source_set->to_array();
		}

		if ( function_exists( 'apply_filters' ) ) {
			$payload = \apply_filters(
				'hwlio_picture_sources',
				$payload,
				$request->attachment_id(),
				$request->img_html(),
				$request->format_preference()
			);
		}

		if ( ! is_array( $payload ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $request->format_preference() as $format ) {
			if ( ! isset( $payload[ $format ] ) || ! is_array( $payload[ $format ] ) ) {
				continue;
			}

			$entry = $payload[ $format ];

			if (
				! isset( $entry['format'], $entry['mime'], $entry['sources'] )
				|| ! is_scalar( $entry['format'] )
				|| ! is_scalar( $entry['mime'] )
				|| ! is_array( $entry['sources'] )
			) {
				continue;
			}

			$source_set = new FormatSourceSet(
				(string) $entry['format'],
				(string) $entry['mime'],
				$entry['sources'],
				isset( $entry['srcset'] ) && is_scalar( $entry['srcset'] ) ? (string) $entry['srcset'] : null
			);

			if ( $source_set->has_sources() && $source_set->format() === $format ) {
				$normalized[ $format ] = $source_set;
			}
		}

		return $normalized;
	}

	/**
	 * Build final picture markup.
	 *
	 * @param PictureRenderRequest          $request Render request.
	 * @param ImageMarkupAnalysis           $analysis Markup analysis.
	 * @param array<string,FormatSourceSet> $formats Filtered formats.
	 * @return string
	 */
	private function picture_markup( PictureRenderRequest $request, ImageMarkupAnalysis $analysis, array $formats ): string {
		$attributes = '';
		$class      = $request->wrapper_class();

		if ( '' !== $class ) {
			$attributes = ' class="' . $this->escape_attr( $class ) . '"';
		}

		$html = '<picture' . $attributes . '>';

		foreach ( $formats as $source_set ) {
			$html .= '<source type="' . $this->escape_attr( $source_set->mime() ) . '" srcset="' . $this->escape_attr( $source_set->srcset() ) . '"';

			if ( null !== $analysis->sizes() && '' !== $analysis->sizes() ) {
				$html .= ' sizes="' . $this->escape_attr( $analysis->sizes() ) . '"';
			}

			$html .= '>';
		}

		$html .= $request->img_html();
		$html .= '</picture>';

		return $html;
	}

	/**
	 * Escape one generated attribute.
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
