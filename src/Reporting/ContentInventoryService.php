<?php
/**
 * Content inventory service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolution;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolver;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundSource;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentDataStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorUnsupportedBackgroundCase;

/**
 * Builds a conservative read-only image inventory for one content record.
 */
final class ContentInventoryService {

	/**
	 * Runtime seam.
	 *
	 * @var ContentInventoryRuntimeInterface
	 */
	private $runtime;

	/**
	 * Lightweight attachment reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $attachments;

	/**
	 * Elementor document store.
	 *
	 * @var ElementorDocumentDataStoreInterface
	 */
	private $elementor_documents;

	/**
	 * Elementor background discovery.
	 *
	 * @var ElementorBackgroundDiscovery
	 */
	private $elementor_backgrounds;

	/**
	 * Trusted attachment marker parser.
	 *
	 * @var TrustedAttachmentMarkerParser
	 */
	private $markers;

	/**
	 * Local uploads attachment resolver.
	 *
	 * @var LocalUploadAttachmentResolver|null
	 */
	private $local_uploads;

	/**
	 * Create service.
	 *
	 * @param ContentInventoryRuntimeInterface    $runtime Runtime seam.
	 * @param AttachmentStatusReader              $attachments Attachment reader.
	 * @param ElementorDocumentDataStoreInterface $elementor_documents Elementor document store.
	 * @param ElementorBackgroundDiscovery        $elementor_backgrounds Background discovery.
	 * @param TrustedAttachmentMarkerParser       $markers Marker parser.
	 * @param LocalUploadAttachmentResolver|null  $local_uploads Local uploads attachment resolver.
	 */
	public function __construct(
		ContentInventoryRuntimeInterface $runtime,
		AttachmentStatusReader $attachments,
		ElementorDocumentDataStoreInterface $elementor_documents,
		ElementorBackgroundDiscovery $elementor_backgrounds,
		TrustedAttachmentMarkerParser $markers,
		?LocalUploadAttachmentResolver $local_uploads = null
	) {
		$this->runtime               = $runtime;
		$this->attachments           = $attachments;
		$this->elementor_documents   = $elementor_documents;
		$this->elementor_backgrounds = $elementor_backgrounds;
		$this->markers               = $markers;
		$this->local_uploads         = $local_uploads;
	}

	/**
	 * Whether one content record exists.
	 *
	 * @param int $content_id Content ID.
	 * @return bool
	 */
	public function content_exists( int $content_id ): bool {
		return $this->runtime->content_exists( $content_id );
	}

	/**
	 * Build the inventory report for one content record.
	 *
	 * @param int $content_id Content ID.
	 * @return PageInventoryReport
	 */
	public function report( int $content_id ): PageInventoryReport {
		return $this->inspect( $content_id )->to_page_inventory_report();
	}

