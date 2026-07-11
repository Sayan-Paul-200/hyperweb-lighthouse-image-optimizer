<?php
/**
 * New-upload integration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentExclusionRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentExclusionRepositoryInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Logging\LogCode;
use HyperWeb\LighthouseImageOptimizer\Logging\Logger;
use HyperWeb\LighthouseImageOptimizer\Logging\LoggerInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Queues optimization work after WordPress finishes generating attachment metadata.
 */
final class NewUploadIntegration implements HookProviderInterface {

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
	 * Exclusion repository.
	 *
	 * @var AttachmentExclusionRepositoryInterface
	 */
	private $exclusions;

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
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Attachment-image check.
	 *
	 * @var callable
	 */
	private $is_image_attachment;

	/**
	 * Internal refresh dispatcher.
	 *
	 * @var callable
	 */
	private $dispatch_refresh;

	/**
	 * Build the WordPress-backed integration.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			ActionSchedulerQueue::for_wordpress(),
			SettingsRepository::for_wordpress(),
			AttachmentExclusionRepository::for_wordpress(),
			SourceCollector::for_wordpress(),
			new AttachmentFingerprintBuilder(),
			DerivativeRepository::for_wordpress(),
			Logger::for_wordpress(),
			new SystemAttachmentClock(),
			static function ( int $attachment_id ): bool {
				return function_exists( 'wp_attachment_is_image' ) && \wp_attachment_is_image( $attachment_id );
			},
			static function ( int $attachment_id, string $context, array $status, array $payload ): void {
				if ( function_exists( 'do_action' ) ) {
					\do_action(
						LifecyclePolicy::HOOK_ATTACHMENT_STATUS_REFRESH,
						$attachment_id,
						$context,
						$status,
						$payload
					);
				}
			}
		);
	}

	/**
	 * Create integration.
	 *
	 * @param QueueInterface                         $queue Queue adapter.
	 * @param SettingsRepositoryInterface            $settings Settings repository.
	 * @param AttachmentExclusionRepositoryInterface $exclusions Exclusion repository.
	 * @param SourceCollector                        $collector Source collector.
	 * @param AttachmentFingerprintBuilder           $fingerprinter Fingerprint builder.
	 * @param DerivativeRepository                   $repository Derivative repository.
	 * @param LoggerInterface                        $logger Logger.
	 * @param AttachmentClockInterface               $clock Clock.
	 * @param callable                               $is_image_attachment Attachment-image check.
	 * @param callable                               $dispatch_refresh Internal refresh dispatcher.
	 */
	public function __construct(
		QueueInterface $queue,
		SettingsRepositoryInterface $settings,
		AttachmentExclusionRepositoryInterface $exclusions,
		SourceCollector $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		DerivativeRepository $repository,
		LoggerInterface $logger,
		AttachmentClockInterface $clock,
		callable $is_image_attachment,
		callable $dispatch_refresh
	) {
		$this->queue               = $queue;
		$this->settings            = $settings;
		$this->exclusions          = $exclusions;
		$this->collector           = $collector;
		$this->fingerprinter       = $fingerprinter;
		$this->repository          = $repository;
		$this->logger              = $logger;
		$this->clock               = $clock;
		$this->is_image_attachment = $is_image_attachment;
		$this->dispatch_refresh    = $dispatch_refresh;
	}

