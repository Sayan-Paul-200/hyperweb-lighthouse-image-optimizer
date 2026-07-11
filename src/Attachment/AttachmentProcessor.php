<?php
/**
 * Attachment processor.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicy;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicyContext;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionOutput;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionRequest;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationResolver;
use HyperWeb\LighthouseImageOptimizer\Image\ImageConverter;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageValidator;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Orchestrates the end-to-end conversion process for a single attachment.
 */
final class AttachmentProcessor {

	/**
	 * Lock manager.
	 *
	 * @var AttachmentLockManager
	 */
	private $locks;

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
	 * Source validator.
	 *
	 * @var SourceImageValidator
	 */
	private $validator;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Conversion policy.
	 *
	 * @var ConversionPolicy
	 */
	private $policy;

	/**
	 * Image converter.
	 *
	 * @var ImageConverter
	 */
	private $converter;

	/**
	 * Destination resolver.
	 *
	 * @var DestinationResolver
	 */
	private $resolver;

	/**
	 * Create processor.
	 *
	 * @param AttachmentLockManager        $locks Lock manager.
	 * @param SourceCollector              $collector Source collector.
	 * @param AttachmentFingerprintBuilder $fingerprinter Fingerprint builder.
	 * @param DerivativeRepository         $repository Derivative repository.
	 * @param SourceImageValidator         $validator Source validator.
	 * @param SettingsRepositoryInterface  $settings Settings repository.
	 * @param ConversionPolicy             $policy Conversion policy.
	 * @param ImageConverter               $converter Image converter.
	 * @param DestinationResolver          $resolver Destination resolver.
	 */
	public function __construct(
		AttachmentLockManager $locks,
		SourceCollector $collector,
		AttachmentFingerprintBuilder $fingerprinter,
		DerivativeRepository $repository,
		SourceImageValidator $validator,
		SettingsRepositoryInterface $settings,
		ConversionPolicy $policy,
		ImageConverter $converter,
		DestinationResolver $resolver
	) {
		$this->locks         = $locks;
		$this->collector     = $collector;
		$this->fingerprinter = $fingerprinter;
		$this->repository    = $repository;
		$this->validator     = $validator;
		$this->settings      = $settings;
		$this->policy        = $policy;
		$this->converter     = $converter;
		$this->resolver      = $resolver;
	}

	/**
	 * Process a single attachment.
	 *
	 * @param int  $attachment_id Attachment ID.
	 * @param bool $force Whether to bypass derivative reuse checks.
	 * @return AttachmentProcessResult
	 */
	public function process( int $attachment_id, bool $force = false ): AttachmentProcessResult {
		$enabled_formats = $this->settings->enabled_formats();
		$target_format   = $enabled_formats[0] ?? '';

		return $this->process_format( $attachment_id, $target_format, 0, 0, $force );
	}

	/**
	 * Process one target format for one attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $target_format Target format.
	 * @param int    $cursor Source cursor.
	 * @param int    $time_budget_seconds Time budget in seconds. Zero means unbounded.
	 * @param bool   $force Whether to bypass derivative reuse checks.
	 * @return AttachmentProcessResult
	 */
	public function process_format(
		int $attachment_id,
		string $target_format,
		int $cursor = 0,
		int $time_budget_seconds = 0,
		bool $force = false
	): AttachmentProcessResult {
		$attachment_id = max( 0, $attachment_id );
		$target_format = strtolower( trim( $target_format ) );
		$cursor        = max( 0, $cursor );
		$acquired      = $this->locks->acquire( $attachment_id );

		if ( ! $acquired->is_successful() ) {
			return AttachmentProcessResult::locked();
		}

		$lock = $acquired->lock();

		try {
			$this->lifecycle_action( 'hwlio_attachment_process_started', array( $attachment_id, $target_format, $cursor, $force ) );

			$result = $this->do_process( $attachment_id, $target_format, $cursor, $time_budget_seconds, $force );

			$this->lifecycle_action( 'hwlio_attachment_process_completed', array( $attachment_id, $result ) );

			return $result;
		} catch ( \Throwable $e ) {
			$result = AttachmentProcessResult::failure(
				AttachmentProcessResult::CODE_UNEXPECTED_ERROR,
				'An unexpected error occurred during processing: ' . $e->getMessage(),
				$target_format,
				$cursor,
				$cursor
			);

			$this->lifecycle_action( 'hwlio_attachment_process_failed', array( $attachment_id, $result ) );

			return $result;
		} finally {
			if ( $lock instanceof AttachmentLock ) {
				$this->locks->release( $attachment_id, $lock->token() );
			}
		}
	}

