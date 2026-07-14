<?php
/**
 * Content inventory snapshot.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one rich internal inventory snapshot for later reporting layers.
 */
final class ContentInventorySnapshot {

	/**
	 * Content facts.
	 *
	 * @var array<string,mixed>
	 */
	private $content;

	/**
	 * Ordered occurrences.
	 *
	 * @var InventoryOccurrence[]
	 */
	private $occurrences;

	/**
	 * Unsupported observations.
	 *
	 * @var UnsupportedInventoryCase[]
	 */
	private $unsupported;

	/**
	 * Create snapshot.
	 *
	 * @param array<string,mixed>        $content Content facts.
	 * @param InventoryOccurrence[]      $occurrences Ordered occurrences.
	 * @param UnsupportedInventoryCase[] $unsupported Unsupported observations.
	 */
	public function __construct( array $content, array $occurrences = array(), array $unsupported = array() ) {
		$this->content     = $this->sanitize_content( $content );
		$this->occurrences = array_values(
			array_filter(
				$occurrences,
				static function ( $occurrence ): bool {
					return $occurrence instanceof InventoryOccurrence;
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
	 * Get content facts.
	 *
	 * @return array<string,mixed>
	 */
	public function content(): array {
		return $this->content;
	}

	/**
	 * Get content ID.
	 *
	 * @return int
	 */
	public function content_id(): int {
		return isset( $this->content['id'] ) ? (int) $this->content['id'] : 0;
	}

	/**
	 * Get content type.
	 *
	 * @return string
	 */
	public function content_type(): string {
		return isset( $this->content['type'] ) && is_string( $this->content['type'] ) ? $this->content['type'] : '';
	}

	/**
	 * Get ordered occurrences.
	 *
	 * @return InventoryOccurrence[]
	 */
	public function occurrences(): array {
		return $this->occurrences;
	}

	/**
	 * Get unsupported observations.
	 *
	 * @return UnsupportedInventoryCase[]
	 */
	public function unsupported(): array {
		return $this->unsupported;
	}

	/**
	 * Whether an attachment appears in the snapshot.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function has_attachment_occurrence( int $attachment_id ): bool {
		$attachment_id = max( 0, $attachment_id );

		if ( $attachment_id < 1 ) {
			return false;
		}

		foreach ( $this->occurrences as $occurrence ) {
			if ( $attachment_id === $occurrence->attachment_id() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert to the existing public inventory report.
	 *
	 * @return PageInventoryReport
	 */
	public function to_page_inventory_report(): PageInventoryReport {
		return new PageInventoryReport(
			$this->content,
			array_map(
				static function ( InventoryOccurrence $occurrence ): PageInventoryItem {
					return $occurrence->to_page_inventory_item();
				},
				$this->occurrences
			),
			$this->unsupported
		);
	}

	/**
	 * Sanitize content facts.
	 *
	 * @param array<string,mixed> $content Raw content facts.
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
