<?php
/**
 * Queue control service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Coordinates global queue pause/resume status and attachment-job counts.
 */
final class QueueControlService {

	/**
	 * State store.
	 *
	 * @var QueueControlStateStoreInterface
	 */
	private $state;

	/**
	 * Attachment job control.
	 *
	 * @var AttachmentJobControlInterface
	 */
	private $jobs;

	/**
	 * Create the service.
	 *
	 * @param QueueControlStateStoreInterface $state State store.
	 * @param AttachmentJobControlInterface   $jobs Attachment job control.
	 */
	public function __construct( QueueControlStateStoreInterface $state, AttachmentJobControlInterface $jobs ) {
		$this->state = $state;
		$this->jobs  = $jobs;
	}

	/**
	 * Read the current status summary.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$state = $this->state->read();

		return array(
			'paused'             => $state->paused(),
			'updated_at_gmt'     => $state->updated_at_gmt(),
			'updated_by_user_id' => $state->updated_by_user_id(),
			'pending'            => $this->jobs->pending_count(),
			'inProgress'         => $this->jobs->in_progress_count(),
		);
	}

	/**
	 * Pause processing.
	 *
	 * @param int $user_id Updating user ID.
	 * @return array<string,mixed>
	 */
	public function pause( int $user_id ): array {
		$this->state->pause( $user_id );

		return $this->summary();
	}

	/**
	 * Resume processing.
	 *
	 * @param int $user_id Updating user ID.
	 * @return array<string,mixed>
	 */
	public function resume( int $user_id ): array {
		$this->state->resume( $user_id );

		return $this->summary();
	}

	/**
	 * Cancel pending attachment jobs.
	 *
	 * @return array<string,mixed>
	 */
	public function cancel_pending(): array {
		return array(
			'result'       => $this->jobs->cancel_pending()->to_array(),
			'queueControl' => $this->summary(),
		);
	}
}
