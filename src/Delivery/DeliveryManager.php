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
	 * Attachment image runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Shared image transformer.
	 *
	 * @var DeliveryImageTransformer
	 */
	private $transformer;

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
	 * @param DeliveryImageTransformer|null      $transformer Shared transformer.
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
		?LocalUploadAttachmentResolver $local_uploads = null,
		?DeliveryImageTransformer $transformer = null
	) {
		$this->runtime       = $runtime;
		$this->local_uploads = $local_uploads;
		$this->transformer   = $transformer ?? new DeliveryImageTransformer(
			$settings,
			$eligibility,
			$extractor,
			$builder,
			$renderer,
			$registry,
			$dimension_repair,
			$loading_attributes
		);
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
				'image_meta'      => $this->runtime->attachment_metadata( $attachment_id ),
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
				'image_meta'      => $this->runtime->attachment_metadata( $attachment_id ),
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
		return $this->transformer->transform( $html, $attachment_id, $context, $known_width );
	}
}
