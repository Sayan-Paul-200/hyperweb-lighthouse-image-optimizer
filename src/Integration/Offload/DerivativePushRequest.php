<?php
/**
 * Derivative push request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;

/**
 * Carries one post-conversion derivative publish request.
 */
final class DerivativePushRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Attachment support facts.
	 *
	 * @var OffloadAttachmentSupport
	 */
	private $support;

	/**
	 * Conversion results.
	 *
	 * @var ConversionResultCollection
	 */
	private $results;

	/**
	 * Create request.
	 *
	 * @param int                        $attachment_id Attachment ID.
	 * @param OffloadAttachmentSupport   $support Attachment support facts.
	 * @param ConversionResultCollection $results Conversion results.
	 */
	public function __construct( int $attachment_id, OffloadAttachmentSupport $support, ConversionResultCollection $results ) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->support       = $support;
		$this->results       = $results;
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get attachment support.
	 *
	 * @return OffloadAttachmentSupport
	 */
	public function support(): OffloadAttachmentSupport {
		return $this->support;
	}

	/**
	 * Get results.
	 *
	 * @return ConversionResultCollection
	 */
	public function results(): ConversionResultCollection {
		return $this->results;
	}
}