	/**
	 * Perform the processing steps.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $target_format Target format.
	 * @param int    $cursor Source cursor.
	 * @param int    $time_budget_seconds Time budget in seconds.
	 * @param bool   $force Force flag.
	 * @return AttachmentProcessResult
	 */
	private function do_process(
		int $attachment_id,
		string $target_format,
		int $cursor,
		int $time_budget_seconds,
		bool $force
	): AttachmentProcessResult {
		if ( '' === $target_format ) {
			$this->repository->save_status(
				$attachment_id,
				new AttachmentStatus( AttachmentStatus::STATE_SKIPPED, array(), 0, AttachmentProcessResult::CODE_SKIPPED_NO_FORMAT, false )
			);

			return AttachmentProcessResult::skip(
				AttachmentProcessResult::CODE_SKIPPED_NO_FORMAT,
				'No target format was provided for attachment processing.',
				null,
				$cursor,
				$cursor
			);
		}

		$collection = $this->collector->collect( $attachment_id );

		if ( array() === $collection->sources() ) {
			$this->repository->save_status(
				$attachment_id,
				new AttachmentStatus( AttachmentStatus::STATE_SKIPPED, array(), 0, AttachmentProcessResult::CODE_SKIPPED_NO_SOURCES, false )
			);

			return AttachmentProcessResult::skip(
				AttachmentProcessResult::CODE_SKIPPED_NO_SOURCES,
				'Attachment has no processable image sources.',
				$target_format,
				$cursor,
				$cursor
			);
		}

		$fingerprint = $this->fingerprinter->build( $collection );

		if ( null === $fingerprint ) {
			$this->repository->save_status(
				$attachment_id,
				new AttachmentStatus( AttachmentStatus::STATE_FAILED, array(), 0, AttachmentProcessResult::CODE_FINGERPRINT_FAILED, false )
			);

			return AttachmentProcessResult::failure(
				AttachmentProcessResult::CODE_FINGERPRINT_FAILED,
				'Attachment fingerprint could not be generated (files may be missing or unreadable).',
				$target_format,
				$cursor,
				$cursor
			);
		}

		$repo_read = $this->repository->read( $attachment_id );
		$manifest  = $repo_read->manifest();
		$status    = $repo_read->status();

		if ( ! $force && $status->excluded() ) {
			return AttachmentProcessResult::skip(
				AttachmentProcessResult::CODE_SKIPPED_EXCLUDED,
				'Attachment is excluded from optimization.',
				$target_format,
				$cursor,
				$cursor
			);
		}

		$validations  = $this->validator->validate_collection( $collection );
		$results      = new ConversionResultCollection();
		$sources      = $collection->sources();
		$source_count = count( $sources );
		$next_cursor  = min( $cursor, $source_count );
		$started_at   = microtime( true );

		foreach ( $sources as $index => $source ) {
			if ( $index < $cursor ) {
				continue;
			}

			$validation = null;
			foreach ( $validations->results() as $v_result ) {
				if ( $v_result->source()->relative_path() === $source->relative_path() ) {
					$validation = $v_result;
					break;
				}
			}

			if ( null === $validation ) {
				continue;
			}

			$context = new ConversionPolicyContext(
				$force,
				$status->excluded(),
				$manifest,
				$fingerprint,
				$validation
			);

			$policy_result = $this->policy->should_convert( $source, $target_format, $context );

			if ( $policy_result->should_convert() ) {
				$resolution = $this->resolver->resolve( $source, $target_format );

				if ( ! $resolution->is_resolved() ) {
					$results     = $results->with_added( $this->result_from_resolution_failure( $source, $target_format, $resolution->code(), $resolution->message() ) );
					$next_cursor = $index + 1;
					continue;
				}

				$request = new ConversionRequest(
					$source,
					$resolution->destination(),
					$this->get_quality_for( $target_format ),
					(float) $this->settings->minimum_savings_percent()
				);

				$results = $results->with_added( $this->converter->convert( $request ) );
			} elseif ( ConversionResultCode::ALREADY_CURRENT === $policy_result->code() ) {
				$reuse = $this->already_current_result( $source, $target_format, $manifest );
				if ( $reuse instanceof ConversionResult ) {
					$results = $results->with_added( $reuse );
				} else {
					$results = $results->with_added(
						ConversionResult::skipped(
							$source,
							$target_format,
							null,
							$policy_result->code(),
							$policy_result->reason(),
							new ConversionSavings( $source->bytes(), null, $this->settings->minimum_savings_percent() )
						)
					);
				}
			} else {
				$results = $results->with_added(
					ConversionResult::skipped(
						$source,
						$target_format,
						null,
						$policy_result->code(),
						$policy_result->reason(),
						new ConversionSavings( $source->bytes(), null, $this->settings->minimum_savings_percent() )
					)
				);
			}

			$next_cursor = $index + 1;

			if ( 0 < $time_budget_seconds && microtime( true ) - $started_at >= $time_budget_seconds && $next_cursor < $source_count ) {
				break;
			}
		}

		$complete    = $next_cursor >= $source_count;
		$final_state = $this->determine_final_state( $results, $complete );
		$repo_result = $this->repository->save_results( $attachment_id, $fingerprint, $results, $final_state );

		$codes    = array_merge( array( AttachmentProcessResult::CODE_PROCESSED ), $repo_result->codes() );
		$messages = $repo_result->messages();

		return AttachmentProcessResult::success( $results, $codes, $messages, $target_format, $cursor, $next_cursor, $complete );
	}

