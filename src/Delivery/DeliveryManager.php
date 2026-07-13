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
	 * Create provider.
	 *
	 * @param SettingsRepositoryInterface     $settings Settings repository.
	 * @param AttachmentImageRuntimeInterface $runtime Runtime seam.
	 * @param MarkupEligibility               $eligibility Eligibility service.
	 * @param AttachmentImageSourceExtractor  $extractor Source extractor.
	 * @param SourceSetBuilder                $builder Source-set builder.
	 * @param PictureRenderer                 $renderer Picture renderer.
	 * @param TransformedMarkupRegistry       $registry Request-local registry.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		AttachmentImageRuntimeInterface $runtime,
		MarkupEligibility $eligibility,
		AttachmentImageSourceExtractor $extractor,
		SourceSetBuilder $builder,
		PictureRenderer $renderer,
		TransformedMarkupRegistry $registry
	) {
		$this->settings    = $settings;
		$this->runtime     = $runtime;
		$this->eligibility = $eligibility;
		$this->extractor   = $extractor;
		$this->builder     = $builder;
		$this->renderer    = $renderer;
		$this->registry    = $registry;
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

			$image_meta = $this->runtime->attachment_metadata( $attachment_id );
			$extraction = $this->extractor->extract( $html, $known_width );

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
					$this->settings->format_preference()
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
