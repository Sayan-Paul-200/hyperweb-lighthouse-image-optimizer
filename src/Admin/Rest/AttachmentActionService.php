<?php
/**
 * Attachment action service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\AttachmentSourceCollectorInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueResult;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationResult;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentReconciliationService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueStatus;
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
	 * Shared attachment reconciliation queue service.
	 *
	 * @var AttachmentReconciliationService
	 */
	private $reconciliation;

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
	 * @param SettingsRepositoryInterface          $settings Settings repository.
	 * @param AttachmentMetaStoreInterface         $meta Attachment meta store.
	 * @param DerivativeRepository                 $repository Derivative repository.
	 * @param AttachmentSourceCollectorInterface   $collector Source collector.
	 * @param AttachmentFingerprintBuilder         $fingerprinter Fingerprint builder.
	 * @param AttachmentClockInterface             $clock Clock.
	 * @param AttachmentDetailsService             $details Details service.
	 * @param AttachmentQueueService|null          $queueing Shared attachment queue service.
	 * @param QueueControlStateStoreInterface|null $controls Queue control state store.
	 * @param OffloadSupportService|null           $offload Offload support service.
	 */
	public function __construct(
		QueueInterface $queue,
		SettingsRepositoryInterface $settings,
		AttachmentMetaStoreInterface $meta,
		DerivativeRepository $repository,
		AttachmentSourceCollectorInterface $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		AttachmentClockInterface $clock,
		AttachmentDetailsService $details,
		?AttachmentQueueService $queueing = null,
		?QueueControlStateStoreInterface $controls = null,
		?OffloadSupportService $offload = null,
		?AttachmentReconciliationService $reconciliation = null
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
		$this->offload       = $offload;
		$this->queueing      = $queueing ?? new AttachmentQueueService(
			$queue,
			$meta,
			$repository,
			$collector,
			$fingerprinter,
			$clock,
			$controls,
			$offload
		);
		$this->reconciliation = $reconciliation ?? new AttachmentReconciliationService(
			$queue,
			$meta,
			$repository,
			$collector,
			$fingerprinter,
			$clock,
			$controls,
			$offload
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

		if ( $response = $this->offload_unsupported_response( 'optimize', $attachment_id ) ) {
			return $response;
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

		if ( $response = $this->offload_unsupported_response( 'retry', $attachment_id ) ) {
			return $response;
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

		if ( $response = $this->offload_unsupported_response( 'reconcile', $attachment_id ) ) {
			return $response;
		}

		return $this->reconciliation_result_response(
			'reconcile',
			$attachment_id,
			$this->reconciliation->reconcile( $attachment_id, 'manual_reconcile' )
		);
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
	 * Convert a shared reconciliation result into an attachment action response.
	 *
	 * @param string                        $action Action name.
	 * @param int                           $attachment_id Attachment ID.
	 * @param AttachmentReconciliationResult $result Shared result.
	 * @return AttachmentActionResult
	 */
	private function reconciliation_result_response( string $action, int $attachment_id, AttachmentReconciliationResult $result ): AttachmentActionResult {
		$queue = array();

		if ( $result->queue_status() instanceof QueueStatus ) {
			$queue[] = $this->queue_payload( 'reconcile', $result->queue_status() );
		}

		if ( $result->is_successful() ) {
			return AttachmentActionResult::success( $action, $attachment_id, $this->details->details( $attachment_id ), $queue );
		}

		$status_code = AttachmentReconciliationResult::CODE_QUEUE_UNAVAILABLE === $result->code() ? 503 : 409;

		if ( in_array( $result->code(), array( AttachmentReconciliationResult::CODE_ENQUEUE_FAILED, AttachmentReconciliationResult::CODE_ATTACHMENT_STATE_UPDATE_FAILED ), true ) ) {
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
	 * Build a conservative unsupported-offload response when needed.
	 *
	 * @param string $action Action name.
	 * @param int    $attachment_id Attachment ID.
	 * @return AttachmentActionResult|null
	 */
	private function offload_unsupported_response( string $action, int $attachment_id ): ?AttachmentActionResult {
		if ( null === $this->offload ) {
			return null;
		}

		$support = $this->offload->attachment_support( $attachment_id );

		if ( $support->is_supported() ) {
			return null;
		}

		return AttachmentActionResult::failure(
			$action,
			$attachment_id,
			$this->details->details( $attachment_id ),
			AttachmentQueueResult::CODE_OFFLOAD_UNSUPPORTED,
			$support->message(),
			409
		);
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
