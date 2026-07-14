<?php
/**
 * Local attachment source collector adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;

/**
 * Adapts the existing local-only SourceCollector to the offload-aware contract.
 */
final class LocalAttachmentSourceCollector implements AttachmentSourceCollectorInterface {

	/**
	 * Local source collector.
	 *
	 * @var SourceCollector
	 */
	private $collector;

	/**
	 * Create adapter.
	 *
	 * @param SourceCollector $collector Local source collector.
	 */
	public function __construct( SourceCollector $collector ) {
		$this->collector = $collector;
	}

	/**
	 * Collect one local source set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return CollectedSourceSet
	 */
	public function collect( int $attachment_id ): CollectedSourceSet {
		return CollectedSourceSet::from_collection( $this->collector->collect( $attachment_id ) );
	}
}
