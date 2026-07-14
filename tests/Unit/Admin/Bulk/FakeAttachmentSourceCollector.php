<?php
/**
 * Fake offload-aware source collector for bulk tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\AttachmentSourceCollectorInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\CollectedSourceSet;

/**
 * Produces queueable or empty source collections by attachment ID.
 */
final class FakeAttachmentSourceCollector implements AttachmentSourceCollectorInterface {

	/**
	 * Default queueability when an attachment ID is not explicitly mapped.
	 *
	 * @var bool
	 */
	private $default_queueable;

	/**
	 * Per-attachment queueability map.
	 *
	 * @var array<int,bool>
	 */
	public $queueable = array();

	/**
	 * Create collector.
	 *
	 * @param bool $default_queueable Default queueability.
	 */
	public function __construct( bool $default_queueable = true ) {
		$this->default_queueable = $default_queueable;
	}

	/**
	 * Collect one attachment source set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return CollectedSourceSet
	 */
	public function collect( int $attachment_id ): CollectedSourceSet {
		$is_queueable = $this->queueable[ $attachment_id ] ?? $this->default_queueable;

		if ( ! $is_queueable ) {
			return CollectedSourceSet::from_collection( new SourceImageCollection() );
		}

		$relative_path = sprintf( '2026/07/image-%d.jpg', max( 1, $attachment_id ) );

		return CollectedSourceSet::from_collection(
			new SourceImageCollection(
				array(
					new SourceImage(
						$attachment_id,
						'full',
						SourceImage::ROLE_FULL,
						$relative_path,
						'/uploads/' . $relative_path,
						'image/jpeg',
						1200,
						800,
						1000,
						1783526400
					),
				)
			)
		);
	}
}