	/**
	 * Register upload metadata hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter(
			'wp_generate_attachment_metadata',
			array( $this, 'handle_generated_metadata' ),
			10,
			3
		);
	}

	/**
	 * Queue optimization jobs after attachment metadata generation.
	 *
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $context Metadata generation context.
	 * @return array<string,mixed>
	 */
	public function handle_generated_metadata( array $metadata, int $attachment_id, string $context ): array {
		$attachment_id = max( 0, $attachment_id );
		$context       = strtolower( trim( $context ) );

		if ( 0 === $attachment_id ) {
			return $metadata;
		}

		if ( ! $this->is_image_attachment( $attachment_id ) ) {
			$this->logger->info(
				LogCode::NEW_UPLOAD_IGNORED,
				'New-upload automation ignored a non-image attachment.',
				array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $this->current_status( $attachment_id ), $this->refresh_payload() );

			return $metadata;
		}

		if ( 'update' === $context ) {
			$this->logger->info(
				LogCode::NEW_UPLOAD_IGNORED,
				'New-upload automation ignored an attachment metadata update context.',
				array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $this->current_status( $attachment_id ), $this->refresh_payload() );

			return $metadata;
		}

		if ( 'create' !== $context ) {
			$this->logger->info(
				LogCode::NEW_UPLOAD_IGNORED,
				'New-upload automation ignored an unsupported metadata generation context.',
				array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $this->current_status( $attachment_id ), $this->refresh_payload() );

			return $metadata;
		}

		$current_status = $this->current_status( $attachment_id );

		if ( ! $this->settings->automatic_optimization_enabled() ) {
			$status = $this->save_status(
				$attachment_id,
				AttachmentStatus::STATE_UNPROCESSED,
				null,
				false,
				$current_status
			);

			$this->logger->info(
				LogCode::NEW_UPLOAD_AUTOMATION_DISABLED,
				'New-upload automation is disabled; attachment remained unprocessed.',
				array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $status, $this->refresh_payload() );

			return $metadata;
		}

		if ( $this->exclusions->is_excluded( $attachment_id ) ) {
			$status = $this->save_status(
				$attachment_id,
				AttachmentStatus::STATE_EXCLUDED,
				null,
				true,
				$current_status
			);

			$this->logger->info(
				LogCode::NEW_UPLOAD_EXCLUDED,
				'New-upload automation skipped an excluded attachment.',
				array(
					'attachment_id' => $attachment_id,
					'context'       => $context,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $status, $this->refresh_payload() );

			return $metadata;
		}

		$enabled_formats = $this->settings->enabled_formats();
		$collection      = $this->collector->collect( $attachment_id );
		$fingerprint     = $this->fingerprinter->build( $collection );

		if ( array() === $enabled_formats || array() === $collection->sources() || ! $fingerprint instanceof AttachmentFingerprint ) {
			$status = $this->save_status(
				$attachment_id,
				AttachmentStatus::STATE_UNPROCESSED,
				null,
				false,
				$current_status
			);

			$this->logger->warning(
				LogCode::NEW_UPLOAD_IGNORED,
				'New-upload automation could not queue the attachment because no valid source fingerprint was available.',
				array(
					'attachment_id'   => $attachment_id,
					'context'         => $context,
					'has_sources'     => array() !== $collection->sources(),
					'has_fingerprint' => $fingerprint instanceof AttachmentFingerprint,
					'enabled_formats' => $enabled_formats,
				),
				$attachment_id
			);

			$this->dispatch_refresh( $attachment_id, $context, $status, $this->refresh_payload() );

			return $metadata;
		}

		$queued_formats = array();
		$failed_formats = array();
		$result_codes   = array();
		$first_failure  = null;

		foreach ( $enabled_formats as $format ) {
			$status = $this->queue->enqueue_optimization(
				new OptimizationJob(
					$attachment_id,
					$format,
					0,
					false,
					'new_upload',
					$fingerprint->signature()
				)
			);

			$result_codes[ $format ] = $this->primary_code( $status );

			if ( $status->has_code( QueueStatus::CODE_QUEUED ) || $status->has_code( QueueStatus::CODE_ALREADY_QUEUED ) ) {
				$queued_formats[] = $format;
				continue;
			}

			$failed_formats[] = $format;

			if ( null === $first_failure ) {
				$first_failure = $this->primary_code( $status );
			}
		}

		if ( array() !== $queued_formats ) {
			$status = $this->save_status(
				$attachment_id,
				AttachmentStatus::STATE_QUEUED,
				null,
				false,
				$current_status
			);

			$this->logger->info(
				LogCode::NEW_UPLOAD_QUEUED,
				'New-upload automation queued optimization jobs for the attachment.',
				array(
					'attachment_id'  => $attachment_id,
					'context'        => $context,
					'queued_formats' => $queued_formats,
					'failed_formats' => $failed_formats,
					'result_codes'   => $result_codes,
				),
				$attachment_id
			);

			$this->dispatch_refresh(
				$attachment_id,
				$context,
				$status,
				$this->refresh_payload( $queued_formats, $failed_formats, $result_codes )
			);

			return $metadata;
		}

		$status = $this->save_status(
			$attachment_id,
			AttachmentStatus::STATE_UNPROCESSED,
			$first_failure,
			false,
			$current_status
		);

		$this->logger->warning(
			LogCode::NEW_UPLOAD_QUEUE_FAILED,
			'New-upload automation could not queue any optimization jobs for the attachment.',
			array(
				'attachment_id'  => $attachment_id,
				'context'        => $context,
				'queued_formats' => $queued_formats,
				'failed_formats' => $failed_formats,
				'result_codes'   => $result_codes,
			),
			$attachment_id
		);

		$this->dispatch_refresh(
			$attachment_id,
			$context,
			$status,
			$this->refresh_payload( $queued_formats, $failed_formats, $result_codes )
		);

		return $metadata;
	}

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private function is_image_attachment( int $attachment_id ): bool {
		try {
			return (bool) call_user_func( $this->is_image_attachment, $attachment_id );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Dispatch the internal status refresh action.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $context Context.
	 * @param AttachmentStatus    $status Current status.
	 * @param array<string,mixed> $payload Refresh payload.
	 * @return void
	 */
	private function dispatch_refresh( int $attachment_id, string $context, AttachmentStatus $status, array $payload ): void {
		call_user_func(
			$this->dispatch_refresh,
			$attachment_id,
			$context,
			$status->to_array(),
			$payload
		);
	}

	/**
	 * Read current attachment status.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentStatus
	 */
	private function current_status( int $attachment_id ): AttachmentStatus {
		return $this->repository->read( $attachment_id )->status();
	}

	/**
	 * Save lightweight status while preserving ready formats.
	 *
	 * @param int              $attachment_id Attachment ID.
	 * @param string           $state State.
	 * @param string|null      $error_code Error code.
	 * @param bool             $excluded Whether excluded.
	 * @param AttachmentStatus $current_status Current status.
	 * @return AttachmentStatus
	 */
	private function save_status(
		int $attachment_id,
		string $state,
		?string $error_code,
		bool $excluded,
		AttachmentStatus $current_status
	): AttachmentStatus {
		$status = new AttachmentStatus(
			$state,
			$current_status->formats_ready(),
			$this->clock->now(),
			$error_code,
			$excluded
		);

		$this->repository->save_status( $attachment_id, $status );

		return $status;
	}

	/**
	 * Get the first machine-readable queue code.
	 *
	 * @param QueueStatus $status Queue status.
	 * @return string|null
	 */
	private function primary_code( QueueStatus $status ): ?string {
		$codes = $status->codes();

		return $codes[0] ?? null;
	}

	/**
	 * Build refresh payload.
	 *
	 * @param string[]                  $queued_formats Queued formats.
	 * @param string[]                  $failed_formats Failed formats.
	 * @param array<string,string|null> $result_codes Result codes by format.
	 * @return array<string,mixed>
	 */
	private function refresh_payload(
		array $queued_formats = array(),
		array $failed_formats = array(),
		array $result_codes = array()
	): array {
		return array(
			'queued_formats' => array_values( $queued_formats ),
			'failed_formats' => array_values( $failed_formats ),
			'result_codes'   => $result_codes,
		);
	}
}