	/**
	 * Build the rich internal inventory snapshot for one content record.
	 *
	 * @param int $content_id Content ID.
	 * @return ContentInventorySnapshot
	 */
	public function inspect( int $content_id ): ContentInventorySnapshot {
		$content_id    = max( 0, $content_id );
		$content_type  = $this->runtime->content_type( $content_id );
		$document_data = $this->elementor_documents->read_document( $content_id );
		$items         = array();
		$unsupported   = array();
		$occurrence_id = 1;

		foreach ( $this->content_fragments( $this->runtime->content_body( $content_id ) ) as $index => $fragment ) {
			$items[] = $this->inventory_from_content_fragment( 'occ-' . $occurrence_id, $fragment, $index + 1, $unsupported );
			++$occurrence_id;
		}

		if ( ! $document_data->is_missing() ) {
			$backgrounds = $this->elementor_backgrounds->discover_from_document( $content_id, $document_data );

			foreach ( $backgrounds->supported_sources() as $source ) {
				$items[] = $this->inventory_from_elementor_background( 'occ-' . $occurrence_id, $source );
				++$occurrence_id;
			}

			foreach ( $backgrounds->unsupported_cases() as $case ) {
				$unsupported[] = $this->inventory_from_elementor_unsupported_case( $case );
			}
		}

		if ( 'product' === $content_type ) {
			$featured_image_id = $this->runtime->featured_image_id( $content_id );

			if ( $featured_image_id > 0 ) {
				$items[] = new InventoryOccurrence(
					'occ-' . $occurrence_id,
					'woocommerce_featured_image',
					PageInventoryItem::PRESENTATION_INLINE,
					PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
					$featured_image_id,
					null,
					$this->attachment_summary( $featured_image_id ),
					array(
						'role' => 'featured',
					),
					array(
						'attachment_role' => 'featured',
					)
				);
				++$occurrence_id;
			}

			foreach ( $this->runtime->product_gallery_image_ids( $content_id ) as $index => $attachment_id ) {
				$items[] = new InventoryOccurrence(
					'occ-' . $occurrence_id,
					'woocommerce_gallery_image',
					PageInventoryItem::PRESENTATION_INLINE,
					PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
					$attachment_id,
					null,
					$this->attachment_summary( $attachment_id ),
					array(
						'role'          => 'gallery',
						'gallery_index' => $index + 1,
					),
					array(
						'attachment_role' => 'gallery',
						'gallery_index'   => $index + 1,
					)
				);
				++$occurrence_id;
			}
		}

		return new ContentInventorySnapshot(
			array(
				'id'                     => $content_id,
				'type'                   => $content_type,
				'title'                  => $this->runtime->content_title( $content_id ),
				'status'                 => $this->runtime->content_status( $content_id ),
				'has_elementor_document' => ! $document_data->is_missing(),
				'is_woo_product'         => 'product' === $content_type,
			),
			$items,
			$unsupported
		);
	}