	/**
	 * Get quality setting for format.
	 *
	 * @param string $format Format.
	 * @return int
	 */
	private function get_quality_for( string $format ): int {
		return 'avif' === strtolower( trim( $format ) )
			? $this->settings->quality_for( 'avif' )
			: $this->settings->quality_for( 'webp' );
	}

	/**
	 * Determine the final status state from results.
	 *
	 * @param ConversionResultCollection $results Results.
	 * @param bool                       $complete Whether processing completed.
	 * @return string
	 */
	private function determine_final_state( ConversionResultCollection $results, bool $complete ): string {
		if ( ! $complete ) {
			return AttachmentStatus::STATE_PARTIAL;
		}

		$successful = $results->successful();
		$failed     = $results->failed();
		$skipped    = $results->skipped();

		if ( array() === $successful ) {
			if ( array() !== $failed ) {
				return AttachmentStatus::STATE_FAILED;
			}
			return AttachmentStatus::STATE_SKIPPED;
		}

		if ( array() !== $failed ) {
			return AttachmentStatus::STATE_PARTIAL;
		}

		// Check if any skips were due to genuine failure codes vs already_current.
		$genuine_skips = array_filter(
			$skipped,
			function ( ConversionResult $result ) {
				return ConversionResultCode::ALREADY_CURRENT !== $result->code()
				&& ConversionResultCode::SKIPPED_ANIMATED_IMAGE !== $result->code();
			}
		);

		return array() === $genuine_skips ? AttachmentStatus::STATE_OPTIMIZED : AttachmentStatus::STATE_PARTIAL;
	}

	/**
	 * Build a conversion result from destination resolution failure.
	 *
	 * @param SourceImage $source Source image.
	 * @param string      $target_format Target format.
	 * @param string      $code Resolution code.
	 * @param string      $message Resolution message.
	 * @return ConversionResult
	 */
	private function result_from_resolution_failure( SourceImage $source, string $target_format, string $code, string $message ): ConversionResult {
		if ( 'source_outside_uploads' === $code ) {
			return ConversionResult::skipped(
				$source,
				$target_format,
				null,
				ConversionResultCode::SKIPPED_OUTSIDE_UPLOADS,
				$message,
				new ConversionSavings( $source->bytes(), null, $this->settings->minimum_savings_percent() )
			);
		}

		return ConversionResult::failed(
			$source,
			$target_format,
			null,
			$code,
			$message,
			null,
			new ConversionSavings( $source->bytes(), null, $this->settings->minimum_savings_percent() )
		);
	}

	/**
	 * Build an already-current result from manifest data.
	 *
	 * @param SourceImage        $source Source image.
	 * @param string             $target_format Target format.
	 * @param DerivativeManifest $manifest Manifest.
	 * @return ConversionResult|null
	 */
	private function already_current_result( SourceImage $source, string $target_format, DerivativeManifest $manifest ): ?ConversionResult {
		$resolution = $this->resolver->resolve( $source, $target_format );

		$destination = $resolution->destination();

		if ( ! $resolution->is_resolved() || null === $destination ) {
			return null;
		}

		$sizes = $manifest->sizes();
		$entry = $sizes[ $source->size_name() ]['formats'][ $target_format ] ?? null;

		if ( ! is_array( $entry ) ) {
			return null;
		}

		$output = new ConversionOutput(
			is_scalar( $entry['file'] ?? null ) ? (string) $entry['file'] : $destination->relative_path(),
			is_scalar( $entry['mime'] ?? null ) ? (string) $entry['mime'] : $destination->target_mime(),
			$source->width(),
			$source->height(),
			is_numeric( $entry['bytes'] ?? null ) ? (int) $entry['bytes'] : 0,
			is_numeric( $entry['quality'] ?? null ) ? (int) $entry['quality'] : $this->get_quality_for( $target_format ),
			is_numeric( $entry['generated_at'] ?? null ) ? (int) $entry['generated_at'] : 0
		);

		return ConversionResult::already_current(
			$source,
			$destination,
			$output,
			new ConversionSavings( $source->bytes(), $output->bytes(), $this->settings->minimum_savings_percent() )
		);
	}

	/**
	 * Fire a lifecycle action when WordPress hooks are loaded.
	 *
	 * @param string       $hook Hook name.
	 * @param array<mixed> $args Hook arguments.
	 * @return void
	 */
	private function lifecycle_action( string $hook, array $args ): void {
		if ( function_exists( 'do_action' ) ) {
			\do_action( $hook, ...$args );
		}
	}
}
