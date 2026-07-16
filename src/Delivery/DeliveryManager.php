<?php
/**
 * Attachment image delivery manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Activates modern-format frontend delivery for attachment image markup.
 */
final class DeliveryManager implements HookProviderInterface {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Attachment image runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

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
	 * Local uploads attachment resolver.
	 *
	 * @var LocalUploadAttachmentResolver|null
	 */
	private $local_uploads;

	/**
	 * Create provider.
	 *
	 * @param SettingsRepositoryInterface        $settings Settings repository.
	 * @param AttachmentImageRuntimeInterface    $runtime Runtime seam.
	 * @param MarkupEligibility                  $eligibility Eligibility service.
	 * @param AttachmentImageSourceExtractor     $extractor Source extractor.
	 * @param SourceSetBuilder                   $builder Source-set builder.
	 * @param PictureRenderer                    $renderer Picture renderer.
	 * @param TransformedMarkupRegistry          $registry Request-local registry.
	 * @param IntrinsicDimensionRepair           $dimension_repair Intrinsic dimension repair.
	 * @param LoadingAttributeManager            $loading_attributes Loading attribute manager.
	 * @param LocalUploadAttachmentResolver|null $local_uploads Local uploads attachment resolver.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		AttachmentImageRuntimeInterface $runtime,
		MarkupEligibility $eligibility,
		AttachmentImageSourceExtractor $extractor,
		SourceSetBuilder $builder,
		PictureRenderer $renderer,
		TransformedMarkupRegistry $registry,
		IntrinsicDimensionRepair $dimension_repair,
		LoadingAttributeManager $loading_attributes,
		?LocalUploadAttachmentResolver $local_uploads = null
	) {
		$this->settings           = $settings;
		$this->runtime            = $runtime;
		$this->eligibility        = $eligibility;
		$this->extractor          = $extractor;
		$this->builder            = $builder;
		$this->renderer           = $renderer;
		$this->registry           = $registry;
		$this->dimension_repair   = $dimension_repair;
		$this->loading_attributes = $loading_attributes;
		$this->local_uploads      = $local_uploads;
	}

	/**
	 * Register runtime hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( 'wp_get_attachment_image', array( $this, 'filter_attachment_image' ), 10, 5 );
		$hooks->add_filter( 'wp_content_img_tag', array( $this, 'filter_content_img_tag' ), 10, 3 );
	}

	/**
	 * Transform one attachment image markup fragment into safe picture markup when eligible.
	 *
	 * @param string $html Attachment image HTML.
	 * @param int    $attachment_id Attachment ID.
	 * @param mixed  $size Requested size.
	 * @param bool   $icon Whether icon fallback was requested.
	 * @param mixed  $attr Requested attributes.
	 * @return string
	 */
	public function filter_attachment_image( string $html, int $attachment_id, $size, bool $icon, $attr ): string {
		return $this->transform_markup(
			$html,
			$attachment_id,
			array(
				'hook'            => 'wp_get_attachment_image',
				'size'            => $size,
				'icon'            => $icon,
				'attr'            => $attr,
				'content_context' => null,
				'request_context' => $this->runtime->request_context(),
			),
			$this->runtime->requested_image_width( $attachment_id, $size, $attr, $this->runtime->attachment_metadata( $attachment_id ) )
		);
	}

	/**
	 * Transform one content image markup fragment into safe picture markup when WordPress resolved an attachment.
	 *
	 * @param string $html Content image HTML.
	 * @param string $context WordPress content-filter context.
	 * @param int    $attachment_id Attachment ID, or 0 when unresolved.
	 * @return string
	 */
	public function filter_content_img_tag( string $html, string $context, int $attachment_id ): string {
		$resolution = null;

		if ( $attachment_id < 1 && null !== $this->local_uploads ) {
			$resolution = $this->local_uploads->resolve( $html );

			if ( $resolution->is_resolved() ) {
				$attachment_id = $resolution->attachment_id();
			}
		}

		return $this->transform_markup(
			$html,
			$attachment_id,
			array(
				'hook'            => 'wp_content_img_tag',
				'size'            => null,
				'icon'            => false,
				'attr'            => array(),
				'content_context' => $context,
				'request_context' => $this->runtime->request_context(),
				'url_resolution'  => null !== $resolution ? $resolution->to_array() : null,
			),
			null
		);
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
	private function transform_markup( string $html, int $attachment_id, array $context, ?int $known_width ): string {
		try {
			if ( ! $this->eligibility->delivery_enabled( $attachment_id, $html, $context ) ) {
				return $html;
			}

			if ( ! $this->eligibility->is_eligible( $attachment_id, $html, $context ) ) {
				return $html;
			}

			if ( $this->registry->has( $attachment_id, $html ) ) {
				return $html;
			}

			$image_meta       = $this->runtime->attachment_metadata( $attachment_id );
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
}
