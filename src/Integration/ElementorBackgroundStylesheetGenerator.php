<?php
/**
 * Elementor background companion stylesheet generator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Builds conservative companion CSS from structured Elementor background mappings.
 */
final class ElementorBackgroundStylesheetGenerator {

	/**
	 * Generator schema/version.
	 *
	 * @var string
	 */
	private const SCHEMA_VERSION = '1';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Shared delivery-plan builder.
	 *
	 * @var ElementorBackgroundDeliveryPlanBuilder
	 */
	private $plan_builder;

	/**
	 * Create generator.
	 *
	 * @param ElementorBackgroundDiscovery $discovery Discovery service.
	 * @param DerivativeRepository         $repository Derivative repository.
	 * @param DerivativeUrlResolver        $resolver Derivative URL resolver.
	 * @param SettingsRepositoryInterface  $settings Settings repository.
	 * @param DerivativeManifestSanitizer  $sanitizer Path sanitizer.
	 * @param callable|null                $uploads_provider Optional uploads-data provider for tests.
	 */
	public function __construct(
		ElementorBackgroundDiscovery $discovery,
		DerivativeRepository $repository,
		DerivativeUrlResolver $resolver,
		SettingsRepositoryInterface $settings,
		DerivativeManifestSanitizer $sanitizer,
		?callable $uploads_provider = null
	) {
		$this->settings     = $settings;
		$this->plan_builder = new ElementorBackgroundDeliveryPlanBuilder(
			$discovery,
			$repository,
			$resolver,
			$settings,
			$sanitizer,
			$uploads_provider
		);
	}

	/**
	 * Generate companion CSS for one Elementor document.
	 *
	 * @param int                                   $document_id Document ID.
	 * @param ElementorBackgroundBreakpointMap|null $breakpoint_map Breakpoint map when reliable.
	 * @return ElementorBackgroundStylesheetResult
	 */
	public function generate( int $document_id, ?ElementorBackgroundBreakpointMap $breakpoint_map = null ): ElementorBackgroundStylesheetResult {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return ElementorBackgroundStylesheetResult::noop(
				0,
				ElementorBackgroundStylesheetResult::CODE_DOCUMENT_UNAVAILABLE
			);
		}

		$plans = $this->plan_builder->build( $document_id, $breakpoint_map );

		if ( ! $plans->has_supported_sources() ) {
			return ElementorBackgroundStylesheetResult::noop(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_NO_SUPPORTED_SOURCES
			);
		}

		if ( $plans->breakpoint_map_missing() ) {
			return ElementorBackgroundStylesheetResult::noop(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_BREAKPOINT_MAP_MISSING
			);
		}

		$blocks         = array();
		$signature_data = array();

		foreach ( $plans->plans() as $plan ) {
			$plan_blocks = $this->plan_blocks( $plan );

			if ( array() === $plan_blocks['blocks'] ) {
				continue;
			}

			$blocks         = array_merge( $blocks, $plan_blocks['blocks'] );
			$signature_data = array_merge( $signature_data, $plan_blocks['signature'] );
		}

		if ( array() === $blocks ) {
			return ElementorBackgroundStylesheetResult::noop(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_NO_SAFE_RULES
			);
		}

		$format_preference = array_values( $this->settings->format_preference() );
		$signature         = hash(
			'sha256',
			$this->json_encode(
				array(
					'document_id'       => $document_id,
					'generator_version' => self::SCHEMA_VERSION,
					'format_preference' => $format_preference,
					'breakpoint_map'    => $breakpoint_map instanceof ElementorBackgroundBreakpointMap ? $breakpoint_map->to_array() : null,
					'normalized_rules'  => $signature_data,
				)
			)
		);
		$css               = $this->stylesheet_css( $document_id, $signature, $blocks );

