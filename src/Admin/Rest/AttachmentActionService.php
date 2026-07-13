<?php
/**
 * Attachment action service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueResult;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
use HyperWeb\LighthouseImageOptimizer\Queue\ReconciliationJob;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Performs attachment-scoped REST actions.
 */
final class AttachmentActionService {

	/**
	 * Queue adapter.
	 *
	 * @var QueueInterface
	 */
	private $queue;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

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
	 * Details service.
	 *
	 * @var AttachmentDetailsService
	 */
	private $details;

	/**
	 * Shared attachment queue service.
	 *
	 * @var AttachmentQueueService
	 */
	private $queueing;

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
	 * @param SettingsRepositoryInterface          $settings Settings repository.
	 * @param AttachmentMetaStoreInterface         $meta Attachment meta store.
	 * @param DerivativeRepository                 $repository Derivative repository.
	 * @param SourceCollector                      $collector Source collector.
	 * @param AttachmentFingerprintBuilder         $fingerprinter Fingerprint builder.
	 * @param AttachmentClockInterface             $clock Clock.
	 * @param AttachmentDetailsService             $details Details service.
	 * @param AttachmentQueueService|null          $queueing Shared attachment queue service.
	 * @param QueueControlStateStoreInterface|null $controls Queue control state store.
	 */
	public function __construct(
		QueueInterface $queue,
		SettingsRepositoryInterface $settings,
		AttachmentMetaStoreInterface $meta,
		DerivativeRepository $repository,
		SourceCollector $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		AttachmentClockInterface $clock,
		AttachmentDetailsService $details,
		?AttachmentQueueService $queueing = null,
		?QueueControlStateStoreInterface $controls = null
	) {
		$this->queue         = $queue;
		$this->settings      = $settings;
		$this->meta          = $meta;
		$this->repository    = $repository;
		$this->collector     = $collector;
		$this->fingerprinter = $fingerprinter;
		$this->clock         = $clock;
		$this->details       = $details;
		$this->controls      = $controls;
		$this->queueing      = $queueing ?? new AttachmentQueueService(
			$queue,
			$meta,
			$repository,
			$collector,
			$fingerprinter,
			$clock,
			$controls
		);
	}

	/**
	 * Queue manual optimization work.
	 *
	 * @param int  $attachment_id Attachment ID.
	 * @param bool $force Whether to force re-optimization.
	 * @return AttachmentActionResult
	 */
	public function optimize( int $attachment_id, bool $force = false ): AttachmentActionResult {
		if ( $this->is_excluded( $attachment_id ) ) {
			return AttachmentActionResult::failure(
				'optimize',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_excluded',
				'This attachment is excluded from optimization. Include it before queueing manual work.',
				409
			);
		}

		$formats = AttachmentStatus::normalize_formats( $this->settings->enabled_formats() );

		return $this->queue_result_response(
			'optimize',
			$attachment_id,
			$this->queueing->queue_selected_formats(
				$attachment_id,
				$formats,
				$force ? 'manual_reoptimize' : 'manual_optimize',
				$force
			)
		);
	}

	/**
	 * Queue retry work.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentActionResult
	 */
	public function retry( int $attachment_id ): AttachmentActionResult {
		if ( $this->is_excluded( $attachment_id ) ) {
			return AttachmentActionResult::failure(
				'retry',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_excluded',
				'This attachment is excluded from optimization. Include it before queueing manual work.',
				409
			);
		}

		$current = $this->repository->read( $attachment_id )->status();

		if ( ! in_array( $current->state(), array( AttachmentStatus::STATE_FAILED, AttachmentStatus::STATE_PARTIAL, AttachmentStatus::STATE_STALE ), true ) ) {
			return AttachmentActionResult::failure(
				'retry',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_not_retryable',
				'Only failed, partial, or stale attachments can be retried.',
				409
			);
		}

		$formats = $this->retry_formats( $current );

		return $this->queue_result_response(
			'retry',
			$attachment_id,
			$this->queueing->queue_selected_formats(
				$attachment_id,
				$formats,
				'manual_retry',
				false
			)
		);
	}

