<?php
/**
 * Fake attachment processor.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessorInterface;

/**
 * Records processor requests for worker tests.
 */
final class FakeAttachmentProcessor implements AttachmentProcessorInterface {

	/**
	 * Captured requests.
	 *
	 * @var AttachmentProcessRequest[]
	 */
	public $requests = array();

	/**
	 * Optional callback returning a process result.
	 *
	 * @var callable|null
	 */
	public $callback;

	/**
	 * Default process result.
	 *
	 * @var AttachmentProcessResult
	 */
	private $result;

	/**
	 * Create fake processor.
	 *
	 * @param AttachmentProcessResult|null $result Default result.
	 */
	public function __construct( ?AttachmentProcessResult $result = null ) {
		$this->result = $result ?? AttachmentProcessResult::skip( 'noop', 'No-op process result.' );
	}

	/**
	 * Process a request.
	 *
	 * @param AttachmentProcessRequest $request Processing request.
	 * @return AttachmentProcessResult
	 */
	public function process_request( AttachmentProcessRequest $request ): AttachmentProcessResult {
		$this->requests[] = $request;

		if ( null !== $this->callback ) {
			return call_user_func( $this->callback, $request );
		}

		return $this->result;
	}
}
