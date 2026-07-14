<?php
/**
 * Attachment reconciliation queue service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\AttachmentSourceCollectorInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;

/**
 * Queues one attachment reconciliation job while preserving repository rules.
 */
final class AttachmentReconciliationService {

	/**
	 * Queue adapter.
	 *
	 * @var QueueInterface
	 */
	private $queue;

	/**
	 * Attachment meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Source collector.
	 *
	 * @var AttachmentSourceCollectorInterface
	 */
	private $collector;

	/**
	 * Fingerprint builder.
	 *
	 * @var AttachmentFingerprintBuilder
	 */
	private $fingerprinter;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Queue control state store.
	 *
	 * @var QueueControlStateStoreInterface|null
	 */
	private $controls;

	/**
	 * Offload support service.
	 *
	 * @var OffloadSupportService|null
	 */
	private $offload;

	/**
	 * Create the service.
	 *
	 * @param QueueInterface                       $queue Queue adapter.
	 * @param AttachmentMetaStoreInterface         $meta Meta store.
	 * @param DerivativeRepository                 $repository Repository.
	 * @param AttachmentSourceCollectorInterface   $collector Source collector.
	 * @param AttachmentFingerprintBuilder         $fingerprinter Fingerprint builder.
	 * @param AttachmentClockInterface             $clock Clock.
	 * @param QueueControlStateStoreInterface|null $controls Optional queue controls.
	 * @param OffloadSupportService|null           $offload Optional offload support service.
	 */
	public function __construct(
		QueueInterface $queue,
		AttachmentMetaStoreInterface $meta,
		DerivativeRepository $repository,
		AttachmentSourceCollectorInterface $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		AttachmentClockInterface $clock,
		?QueueControlStateStoreInterface $controls = null,
		?OffloadSupportService $offload = null
	) {
		$this->queue         = $queue;
		$this->meta          = $meta;
		$this->repository    = $repository;
		$this->collector     = $collector;
		$this->fingerprinter = $fingerprinter;
		$this->clock         = $clock;
		$this->controls      = $controls;
		$this->offload       = $offload;
	}

	/**
	 * Queue reconciliation work for one attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $reason Queueing reason.
	 * @return AttachmentReconciliationResult
	 */
	public function reconcile( int $attachment_id, string $reason = 'manual_reconcile' ): AttachmentReconciliationResult {
		if ( null !== $this->controls && $this->controls->read()->paused() ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_QUEUE_PAUSED,
				'Attachment processing is currently paused.'
			);
		}

		if ( $this->is_excluded( $attachment_id ) ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_ATTACHMENT_EXCLUDED,
				'This attachment is excluded from optimization.'
			);
		}

		if ( ! $this->queue->available() ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_QUEUE_UNAVAILABLE,
				'The background queue is unavailable right now.'
			);
		}

		if ( null !== $this->offload ) {
			$support = $this->offload->attachment_support( $attachment_id );

			if ( ! $support->is_supported() ) {
				return AttachmentReconciliationResult::failure(
					AttachmentReconciliationResult::CODE_OFFLOAD_UNSUPPORTED,
					$support->message()
				);
			}
		}

		$fingerprint = $this->fingerprint_for_attachment( $attachment_id );

		if ( ! $fingerprint instanceof AttachmentFingerprint ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_ATTACHMENT_SOURCE_UNAVAILABLE,
				'This attachment does not currently have a valid source fingerprint for queueing.'
			);
		}

		$status = $this->queue->enqueue_reconciliation(
			new ReconciliationJob( $attachment_id, $fingerprint->signature(), $reason )
		);

		if ( ! $status->is_successful() ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_ENQUEUE_FAILED,
				$this->queue_message( $status, 'The requested job could not be queued.' ),
				$status
			);
		}

		if ( ! $this->write_status( $attachment_id, AttachmentStatus::STATE_QUEUED, false ) ) {
			return AttachmentReconciliationResult::failure(
				AttachmentReconciliationResult::CODE_ATTACHMENT_STATE_UPDATE_FAILED,
				'The attachment state could not be updated safely.',
				$status
			);
		}

		$code    = $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) ? AttachmentReconciliationResult::CODE_ALREADY_QUEUED : AttachmentReconciliationResult::CODE_QUEUED;
		$message = AttachmentReconciliationResult::CODE_ALREADY_QUEUED === $code
			? 'An equivalent reconciliation job is already queued.'
			: 'Reconciliation work was queued successfully.';

		return AttachmentReconciliationResult::success( $code, $message, $status );
	}

	/**
	 * Determine whether the attachment is excluded.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private function is_excluded( int $attachment_id ): bool {
		$raw = $this->meta->get( $attachment_id, LifecyclePolicy::META_EXCLUDED, null );

		if ( null !== $raw ) {
			if ( is_bool( $raw ) ) {
				return $raw;
			}

			if ( is_numeric( $raw ) ) {
				return 1 === (int) $raw;
			}

			if ( is_string( $raw ) ) {
				return in_array( strtolower( trim( $raw ) ), array( '1', 'true', 'yes', 'on' ), true );
			}
		}

		return $this->repository->read( $attachment_id )->status()->excluded();
	}

	/**
	 * Build the current fingerprint for one attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentFingerprint|null
	 */
	private function fingerprint_for_attachment( int $attachment_id ): ?AttachmentFingerprint {
		$collected = $this->collector->collect( $attachment_id );

		try {
			$collection = $collected->collection();

			if ( array() === $collection->sources() ) {
				return null;
			}

			return $this->fingerprinter->build( $collection );
		} finally {
			$collected->release();
		}
	}

	/**
	 * Persist a lightweight queued status while preserving ready formats.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $state Attachment state.
	 * @param bool   $excluded Whether excluded.
	 * @return bool
	 */
	private function write_status( int $attachment_id, string $state, bool $excluded ): bool {
		$current = $this->repository->read( $attachment_id )->status();
		$status  = new AttachmentStatus(
			$state,
			$current->formats_ready(),
			$this->clock->now(),
			null,
			$excluded
		);

		return $this->repository->save_status( $attachment_id, $status )->is_successful();
	}

	/**
	 * Get the first queue message or a safe fallback.
	 *
	 * @param QueueStatus $status Queue status.
	 * @param string      $fallback Safe fallback.
	 * @return string
	 */
	private function queue_message( QueueStatus $status, string $fallback ): string {
		$messages = $status->messages();

		if ( isset( $messages[0] ) && is_scalar( $messages[0] ) ) {
			return (string) $messages[0];
		}

		return $fallback;
	}
}