	/**
	 * Queue manual reconciliation work.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentActionResult
	 */
	public function reconcile( int $attachment_id ): AttachmentActionResult {
		if ( $this->is_excluded( $attachment_id ) ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_excluded',
				'This attachment is excluded from optimization. Include it before queueing manual work.',
				409
			);
		}

		if ( ! $this->queue->available() ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'queue_unavailable',
				'The background queue is unavailable right now.',
				503
			);
		}

		if ( null !== $this->controls && $this->controls->read()->paused() ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'queue_paused',
				'Attachment processing is currently paused.',
				409
			);
		}

		$fingerprint = $this->fingerprint_for_attachment( $attachment_id );

		if ( ! $fingerprint instanceof AttachmentFingerprint ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_source_unavailable',
				'This attachment does not currently have a valid source fingerprint for queueing.',
				409
			);
		}

		$status = $this->queue->enqueue_reconciliation(
			new ReconciliationJob( $attachment_id, $fingerprint->signature(), 'manual_reconcile' )
		);
		$queue  = array(
			$this->queue_payload( 'reconcile', $status ),
		);

		if ( ! $status->is_successful() ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'queue_enqueue_failed',
				$this->queue_message( $status, 'The requested job could not be queued.' ),
				500,
				$queue
			);
		}

		if ( ! $this->write_status( $attachment_id, AttachmentStatus::STATE_QUEUED, false, array() ) ) {
			return AttachmentActionResult::failure(
				'reconcile',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_state_update_failed',
				'The attachment state could not be updated safely.',
				500,
				$queue
			);
		}

		return AttachmentActionResult::success( 'reconcile', $attachment_id, $this->details->details( $attachment_id ), $queue );
	}

	/**
	 * Exclude the attachment from optimization.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentActionResult
	 */
	public function exclude( int $attachment_id ): AttachmentActionResult {
		if ( ! $this->settings->attachment_exclusion_allowed() ) {
			return AttachmentActionResult::failure(
				'exclude',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_exclusion_disabled',
				'Per-attachment exclusion is disabled for this site.',
				409
			);
		}

		$this->meta->update( $attachment_id, LifecyclePolicy::META_EXCLUDED, true );

		if ( ! $this->write_status( $attachment_id, AttachmentStatus::STATE_EXCLUDED, true, array() ) ) {
			return AttachmentActionResult::failure(
				'exclude',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_state_update_failed',
				'The attachment state could not be updated safely.',
				500
			);
		}

		return AttachmentActionResult::success( 'exclude', $attachment_id, $this->details->details( $attachment_id ) );
	}

	/**
	 * Include the attachment in optimization again.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentActionResult
	 */
	public function include( int $attachment_id ): AttachmentActionResult {
		if ( ! $this->settings->attachment_exclusion_allowed() ) {
			return AttachmentActionResult::failure(
				'include',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_exclusion_disabled',
				'Per-attachment exclusion is disabled for this site.',
				409
			);
		}

		$this->meta->delete( $attachment_id, LifecyclePolicy::META_EXCLUDED );

		if ( ! $this->write_status( $attachment_id, $this->included_state( $attachment_id ), false, array() ) ) {
			return AttachmentActionResult::failure(
				'include',
				$attachment_id,
				$this->details->details( $attachment_id ),
				'attachment_state_update_failed',
				'The attachment state could not be updated safely.',
				500
			);
		}

		return AttachmentActionResult::success( 'include', $attachment_id, $this->details->details( $attachment_id ) );
	}

	/**
	 * Convert a shared queue result into an attachment action response.
	 *
	 * @param string                $action Action name.
	 * @param int                   $attachment_id Attachment ID.
	 * @param AttachmentQueueResult $result Queue result.
	 * @return AttachmentActionResult
	 */
	private function queue_result_response( string $action, int $attachment_id, AttachmentQueueResult $result ): AttachmentActionResult {
		$queue = array();

		foreach ( $result->queue_statuses() as $target => $status ) {
			$queue[] = $this->queue_payload( $target, $status );
		}

		if ( $result->is_successful() ) {
			return AttachmentActionResult::success( $action, $attachment_id, $this->details->details( $attachment_id ), $queue );
		}

		$status_code = AttachmentQueueResult::CODE_QUEUE_UNAVAILABLE === $result->code() ? 503 : 409;

		if ( AttachmentQueueResult::CODE_ENQUEUE_FAILED === $result->code() || AttachmentQueueResult::CODE_ATTACHMENT_STATE_UPDATE_FAILED === $result->code() ) {
			$status_code = 500;
		}

		return AttachmentActionResult::failure(
			$action,
			$attachment_id,
			$this->details->details( $attachment_id ),
			$result->code(),
			$result->message(),
			$status_code,
			$queue
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
			return $this->truthy( $raw );
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
	 * Resolve retry formats conservatively from the current status snapshot.
	 *
	 * @param AttachmentStatus $current Current status.
	 * @return string[]
	 */
	private function retry_formats( AttachmentStatus $current ): array {
		$enabled = AttachmentStatus::normalize_formats( $this->settings->enabled_formats() );

		if ( in_array( $current->state(), array( AttachmentStatus::STATE_FAILED, AttachmentStatus::STATE_STALE ), true ) ) {
			return $enabled;
		}

		return array_values( array_diff( $enabled, $current->formats_ready() ) );
	}

	/**
	 * Determine whether a stored meta value is truthy.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return 1 === (int) $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return false;
	}

	/**
	 * Persist a lightweight status update while preserving ready formats.
	 *
	 * @param int      $attachment_id Attachment ID.
	 * @param string   $state Status state.
	 * @param bool     $excluded Whether excluded.
	 * @param string[] $codes Ignored extra codes reserved for future use.
	 * @return bool
	 */
	private function write_status( int $attachment_id, string $state, bool $excluded, array $codes ): bool {
		unset( $codes );

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
	 * Determine the restored state after inclusion.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private function included_state( int $attachment_id ): string {
		$current = $this->repository->read( $attachment_id )->status();
		$ready   = $current->formats_ready();

		if ( array() === $ready ) {
			return AttachmentStatus::STATE_UNPROCESSED;
		}

		$enabled = AttachmentStatus::normalize_formats( $this->settings->enabled_formats() );

		if ( array() !== $enabled && array() === array_diff( $enabled, $ready ) ) {
			return AttachmentStatus::STATE_OPTIMIZED;
		}

		return AttachmentStatus::STATE_PARTIAL;
	}

	/**
	 * Convert one queue status into a safe response payload.
	 *
	 * @param string      $target Target format or action name.
	 * @param QueueStatus $status Queue status.
	 * @return array<string,mixed>
	 */
	private function queue_payload( string $target, QueueStatus $status ): array {
		return array(
			'target'              => $target,
			'successful'          => $status->is_successful(),
			'action_id'           => $status->action_id(),
			'async'               => $status->is_async(),
			'scheduled_timestamp' => $status->scheduled_timestamp(),
			'codes'               => $status->codes(),
			'messages'            => $this->sanitize_messages( $status->messages() ),
		);
	}

	/**
	 * Read the first safe queue message.
	 *
	 * @param QueueStatus $status Queue status.
	 * @param string      $fallback Fallback message.
	 * @return string
	 */
	private function queue_message( QueueStatus $status, string $fallback ): string {
		$messages = $this->sanitize_messages( $status->messages() );

		return isset( $messages[0] ) ? $messages[0] : $fallback;
	}

	/**
	 * Sanitize queue messages for REST output.
	 *
	 * @param string[] $messages Messages.
	 * @return string[]
	 */
	private function sanitize_messages( array $messages ): array {
		$sanitized = array();

		foreach ( $messages as $message ) {
			if ( ! is_scalar( $message ) ) {
				continue;
			}

			$message = trim( (string) $message );

			if ( '' === $message ) {
				continue;
			}

			$message = (string) preg_replace(
				'/(^|[\s({\[=:])(?:[A-Za-z]:[\\\\\/][^\s<>"\')\]}]+)/',
				'$1[redacted_path]',
				$message
			);
			$message = (string) preg_replace(
				'/(^|[\s({\[=:])(?:\/[^\s<>"\')\]}]+(?:\/[^\s<>"\')\]}]+)+)/',
				'$1[redacted_path]',
				$message
			);

			$sanitized[] = $message;
		}

		return array_values( array_unique( $sanitized ) );
	}
}
