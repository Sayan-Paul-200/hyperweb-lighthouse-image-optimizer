<?php
/**
 * Responsive preload manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Emits one opt-in responsive preload tag for an explicitly selected late-discovered critical image.
 */
final class ResponsivePreloadManager implements HookProviderInterface {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Runtime seam.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Critical-image registry.
	 *
	 * @var CriticalImageRegistry
	 */
	private $critical_images;

	/**
	 * Locator.
	 *
	 * @var LateDiscoveredCriticalImageLocator
	 */
	private $locator;

	/**
	 * Intrinsic-dimension repair service.
	 *
	 * @var IntrinsicDimensionRepair
	 */
	private $dimension_repair;

	/**
	 * Source extractor.
	 *
	 * @var AttachmentImageSourceExtractor
	 */
	private $extractor;

	/**
	 * Source-set builder.
	 *
	 * @var SourceSetBuilder
	 */
	private $builder;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Request-local preload registry.
	 *
	 * @var ResponsivePreloadRegistry
	 */
	private $registry;

	/**
	 * Create manager.
	 *
	 * @param SettingsRepositoryInterface        $settings        Settings repository.
	 * @param AttachmentImageRuntimeInterface    $runtime Runtime seam.
	 * @param CriticalImageRegistry              $critical_images Critical-image registry.
	 * @param LateDiscoveredCriticalImageLocator $locator      Locator.
	 * @param IntrinsicDimensionRepair           $dimension_repair Intrinsic-dimension repair.
	 * @param AttachmentImageSourceExtractor     $extractor       Source extractor.
	 * @param SourceSetBuilder                   $builder         Source-set builder.
	 * @param ImageMarkupAnalyzerInterface       $analyzer        Markup analyzer.
	 * @param ResponsivePreloadRegistry          $registry        Request-local preload registry.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		AttachmentImageRuntimeInterface $runtime,
		CriticalImageRegistry $critical_images,
		LateDiscoveredCriticalImageLocator $locator,
		IntrinsicDimensionRepair $dimension_repair,
		AttachmentImageSourceExtractor $extractor,
		SourceSetBuilder $builder,
		ImageMarkupAnalyzerInterface $analyzer,
		ResponsivePreloadRegistry $registry
	) {
		$this->settings         = $settings;
		$this->runtime          = $runtime;
		$this->critical_images  = $critical_images;
		$this->locator          = $locator;
		$this->dimension_repair = $dimension_repair;
		$this->extractor        = $extractor;
		$this->builder          = $builder;
		$this->analyzer         = $analyzer;
		$this->registry         = $registry;
	}

	/**
	 * Register runtime hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'wp_head', array( $this, 'emit_preload_tag' ), 1, 0 );
	}

	/**
	 * Emit one responsive preload tag when the current request qualifies.
	 *
	 * @return void
	 */
	public function emit_preload_tag(): void {
		$result = $this->build_for_current_request();

		if ( ! $result->is_ready() ) {
			return;
		}

		$link = $result->link();

		if ( ! $link instanceof ResponsivePreloadLink || $this->registry->has( $link ) ) {
			return;
		}

		$this->registry->record( $link );
		$html = $link->html();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ResponsivePreloadLink::html() returns fully escaped tag markup.
		echo $html;
	}

	/**
	 * Build one preload result for the current request.
	 *
	 * @return ResponsivePreloadResult
	 */
	public function build_for_current_request(): ResponsivePreloadResult {
		if ( ! $this->settings->responsive_preload_enabled() ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_DISABLED );
		}

		$request_context = $this->runtime->request_context();

