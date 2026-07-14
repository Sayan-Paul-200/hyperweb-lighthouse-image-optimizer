<?php
/**
 * Content-critical image selector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImagePostMetaStoreInterface;

/**
 * Resolves the content-local critical attachment set for page-analysis rules.
 */
final class ContentCriticalImageSelector {

	/**
	 * Critical-image post-meta store.
	 *
	 * @var CriticalImagePostMetaStoreInterface
	 */
	private $critical_images;

	/**
	 * Create selector.
	 *
	 * @param CriticalImagePostMetaStoreInterface $critical_images Critical-image meta store.
	 */
	public function __construct( CriticalImagePostMetaStoreInterface $critical_images ) {
		$this->critical_images = $critical_images;
	}

	/**
	 * Resolve the content-local critical attachment IDs.
	 *
	 * @param ContentInventorySnapshot $snapshot Inventory snapshot.
	 * @return int[]
	 */
	public function select( ContentInventorySnapshot $snapshot ): array {
		$critical_ids = array();
		$content_type = $snapshot->content_type();
		$content_id   = $snapshot->content_id();

		if ( $content_id > 0 && in_array( $content_type, array( 'post', 'page' ), true ) ) {
			$attachment_id = $this->critical_images->get_critical_image_id( $content_id );

			if ( $attachment_id > 0 && $snapshot->has_attachment_occurrence( $attachment_id ) ) {
				$critical_ids[] = $attachment_id;
			}
		}

		if ( 'product' === $content_type ) {
			foreach ( $snapshot->occurrences() as $occurrence ) {
				if (
					'woocommerce_featured_image' === $occurrence->source()
					&& null !== $occurrence->attachment_id()
				) {
					$critical_ids[] = $occurrence->attachment_id();
				}
			}
		}

		return array_values(
			array_unique(
				array_map(
					static function ( $attachment_id ): int {
						return max( 0, (int) $attachment_id );
					},
					array_filter(
						$critical_ids,
						static function ( $attachment_id ): bool {
							return is_numeric( $attachment_id ) && (int) $attachment_id > 0;
						}
					)
				)
			)
		);
	}
}
