<?php
/**
 * Attachment queue service.
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
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Queues selected output formats for one attachment.
 */
final class AttachmentQueueService {

	/**
	 * Queue adapter.
	 *
	 * @var QueueInterface
	 */
	private $queue;

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Source collector.
	 *
	 * @var SourceCollector
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
	 * Create the service.
	 *
	 * @param QueueInterface                       $queue Queue adapter.
	 * @param AttachmentMetaStoreInterface         $meta Meta store.
	 * @param DerivativeRepository                 $repository Repository.
	 * @param SourceCollector                      $collector Source collector.
	 * @param AttachmentFingerprintBuilder         $fingerprinter Fingerprint builder.
	 * @param AttachmentClockInterface             $clock Clock.
	 * @param QueueControlStateStoreInterface|null $controls Optional queue control state store.
	 */
	public function __construct(
		QueueInterface $queue,
		AttachmentMetaStoreInterface $meta,
		DerivativeRepository $repository,
		SourceCollector $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		AttachmentClockInterface $clock,
		?QueueControlStateStoreInterface $controls = null
	) {
		$this->queue         = $queue;
		$this->meta          = $meta;
		$this->repository    = $repository;
		$this->collector     = $collector;
		$this->fingerprinter = $fingerprinter;
		$this->clock         = $clock;
		$this->controls      = $controls;
	}

	/**
	 * Queue one selected target-format set for an attachment.
	 *
	 * @param int      $attachment_id Attachment ID.
	 * @param string[] $formats Selected target formats.
	 * @param string   $reason Queue reason.
	 * @param bool     $force Force flag.
	 * @return AttachmentQueueResult
	 */
	public function queue_selected_formats( int $attachment_id, array $formats, string $reason, bool $force = false ): AttachmentQueueResult {
		if ( null !== $this->controls && $this->controls->read()->paused() ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_QUEUE_PAUSED,
				'Attachment processing is currently paused.'
			);
		}

		if ( $this->is_excluded( $attachment_id ) ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_ATTACHMENT_EXCLUDED,
				'This attachment is excluded from optimization.'
			);
		}

		if ( ! $this->queue->available() ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_QUEUE_UNAVAILABLE,
				'The background queue is unavailable right now.'
			);
		}

		$formats = AttachmentStatus::normalize_formats( $formats );

		if ( array() === $formats ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_NO_ENABLED_FORMATS,
				'No enabled output formats are available for this action.'
			);
		}

		$fingerprint = $this->fingerprint_for_attachment( $attachment_id );

		if ( ! $fingerprint instanceof AttachmentFingerprint ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_ATTACHMENT_SOURCE_UNAVAILABLE,
				'This attachment does not currently have a valid source fingerprint for queueing.'
			);
		}

		$queue_statuses = array();
		$has_success    = false;

		foreach ( $formats as $format ) {
			$status = $this->queue->enqueue_optimization(
				new OptimizationJob( $attachment_id, $format, 0, $force, $reason, $fingerprint->signature() )
			);

			$queue_statuses[ $format ] = $status;

			if ( $status->is_successful() ) {
				$has_success = true;
			}
		}

		if ( ! $has_success ) {
			$first = reset( $queue_statuses );

			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_ENQUEUE_FAILED,
				$first instanceof QueueStatus && array() !== $first->messages() ? (string) $first->messages()[0] : 'The requested job could not be queued.',
				$queue_statuses
			);
		}

		if ( ! $this->write_status( $attachment_id, AttachmentStatus::STATE_QUEUED, false ) ) {
			return AttachmentQueueResult::failure(
				AttachmentQueueResult::CODE_ATTACHMENT_STATE_UPDATE_FAILED,
				'The attachment state could not be updated safely.',
				$queue_statuses
			);
		}

		$all_already_queued = true;

		foreach ( $queue_statuses as $status ) {
			if ( ! $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) ) {
				$all_already_queued = false;
				break;
			}
		}

		return AttachmentQueueResult::success(
			$all_already_queued ? AttachmentQueueResult::CODE_ALREADY_QUEUED : AttachmentQueueResult::CODE_QUEUED,
			$all_already_queued ? 'An equivalent attachment job is already queued.' : 'Attachment work was queued successfully.',
			$queue_statuses
		);
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
		$collection = $this->collector->collect( $attachment_id );

		if ( array() === $collection->sources() ) {
			return null;
		}

		return $this->fingerprinter->build( $collection );
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
}
