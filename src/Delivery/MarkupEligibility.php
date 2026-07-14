<?php
/**
 * Delivery markup eligibility service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Decides whether attachment image delivery may transform one markup fragment.
 */
final class MarkupEligibility {

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
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Offload support service.
	 *
	 * @var OffloadSupportService|null
	 */
	private $offload;

	/**
	 * Create service.
	 *
	 * @param SettingsRepositoryInterface     $settings Settings repository.
	 * @param AttachmentImageRuntimeInterface $runtime Runtime seam.
	 * @param ImageMarkupAnalyzerInterface    $analyzer Markup analyzer.
	 * @param OffloadSupportService|null      $offload Offload support service.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		AttachmentImageRuntimeInterface $runtime,
		ImageMarkupAnalyzerInterface $analyzer,
		?OffloadSupportService $offload = null
	) {
		$this->settings = $settings;
		$this->runtime  = $runtime;
		$this->analyzer = $analyzer;
		$this->offload  = $offload;
	}

	/**
	 * Whether delivery is enabled after developer override.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $html Original HTML.
	 * @param array<string,mixed> $context Request context.
	 * @return bool
	 */
	public function delivery_enabled( int $attachment_id, string $html, array $context ): bool {
		$enabled = $this->settings->delivery_enabled();

		if ( function_exists( 'apply_filters' ) ) {
			$enabled = (bool) \apply_filters(
				'hwlio_delivery_is_enabled',
				$enabled,
				$attachment_id,
				$html,
				$context
			);
		}

		return (bool) $enabled && ! $this->settings->delivery_emergency_disabled();
	}

	/**
	 * Whether markup may be transformed for this callback.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $html Original HTML.
	 * @param array<string,mixed> $context Request context.
	 * @return bool
	 */
	public function is_eligible( int $attachment_id, string $html, array $context ): bool {
		$request_context = isset( $context['request_context'] ) && is_array( $context['request_context'] )
			? $context['request_context']
			: array();
		$analysis        = $this->analyzer->analyze( $html );

		$eligible = $attachment_id > 0
			&& empty( $context['icon'] )
			&& $this->runtime->attachment_is_image( $attachment_id )
			&& empty( $request_context['is_admin'] )
			&& empty( $request_context['is_feed'] )
			&& empty( $request_context['is_ajax'] )
			&& empty( $request_context['is_rest'] )
			&& ! $analysis->is_picture()
			&& $analysis->is_renderable_img();

		if ( $eligible && null !== $this->offload ) {
			$support  = $this->offload->attachment_support( $attachment_id );
			$eligible = $support->is_supported();
		}

		if ( function_exists( 'apply_filters' ) ) {
			$eligible = (bool) \apply_filters(
				'hwlio_markup_is_eligible',
				$eligible,
				$attachment_id,
				$html,
				$context
			);
		}

		return (bool) $eligible;
	}
}
