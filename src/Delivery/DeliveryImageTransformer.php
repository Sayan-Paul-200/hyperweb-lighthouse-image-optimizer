<?php
/**
 * Shared delivery image transformer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Runs one attachment-backed IMG fragment through the modern delivery pipeline.
 */
final class DeliveryImageTransformer {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Eligibility service.
	 *
	 * @var MarkupEligibility
	 */
	private $eligibility;

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
	 * Picture renderer.
	 *
	 * @var PictureRenderer
	 */
	private $renderer;

	/**
	 * Request-local transformed markup registry.
	 *
	 * @var TransformedMarkupRegistry
	 */
	private $registry;

	/**
	 * Intrinsic dimension repair service.
	 *
	 * @var IntrinsicDimensionRepair
	 */
	private $dimension_repair;

	/**
	 * Loading attribute manager.
	 *
	 * @var LoadingAttributeManager
	 */
	private $loading_attributes;

	/**
	 * Create transformer.
	 *
	 * @param SettingsRepositoryInterface    $settings Settings repository.
	 * @param MarkupEligibility              $eligibility Eligibility service.
	 * @param AttachmentImageSourceExtractor $extractor Source extractor.
	 * @param SourceSetBuilder               $builder Source-set builder.
	 * @param PictureRenderer                $renderer Picture renderer.
	 * @param TransformedMarkupRegistry      $registry Request-local registry.
	 * @param IntrinsicDimensionRepair       $dimension_repair Intrinsic dimension repair.
	 * @param LoadingAttributeManager        $loading_attributes Loading attribute manager.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		MarkupEligibility $eligibility,
		AttachmentImageSourceExtractor $extractor,
		SourceSetBuilder $builder,
		PictureRenderer $renderer,
		TransformedMarkupRegistry $registry,
		IntrinsicDimensionRepair $dimension_repair,
		LoadingAttributeManager $loading_attributes
	) {
		$this->settings           = $settings;
		$this->eligibility        = $eligibility;
		$this->extractor          = $extractor;
		$this->builder            = $builder;
		$this->renderer           = $renderer;
		$this->registry           = $registry;
		$this->dimension_repair   = $dimension_repair;
		$this->loading_attributes = $loading_attributes;
	}

	/**
	 * Transform one attachment-backed image fragment through the shared delivery pipeline.
	 *
	 * @param string              $html Image HTML.
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $context Shared delivery context.
	 * @param int|null            $known_width Known width hint.
	 * @return string
	 */
	public function transform( string $html, int $attachment_id, array $context, ?int $known_width = null ): string {
		try {
			if ( ! $this->eligibility->delivery_enabled( $attachment_id, $html, $context ) ) {
				return $html;
			}

			if ( ! $this->eligibility->is_eligible( $attachment_id, $html, $context ) ) {
				return $html;
			}

			if ( ! $this->allow_repeated_original( $context ) && $this->registry->has( $attachment_id, $html ) ) {
				return $html;
			}

			$image_meta       = $this->image_meta( $context );
			$dimension_repair = $this->dimension_repair->repair( $attachment_id, $html, $image_meta, $known_width );
			$html             = $dimension_repair->html();
			$html             = $this->loading_attributes->apply_to_fallback_markup( $html, $attachment_id );
			$extraction       = $this->extractor->extract( $html, $known_width );

			if ( ! $extraction->has_sources() || array() === $image_meta ) {
				return $html;
			}

			$source_sets = $this->builder->build(
				new SourceSetBuildRequest( $attachment_id, $extraction->sources(), $image_meta )
			);

			if ( array() === $source_sets->formats() ) {
				return $html;
			}

			$rendered = $this->renderer->render(
				new PictureRenderRequest(
					$attachment_id,
					$html,
					$source_sets,
					$this->settings->format_preference(),
					$dimension_repair->codes()
				)
			);

			if ( ! $rendered->is_rendered() ) {
				return $html;
			}

			$this->registry->record( $attachment_id, $html );
			$this->registry->record( $attachment_id, $rendered->html() );

			return $rendered->html();
		} catch ( \Throwable $exception ) {
			unset( $exception );

			return $html;
		}
	}

	/**
	 * Read attachment metadata from the supplied context.
	 *
	 * @param array<string,mixed> $context Transform context.
	 * @return array<string,mixed>
	 */
	private function image_meta( array $context ): array {
		if ( isset( $context['image_meta'] ) && is_array( $context['image_meta'] ) ) {
			return $context['image_meta'];
		}

		return array();
	}

	/**
	 * Whether a trusted isolated caller may transform repeated identical original markup.
	 *
	 * @param array<string,mixed> $context Transform context.
	 * @return bool
	 */
	private function allow_repeated_original( array $context ): bool {
		return ! empty( $context['allow_repeated_original'] );
	}
}