	/**
	 * Extract raw IMG fragments from stored content.
	 *
	 * @param string $content Raw content.
	 * @return string[]
	 */
	private function content_fragments( string $content ): array {
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
	 * Build one content-image inventory item.
	 *
	 * @param string                     $id Stable occurrence ID.
	 * @param string                     $fragment IMG fragment.
	 * @param int                        $occurrence One-based occurrence index.
	 * @param UnsupportedInventoryCase[] $unsupported Unsupported collector.
	 * @return InventoryOccurrence
	 */
	private function inventory_from_content_fragment( string $id, string $fragment, int $occurrence, array &$unsupported ): InventoryOccurrence {
		$attachment_id = $this->markers->parse_attachment_id( $fragment );
		$url           = $this->attribute_value( $fragment, 'src' );
		$evidence      = array(
			'occurrence' => $occurrence,
		);

		if ( $attachment_id < 1 && null !== $this->local_uploads ) {
			$resolution = $this->local_uploads->resolve( $fragment );

			if ( $resolution->is_resolved() ) {
				$attachment_id                      = $resolution->attachment_id();
				$evidence['url_resolution']         = $resolution->to_array();
				$evidence['url_resolution_code']    = $resolution->code();
				$evidence['resolved_relative_path'] = $resolution->relative_path();
			} elseif ( LocalUploadAttachmentResolution::CODE_UNRESOLVED === $resolution->code() && '' !== $resolution->relative_path() ) {
				$evidence['url_resolution']         = $resolution->to_array();
				$evidence['url_resolution_code']    = $resolution->code();
				$evidence['resolved_relative_path'] = $resolution->relative_path();
			}
		}

		if ( $attachment_id > 0 ) {
			$resolution_code    = isset( $evidence['url_resolution_code'] ) ? (string) $evidence['url_resolution_code'] : '';
			$evidence['marker'] = '' !== $resolution_code
				? $resolution_code
				: 'trusted_attachment';

			return new InventoryOccurrence(
				$id,
				'core_content',
				PageInventoryItem::PRESENTATION_INLINE,
				PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
				$attachment_id,
				$url,
				$this->attachment_summary( $attachment_id ),
				$evidence,
				array(
					'raw_img_html' => $fragment,
				)
			);
		}

		$origin = $this->classify_url_origin( $url );

		if ( PageInventoryItem::ORIGIN_UNKNOWN === $origin ) {
			$unsupported[] = new UnsupportedInventoryCase(
				'content_non_classifiable_reference',
				UnsupportedInventoryCase::SOURCE_CORE_CONTENT,
				'Unknown content image reference',
				'This content image reference could not be classified confidently.',
				array(
					'occurrence' => $occurrence,
					'url'        => null !== $url ? $url : 'missing',
				)
			);
		}

		return new InventoryOccurrence(
			$id,
			'core_content',
			PageInventoryItem::PRESENTATION_INLINE,
			$origin,
			null,
			$url,
			null,
			$evidence,
			array(
				'raw_img_html' => $fragment,
			)
		);
	}

	/**
	 * Build one Elementor background inventory item.
	 *
	 * @param string                    $id Stable occurrence ID.
	 * @param ElementorBackgroundSource $source Supported source.
	 * @return InventoryOccurrence
	 */
	private function inventory_from_elementor_background( string $id, ElementorBackgroundSource $source ): InventoryOccurrence {
		$data = $source->to_array();

		return new InventoryOccurrence(
			$id,
			'elementor_background',
			PageInventoryItem::PRESENTATION_BACKGROUND,
			PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT,
			isset( $data['attachment_id'] ) ? (int) $data['attachment_id'] : null,
			isset( $data['url'] ) && is_string( $data['url'] ) ? $data['url'] : null,
			isset( $data['attachment_id'] ) ? $this->attachment_summary( (int) $data['attachment_id'] ) : null,
			array(
				'element_id'    => isset( $data['element_id'] ) ? (string) $data['element_id'] : '',
				'element_type'  => isset( $data['element_type'] ) ? (string) $data['element_type'] : '',
				'widget_type'   => isset( $data['widget_type'] ) && is_string( $data['widget_type'] ) ? $data['widget_type'] : null,
				'setting_group' => isset( $data['setting_group'] ) ? (string) $data['setting_group'] : '',
				'device'        => isset( $data['device'] ) ? (string) $data['device'] : '',
				'setting_key'   => isset( $data['setting_key'] ) ? (string) $data['setting_key'] : '',
			),
			array(
				'element_id'    => isset( $data['element_id'] ) ? (string) $data['element_id'] : '',
				'element_type'  => isset( $data['element_type'] ) ? (string) $data['element_type'] : '',
				'widget_type'   => isset( $data['widget_type'] ) && is_string( $data['widget_type'] ) ? $data['widget_type'] : null,
				'setting_group' => isset( $data['setting_group'] ) ? (string) $data['setting_group'] : '',
				'device'        => isset( $data['device'] ) ? (string) $data['device'] : '',
				'setting_key'   => isset( $data['setting_key'] ) ? (string) $data['setting_key'] : '',
			)
		);
	}

	/**
	 * Map one unsupported Elementor case into the generic inventory shape.
	 *
	 * @param ElementorUnsupportedBackgroundCase $unsupported_case Unsupported case.
	 * @return UnsupportedInventoryCase
	 */
	private function inventory_from_elementor_unsupported_case( ElementorUnsupportedBackgroundCase $unsupported_case ): UnsupportedInventoryCase {
		$data    = $unsupported_case->to_array();
		$label   = 'Unsupported Elementor background';
		$message = 'This Elementor background reference could not be inventoried as a trusted local attachment.';

		if ( ElementorUnsupportedBackgroundCase::CODE_INVALID_DOCUMENT_DATA === $unsupported_case->code() ) {
			$label   = 'Invalid Elementor document data';
			$message = 'Stored Elementor document data is invalid, so background inventory could not continue safely.';
		} elseif ( ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_CSS_URL === $unsupported_case->code() ) {
			$label   = 'Elementor custom CSS background';
			$message = 'A custom CSS background url() token was found and remains unsupported in this inventory pass.';
		} elseif ( ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_MODE === $unsupported_case->code() ) {
			$label   = 'Unsupported Elementor background mode';
			$message = 'This Elementor background mode is outside the supported structured attachment-backed inventory scope.';
		} elseif ( ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_VALUE === $unsupported_case->code() ) {
			$label   = 'Unregistered Elementor background value';
			$message = 'This Elementor background value does not expose a trusted attachment ID for conservative inventorying.';
		}

		return new UnsupportedInventoryCase(
			'elementor_' . $unsupported_case->code(),
			UnsupportedInventoryCase::SOURCE_ELEMENTOR_BACKGROUND,
			$label,
			$message,
			array(
				'element_id'    => isset( $data['element_id'] ) ? (string) $data['element_id'] : '',
				'setting_group' => isset( $data['setting_group'] ) ? (string) $data['setting_group'] : '',
				'device'        => isset( $data['device'] ) ? (string) $data['device'] : '',
				'setting_key'   => isset( $data['setting_key'] ) ? (string) $data['setting_key'] : '',
				'value_hint'    => isset( $data['value_hint'] ) && is_string( $data['value_hint'] ) ? $data['value_hint'] : null,
			)
		);
	}

	/**
	 * Build one lightweight attachment summary.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	private function attachment_summary( int $attachment_id ): array {
		$status = $this->attachments->read( $attachment_id );

		return array(
			'state'         => $status->state(),
			'ready_formats' => $status->formats_ready(),
			'excluded'      => $status->excluded(),
		);
	}

	/**
	 * Read one raw attribute value.
	 *
	 * @param string $fragment HTML fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function attribute_value( string $fragment, string $attribute ): ?string {
		$pattern = sprintf(
			'/\b%s\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
			preg_quote( $attribute, '/' )
		);

		if ( 1 !== preg_match( $pattern, $fragment, $matches ) ) {
			return null;
		}

		foreach ( array( 1, 2, 3 ) as $index ) {
			if ( array_key_exists( $index, $matches ) ) {
				$value = trim( (string) $matches[ $index ] );

				return '' === $value ? null : html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
			}
		}

		return null;
	}

	/**
	 * Classify one content URL conservatively.
	 *
	 * @param string|null $url Candidate URL.
	 * @return string
	 */
	private function classify_url_origin( ?string $url ): string {
		if ( ! is_string( $url ) || '' === trim( $url ) ) {
			return PageInventoryItem::ORIGIN_UNKNOWN;
		}

		$url              = trim( $url );
		$uploads_base_url = $this->trim_trailing_slash( $this->runtime->uploads_base_url() );

		if ( '' !== $uploads_base_url && 0 === strpos( $url, $uploads_base_url ) ) {
			return PageInventoryItem::ORIGIN_LOCAL_UNREGISTERED;
		}

		$parts = function_exists( 'wp_parse_url' )
			? \wp_parse_url( $url )
			: parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Safe fallback outside WordPress bootstrap.

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return PageInventoryItem::ORIGIN_UNKNOWN;
		}

		if ( $this->same_origin( $url, $this->runtime->home_url() ) || $this->same_origin( $url, $this->runtime->site_url() ) ) {
			return PageInventoryItem::ORIGIN_LOCAL_UNREGISTERED;
		}

		return PageInventoryItem::ORIGIN_EXTERNAL;
	}

	/**
	 * Whether two URLs share the same origin.
	 *
	 * @param string $left Left URL.
	 * @param string $right Right URL.
	 * @return bool
	 */
	private function same_origin( string $left, string $right ): bool {
		$left_parts  = function_exists( 'wp_parse_url' )
			? \wp_parse_url( $left )
			: parse_url( $left ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Safe fallback outside WordPress bootstrap.
		$right_parts = function_exists( 'wp_parse_url' )
			? \wp_parse_url( $right )
			: parse_url( $right ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Safe fallback outside WordPress bootstrap.

		if ( ! is_array( $left_parts ) || ! is_array( $right_parts ) ) {
			return false;
		}

		return isset( $left_parts['host'], $right_parts['host'] )
			&& strtolower( (string) $left_parts['host'] ) === strtolower( (string) $right_parts['host'] )
			&& (int) ( $left_parts['port'] ?? 0 ) === (int) ( $right_parts['port'] ?? 0 );
	}

	/**
	 * Trim one trailing slash.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function trim_trailing_slash( string $value ): string {
		return rtrim( trim( $value ), '/' );
	}
}