		if (
			! empty( $request_context['is_admin'] )
			|| ! empty( $request_context['is_feed'] )
			|| ! empty( $request_context['is_ajax'] )
			|| ! empty( $request_context['is_rest'] )
			|| $this->runtime->current_singular_post_id() < 1
		) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_INELIGIBLE_REQUEST );
		}

		$selection     = $this->critical_images->resolve();
		$attachment_id = $selection->preload_attachment_id();

		if ( null === $attachment_id || ! $selection->should_preload_attachment( $attachment_id ) || ! $this->runtime->attachment_is_image( $attachment_id ) ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_PRELOAD_SELECTION );
		}

		$match = $this->locator->locate( $attachment_id );

		if ( ! $match instanceof LateDiscoveredCriticalImageMatch ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_UNIQUE_MATCH );
		}

		$image_meta      = $this->runtime->attachment_metadata( $attachment_id );
		$normalized_html = $this->dimension_repair->repair( $attachment_id, $match->html(), $image_meta )->html();
		$analysis        = $this->analyzer->analyze( $normalized_html );

		if ( ! $analysis->is_renderable_img() || null === $analysis->sizes() || '' === trim( $analysis->sizes() ) ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_MISSING_SIZES );
		}

		$extraction = $this->extractor->extract( $normalized_html );

		if ( ! $extraction->has_sources() ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_MATCHING_SOURCE );
		}

		$source_sets = $this->builder->build(
			new SourceSetBuildRequest( $attachment_id, $extraction->sources(), $image_meta )
		);
		$format_set  = $this->preferred_format_source_set( $source_sets );

		if ( ! $format_set instanceof FormatSourceSet ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_SOURCE_SETS );
		}

		$original_source = $this->match_original_source( (string) $analysis->src(), $extraction->sources() );

		if ( ! is_array( $original_source ) ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_MATCHING_SOURCE );
		}

		$width         = (int) $original_source['value'];
		$modern_source = $format_set->sources()[ $width ] ?? null;

		if ( ! is_array( $modern_source ) || empty( $modern_source['url'] ) ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_NO_MATCHING_SOURCE );
		}

		$link = new ResponsivePreloadLink(
			$attachment_id,
			$format_set->format(),
			(string) $modern_source['url'],
			$format_set->mime(),
			$format_set->srcset(),
			(string) $analysis->sizes()
		);

		if ( $this->registry->has( $link ) ) {
			return ResponsivePreloadResult::noop( ResponsivePreloadResult::CODE_ALREADY_EMITTED );
		}

		return ResponsivePreloadResult::ready( $link );
	}

	/**
	 * Select one preferred modern format source set.
	 *
	 * @param SourceSetBuildResult $source_sets Source sets.
	 * @return FormatSourceSet|null
	 */
	private function preferred_format_source_set( SourceSetBuildResult $source_sets ): ?FormatSourceSet {
		foreach ( $this->settings->format_preference() as $format ) {
			$format_set = $source_sets->format( $format );

			if ( $format_set instanceof FormatSourceSet && $format_set->has_sources() ) {
				return $format_set;
			}
		}

		return null;
	}

	/**
	 * Match the fallback src against one original source candidate.
	 *
	 * @param string                         $src     Fallback src.
	 * @param array<int,array<string,mixed>> $sources Extracted source candidates.
	 * @return array<string,mixed>|null
	 */
	private function match_original_source( string $src, array $sources ): ?array {
		if ( '' === trim( $src ) ) {
			return null;
		}

		$matches = array();

		foreach ( $sources as $source ) {
			if (
				is_array( $source )
				&& isset( $source['url'] )
				&& is_string( $source['url'] )
				&& $this->urls_match( $src, $source['url'] )
			) {
				$matches[] = $source;
			}
		}

		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/**
	 * Compare two source URLs conservatively.
	 *
	 * @param string $left Left URL.
	 * @param string $right Right URL.
	 * @return bool
	 */
	private function urls_match( string $left, string $right ): bool {
		$left  = trim( $left );
		$right = trim( $right );

		if ( '' === $left || '' === $right ) {
			return false;
		}

		if ( $left === $right ) {
			return true;
		}

		$left_path  = $this->url_path( $left );
		$right_path = $this->url_path( $right );

		return '' !== $left_path && $left_path === $right_path;
	}

	/**
	 * Get one normalized URL path.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function url_path( string $url ): string {
		if ( function_exists( 'wp_parse_url' ) ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Fallback for non-WordPress test/runtime contexts.
			$path = parse_url( $url, PHP_URL_PATH );
		}

		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}

		return str_replace( '\\', '/', rawurldecode( trim( $path ) ) );
	}
}
