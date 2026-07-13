<?php
/**
 * Attachment details service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;

/**
 * Reads sanitized attachment details for REST consumers.
 */
final class AttachmentDetailsService {

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Create the service.
	 *
	 * @param DerivativeRepository $repository Derivative repository.
	 */
	public function __construct( DerivativeRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Read one attachment snapshot.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	public function details( int $attachment_id ): array {
		$attachment_id = max( 0, $attachment_id );
		$read          = $this->repository->read( $attachment_id );

		return array(
			'attachment_id' => $attachment_id,
			'warnings'      => $read->has_warnings(),
			'codes'         => $read->codes(),
			'messages'      => $read->messages(),
			'status'        => $read->status()->to_array(),
			'manifest'      => $read->manifest()->to_array(),
		);
	}
}
