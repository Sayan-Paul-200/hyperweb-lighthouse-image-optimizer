<?php
/**
 * Elementor static widget delivery bridge.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Delivery\AttachmentImageRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryImageTransformer;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolver;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;

/**
 * Sends safe Elementor static image-widget IMG fragments through delivery.
 */
final class ElementorWidgetDeliveryBridge implements HookProviderInterface {

	/**
	 * Elementor runtime seam.
	 *
	 * @var ElementorRuntimeInterface
	 */
	private $elementor;

	/**
	 * Attachment image runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Widget matcher.
	 *
	 * @var ElementorWidgetMatcher
	 */
	private $matcher;

	/**
	 * Shared delivery transformer.
	 *
	 * @var DeliveryImageTransformer
	 */
	private $transformer;

	/**
	 * Trusted attachment marker parser.
	 *
	 * @var TrustedAttachmentMarkerParser
	 */
	private $markers;

	/**
	 * Local uploads resolver.
	 *
	 * @var LocalUploadAttachmentResolver
	 */
	private $local_uploads;

	/**
	 * Create bridge.
	 *
	 * @param ElementorRuntimeInterface       $elementor Elementor runtime.
	 * @param AttachmentImageRuntimeInterface $runtime Attachment image runtime.
	 * @param ElementorWidgetMatcher          $matcher Widget matcher.
	 * @param DeliveryImageTransformer        $transformer Shared delivery transformer.
	 * @param TrustedAttachmentMarkerParser   $markers Trusted attachment marker parser.
	 * @param LocalUploadAttachmentResolver   $local_uploads Local uploads resolver.
	 */
	public function __construct(
		ElementorRuntimeInterface $elementor,
		AttachmentImageRuntimeInterface $runtime,
		ElementorWidgetMatcher $matcher,
		DeliveryImageTransformer $transformer,
		TrustedAttachmentMarkerParser $markers,
		LocalUploadAttachmentResolver $local_uploads
	) {
		$this->elementor     = $elementor;
		$this->runtime       = $runtime;
		$this->matcher       = $matcher;
		$this->transformer   = $transformer;
		$this->markers       = $markers;
		$this->local_uploads = $local_uploads;
	}

	/**
	 * Register the narrow Elementor rendered-content filter.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( 'elementor/widget/render_content', array( $this, 'filter_widget_content' ), 10, 2 );
	}

	/**
	 * Transform one supported static Elementor widget image.
	 *
	 * @param string $content Rendered widget content.
	 * @param mixed  $widget Elementor widget object.
	 * @return string
	 */
	public function filter_widget_content( string $content, $widget ): string {
		if ( ! $this->elementor->is_available() || $this->elementor->is_editor_mode() || $this->elementor->is_preview_mode() ) {
			return $content;
		}

		if ( false !== stripos( $content, '<picture' ) ) {
			return $content;
		}

		$widget_name = $this->widget_name( $widget );

		if ( ! in_array( $widget_name, ElementorWidgetMatcher::SUPPORTED_STATIC_WIDGET_NAMES, true ) ) {
			return $content;
		}

		$fragments = $this->image_fragments( $content );

		if ( 1 !== count( $fragments ) ) {
			return $content;
		}

		$html          = $fragments[0];
		$attachment_id = $this->markers->parse_attachment_id( $html );
		$resolution    = null;

		if ( $attachment_id < 1 ) {
			$resolution = $this->local_uploads->resolve( $html );

			if ( $resolution->is_resolved() ) {
				$attachment_id = $resolution->attachment_id();
			}
		}

		if ( $attachment_id < 1 ) {
			return $content;
		}

		$match = $this->matcher->match_widget_fragment( $html, $widget_name, true );

		if ( ElementorWidgetMatcher::MATCH_SUPPORTED_ATTACHMENT_WIDGET !== $match ) {
			return $content;
		}

		$transformed = $this->transformer->transform(
			$html,
			$attachment_id,
			array(
				'hook'                    => 'elementor/widget/render_content',
				'size'                    => null,
				'icon'                    => false,
				'attr'                    => array(),
				'content_context'         => $widget_name,
				'request_context'         => $this->runtime->request_context(),
				'url_resolution'          => null !== $resolution ? $resolution->to_array() : null,
				'image_meta'              => $this->runtime->attachment_metadata( $attachment_id ),
				'allow_repeated_original' => true,
			),
			null
		);

		if ( $transformed === $html ) {
			return $content;
		}

		return $this->replace_once( $content, $html, $transformed );
	}

	/**
	 * Resolve one Elementor widget name.
	 *
	 * @param mixed $widget Widget object.
	 * @return string
	 */
	private function widget_name( $widget ): string {
		if ( ! is_object( $widget ) || ! is_callable( array( $widget, 'get_name' ) ) ) {
			return '';
		}

		return strtolower( trim( (string) $widget->get_name() ) );
	}

	/**
	 * Extract IMG fragments from widget content.
	 *
	 * @param string $content Content.
	 * @return string[]
	 */
	private function image_fragments( string $content ): array {
		$found = preg_match_all( '/<img\b[^>]*>/i', $content, $matches );

		if ( ! is_int( $found ) || $found < 1 ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static function ( string $fragment ): string {
						return trim( $fragment );
					},
					$matches[0]
				),
				static function ( string $fragment ): bool {
					return '' !== $fragment;
				}
			)
		);
	}

	/**
	 * Replace one exact fragment in a larger content string.
	 *
	 * @param string $content Content.
	 * @param string $needle Original fragment.
	 * @param string $replacement Replacement.
	 * @return string
	 */
	private function replace_once( string $content, string $needle, string $replacement ): string {
		$position = strpos( $content, $needle );

		if ( false === $position ) {
			return $content;
		}

		return substr( $content, 0, $position ) . $replacement . substr( $content, $position + strlen( $needle ) );
	}
}
