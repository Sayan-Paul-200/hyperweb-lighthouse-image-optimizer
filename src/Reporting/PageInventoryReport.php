<?php
/**
 * Page inventory report.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one content inventory report payload.
 */
final class PageInventoryReport {

	/**
	 * Content facts.
	 *
	 * @var array<string,mixed>
	 */
	private $content;

	/**
	 * Inventory items.
	 *
	 * @var PageInventoryItem[]
	 */
	private $items;

	/**
	 * Unsupported observations.
	 *
	 * @var UnsupportedInventoryCase[]
	 */
	private $unsupported;

	/**
	 * Create report.
	 *
	 * @param array<string,mixed>        $content Content facts.
	 * @param PageInventoryItem[]        $items Items.
	 * @param UnsupportedInventoryCase[] $unsupported Unsupported observations.
	 */
	public function __construct( array $content, array $items = array(), array $unsupported = array() ) {
		$this->content     = $this->sanitize_content( $content );
		$this->items       = array_values(
			array_filter(
				$items,
				static function ( $item ): bool {
					return $item instanceof PageInventoryItem;
				}
			)
		);
		$this->unsupported = array_values(
			array_filter(
				$unsupported,
				static function ( $item ): bool {
					return $item instanceof UnsupportedInventoryCase;
				}
			)
		);
	}

	/**
	 * Serialize report.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'content'     => $this->content,
			'summary'     => $this->summary(),
			'items'       => array_map(
				static function ( PageInventoryItem $item ): array {
					return $item->to_array();
				},
				$this->items
			),
			'unsupported' => array_map(
				static function ( UnsupportedInventoryCase $item ): array {
					return $item->to_array();
				},
				$this->unsupported
			),
		);
	}

	/**
	 * Build scalar summary counts.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$by_origin       = array(
			PageInventoryItem::ORIGIN_LOCAL_ATTACHMENT   => 0,
			PageInventoryItem::ORIGIN_LOCAL_UNREGISTERED => 0,
			PageInventoryItem::ORIGIN_EXTERNAL           => 0,
			PageInventoryItem::ORIGIN_UNKNOWN            => 0,
		);
		$by_presentation = array(
			PageInventoryItem::PRESENTATION_INLINE     => 0,
			PageInventoryItem::PRESENTATION_BACKGROUND => 0,
		);
		$attachments     = array();

		foreach ( $this->items as $item ) {
			++$by_origin[ $item->origin() ];
			++$by_presentation[ $item->presentation() ];

			if ( null !== $item->attachment_id() ) {
				$attachments[ $item->attachment_id() ] = true;
			}
		}

		return array(
			'total_items'             => count( $this->items ),
			'by_origin'               => $by_origin,
			'by_presentation'         => $by_presentation,
			'unique_attachment_count' => count( $attachments ),
			'unsupported_count'       => count( $this->unsupported ),
		);
	}

	/**
	 * Sanitize one content facts payload.
	 *
	 * @param array<string,mixed> $content Content facts.
	 * @return array<string,mixed>
	 */
	private function sanitize_content( array $content ): array {
		return array(
			'id'                     => isset( $content['id'] ) && is_numeric( $content['id'] ) ? max( 0, (int) $content['id'] ) : 0,
			'type'                   => isset( $content['type'] ) && is_string( $content['type'] ) ? trim( $content['type'] ) : '',
			'title'                  => isset( $content['title'] ) && is_string( $content['title'] ) ? trim( $content['title'] ) : '',
			'status'                 => isset( $content['status'] ) && is_string( $content['status'] ) ? trim( $content['status'] ) : '',
			'has_elementor_document' => ! empty( $content['has_elementor_document'] ),
			'is_woo_product'         => ! empty( $content['is_woo_product'] ),
		);
	}
}