		return ElementorBackgroundStylesheetResult::ready(
			$document_id,
			ElementorBackgroundStylesheetResult::CODE_RULES_GENERATED,
			true,
			count( $blocks ),
			$signature,
			$css
		);
	}

	/**
	 * Build one plan's CSS blocks and signature payload.
	 *
	 * @param ElementorBackgroundDeliveryPlan $plan Delivery plan.
	 * @return array{blocks: array<int,string>, signature: array<int,array<string,mixed>>}
	 */
	private function plan_blocks( ElementorBackgroundDeliveryPlan $plan ): array {
		$blocks         = array();
		$signature_data = array();

		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			$variant = $plan->variant( $device );

			if ( ! $variant instanceof ElementorBackgroundDeliveryVariant || ! $variant->has_format_candidates() ) {
				continue;
			}

			$image_set = $this->image_set_value(
				$variant->format_candidates(),
				$variant->original_url(),
				$variant->original_mime()
			);

			if ( '' === $image_set ) {
				continue;
			}

			if ( null === $variant->media_query() ) {
				$blocks[] = $this->selector_block( $plan->selector(), $image_set );
			} else {
				$blocks[] = $this->media_query_block( $variant->media_query(), $plan->selector(), $image_set );
			}

			$signature_data[] = array(
				'key'      => $plan->key(),
				'selector' => $plan->selector(),
				'device'   => $device,
				'query'    => $variant->media_query(),
				'formats'  => $variant->format_candidates(),
				'original' => $variant->original_url(),
			);
		}

		return array(
			'blocks'    => $blocks,
			'signature' => $signature_data,
		);
	}

	/**
	 * Build one CSS image-set value.
	 *
	 * @param array<int,array<string,string>> $formats Preferred modern format URLs.
	 * @param string                          $original_url Original local source URL.
	 * @param string                          $original_mime Original source MIME when known.
	 * @return string
	 */
	private function image_set_value( array $formats, string $original_url, string $original_mime ): string {
		$candidates = array();

		foreach ( $formats as $format ) {
			if ( empty( $format['url'] ) || empty( $format['mime'] ) ) {
				continue;
			}

			$candidates[] = sprintf(
				'url("%s") type("%s") 1x',
				$this->escape_css_url( $format['url'] ),
				$this->escape_css_value( $format['mime'] )
			);
		}

		if ( array() === $candidates ) {
			return '';
		}

		$original_candidate = sprintf(
			'url("%s")%s 1x',
			$this->escape_css_url( $original_url ),
			'' !== $original_mime ? sprintf( ' type("%s")', $this->escape_css_value( $original_mime ) ) : ''
		);

		$candidates[] = $original_candidate;

		return 'image-set(' . implode( ', ', $candidates ) . ')';
	}

	/**
	 * Build one base selector block.
	 *
	 * @param string $selector Selector.
	 * @param string $image_set Image-set value.
	 * @return string
	 */
	private function selector_block( string $selector, string $image_set ): string {
		return $selector . ' {' . "\n"
			. '	background-image: ' . $image_set . ';' . "\n"
			. '}';
	}

	/**
	 * Build one media-query-scoped selector block.
	 *
	 * @param string $query Media query.
	 * @param string $selector Selector.
	 * @param string $image_set Image-set value.
	 * @return string
	 */
	private function media_query_block( string $query, string $selector, string $image_set ): string {
		return '@media ' . $query . ' {' . "\n"
			. '	' . str_replace( "\n", "\n\t", $this->selector_block( $selector, $image_set ) ) . "\n"
			. '}';
	}

	/**
	 * Build final stylesheet CSS.
	 *
	 * @param int      $document_id Document ID.
	 * @param string   $signature Signature.
	 * @param string[] $blocks CSS blocks.
	 * @return string
	 */
	private function stylesheet_css( int $document_id, string $signature, array $blocks ): string {
		return sprintf(
			"/* HYLIO Elementor background companion | document:%d | schema:%s | signature:%s */\n@supports (background-image: image-set(url(\"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==\") 1x)) {\n%s\n}\n",
			$document_id,
			self::SCHEMA_VERSION,
			$signature,
			implode( "\n", array_map( array( $this, 'indent_block' ), $blocks ) )
		);
	}

	/**
	 * Indent one CSS block.
	 *
	 * @param string $block CSS block.
	 * @return string
	 */
	private function indent_block( string $block ): string {
		return "\t" . str_replace( "\n", "\n\t", $block );
	}

	/**
	 * Escape one CSS URL.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private function escape_css_url( string $url ): string {
		return str_replace(
			array( '\\', '"' ),
			array( '\\\\', '\\"' ),
			$url
		);
	}

	/**
	 * Escape one CSS value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function escape_css_value( string $value ): string {
		return str_replace(
			array( '\\', '"' ),
			array( '\\\\', '\\"' ),
			$value
		);
	}

	/**
	 * JSON-encode data deterministically.
	 *
	 * @param mixed $value Value to encode.
	 * @return string
	 */
	private function json_encode( $value ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			$json = \wp_json_encode( $value );

			if ( is_string( $json ) ) {
				return $json;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for non-WordPress unit contexts when wp_json_encode() is unavailable.
		$json = json_encode( $value );

		return is_string( $json ) ? $json : '';
	}
}
