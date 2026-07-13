<?php
/**
 * Derivative repository.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionOutput;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationDispatcherInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressCacheInvalidationDispatcher;

/**
 * Reads and writes plugin-owned attachment derivative metadata.
 */
final class DerivativeRepository {

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Manifest sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Cache invalidation dispatcher.
	 *
	 * @var CacheInvalidationDispatcherInterface|null
	 */
	private $cache_invalidation;

	/**
	 * Build WordPress-backed repository.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentMetaStore(),
			new DerivativeManifestSanitizer(),
			new SystemAttachmentClock(),
			new WordPressCacheInvalidationDispatcher()
		);
	}

	/**
	 * Create repository.
	 *
	 * @param AttachmentMetaStoreInterface              $meta Meta store.
	 * @param DerivativeManifestSanitizer               $sanitizer Manifest sanitizer.
	 * @param AttachmentClockInterface                  $clock Clock.
	 * @param CacheInvalidationDispatcherInterface|null $cache_invalidation Cache invalidation dispatcher.
	 */
	public function __construct(
		AttachmentMetaStoreInterface $meta,
		DerivativeManifestSanitizer $sanitizer,
		AttachmentClockInterface $clock,
		?CacheInvalidationDispatcherInterface $cache_invalidation = null
	) {
		$this->meta               = $meta;
		$this->sanitizer          = $sanitizer;
		$this->clock              = $clock;
		$this->cache_invalidation = $cache_invalidation;
	}

	/**
	 * Read derivative manifest and status.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return DerivativeRepositoryResult
	 */
	public function read( int $attachment_id ): DerivativeRepositoryResult {
		$attachment_id = max( 0, $attachment_id );
		$raw_manifest  = $this->meta->get( $attachment_id, LifecyclePolicy::META_DERIVATIVES, null );
		$sanitized     = $this->sanitizer->sanitize( $raw_manifest );
		$manifest      = $sanitized['manifest'];
		$codes         = $sanitized['codes'];
		$messages      = $sanitized['messages'];
		$raw_status    = $this->meta->get( $attachment_id, LifecyclePolicy::META_STATUS, null );
		$status        = AttachmentStatus::from_stored( $raw_status );

		if ( $this->status_needs_repair( $raw_status, $status ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_INVALID_STATUS_REPAIRED;
			$messages[] = 'Stored attachment status was invalid and was normalized.';
		}

		return new DerivativeRepositoryResult(
			true,
			in_array( DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED, $codes, true )
				|| in_array( DerivativeRepositoryResult::CODE_INVALID_STATUS_REPAIRED, $codes, true ),
			$manifest,
			$status,
			$codes,
			$messages
		);
	}

	/**
	 * Save conversion results into derivative metadata.
	 *
	 * @param int                        $attachment_id Attachment ID.
	 * @param AttachmentFingerprint      $fingerprint Current fingerprint.
	 * @param ConversionResultCollection $results Conversion results.
	 * @param string                     $state Desired status state.
	 * @return DerivativeRepositoryResult
	 */
	public function save_results(
		int $attachment_id,
		AttachmentFingerprint $fingerprint,
		ConversionResultCollection $results,
		string $state = AttachmentStatus::STATE_PARTIAL
	): DerivativeRepositoryResult {
		$attachment_id = max( 0, $attachment_id );
		$read          = $this->read( $attachment_id );
		$manifest      = $read->manifest();
		$codes         = $read->codes();
		$messages      = $read->messages();
		$stored        = $manifest->fingerprint();

		if ( $stored instanceof AttachmentFingerprint && ! $this->fingerprints_match( $stored, $fingerprint ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_FINGERPRINT_MISMATCH;
			$messages[] = 'Stored derivative metadata belongs to a different attachment fingerprint and was preserved.';

			return new DerivativeRepositoryResult(
				false,
				true,
				$manifest,
				$read->status(),
				$codes,
				$messages
			);
		}

		$updated_at = $this->clock->now();
		$sizes      = $manifest->sizes();
		$ready      = $this->merge_successful_results( $sizes, $results );

		if ( 0 < $ready ) {
			$manifest = $manifest->with_data( $fingerprint, $updated_at, $sizes );

			if ( ! $this->meta->update( $attachment_id, LifecyclePolicy::META_DERIVATIVES, $manifest->to_array() ) ) {
				$codes[]    = DerivativeRepositoryResult::CODE_WRITE_FAILED;
				$messages[] = 'Derivative metadata could not be saved.';

				return new DerivativeRepositoryResult( false, true, $manifest, $read->status(), $codes, $messages );
			}

			$codes[] = DerivativeRepositoryResult::CODE_SAVED;
			$this->dispatch_derivatives_saved( $attachment_id, $results, $updated_at );
		} else {
			$codes[] = DerivativeRepositoryResult::CODE_NO_READY_RESULTS;
		}

		$status = $this->status_from_results( $state, $manifest, $results, $updated_at );

		if ( ! $this->meta->update( $attachment_id, LifecyclePolicy::META_STATUS, $status->to_array() ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_WRITE_FAILED;
			$messages[] = 'Attachment status metadata could not be saved.';

			return new DerivativeRepositoryResult( false, true, $manifest, $status, $codes, $messages );
		}

		$codes[] = DerivativeRepositoryResult::CODE_STATUS_SAVED;

		return new DerivativeRepositoryResult(
			true,
			$read->has_warnings(),
			$manifest,
			$status,
			$codes,
			$messages
		);
	}

	/**
	 * Save attachment status.
	 *
	 * @param int              $attachment_id Attachment ID.
	 * @param AttachmentStatus $status Status.
	 * @return DerivativeRepositoryResult
	 */
	public function save_status( int $attachment_id, AttachmentStatus $status ): DerivativeRepositoryResult {
		$attachment_id = max( 0, $attachment_id );
		$read          = $this->read( $attachment_id );
		$codes         = $read->codes();
		$messages      = $read->messages();

		if ( ! $this->meta->update( $attachment_id, LifecyclePolicy::META_STATUS, $status->to_array() ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_WRITE_FAILED;
			$messages[] = 'Attachment status metadata could not be saved.';

			return new DerivativeRepositoryResult( false, true, $read->manifest(), $status, $codes, $messages );
		}

		$codes[] = DerivativeRepositoryResult::CODE_STATUS_SAVED;

		return new DerivativeRepositoryResult( true, $read->has_warnings(), $read->manifest(), $status, $codes, $messages );
	}

	/**
	 * Begin reconciliation by replacing the active manifest with an empty manifest for the current fingerprint.
	 *
	 * @param int                   $attachment_id Attachment ID.
	 * @param AttachmentFingerprint $fingerprint Current fingerprint.
	 * @return DerivativeRepositoryResult
	 */
	public function begin_reconciliation( int $attachment_id, AttachmentFingerprint $fingerprint ): DerivativeRepositoryResult {
		$attachment_id = max( 0, $attachment_id );
		$read          = $this->read( $attachment_id );
		$manifest      = DerivativeManifest::empty()->with_data( $fingerprint, $this->clock->now(), array() );
		$codes         = $read->codes();
		$messages      = $read->messages();

		if ( ! $this->meta->update( $attachment_id, LifecyclePolicy::META_DERIVATIVES, $manifest->to_array() ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_WRITE_FAILED;
			$messages[] = 'Derivative metadata could not be reset for reconciliation.';

			return new DerivativeRepositoryResult( false, true, $read->manifest(), $read->status(), $codes, $messages );
		}

		$codes[] = DerivativeRepositoryResult::CODE_RECONCILIATION_STARTED;

		return new DerivativeRepositoryResult(
			true,
			$read->has_warnings(),
			$manifest,
			$read->status(),
			$codes,
			$messages
		);
	}

	/**
	 * Delete plugin-owned derivative metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return DerivativeRepositoryResult
	 */
	public function delete( int $attachment_id ): DerivativeRepositoryResult {
		$attachment_id = max( 0, $attachment_id );
		$manifest_ok   = $this->meta->delete( $attachment_id, LifecyclePolicy::META_DERIVATIVES );
		$status_ok     = $this->meta->delete( $attachment_id, LifecyclePolicy::META_STATUS );
		$codes         = array( DerivativeRepositoryResult::CODE_DELETED );
		$messages      = array();

		if ( ! $manifest_ok || ! $status_ok ) {
			$codes[]    = DerivativeRepositoryResult::CODE_DELETE_FAILED;
			$messages[] = 'Derivative metadata could not be fully deleted.';
		}

		return new DerivativeRepositoryResult(
			$manifest_ok && $status_ok,
			! $manifest_ok || ! $status_ok,
			DerivativeManifest::empty(),
			AttachmentStatus::unprocessed(),
			$codes,
			$messages
		);
	}

	/**
	 * Merge successful conversion results into sizes by reference.
	 *
	 * @param array<string,array<string,mixed>> $sizes Sizes.
	 * @param ConversionResultCollection        $results Results.
	 * @return int Number of ready results merged.
	 */
	private function merge_successful_results( array &$sizes, ConversionResultCollection $results ): int {
		$ready = 0;

		foreach ( $results->successful() as $result ) {
			$output = $result->output();

			if ( ! $output instanceof ConversionOutput ) {
				continue;
			}

			$format = strtolower( trim( $result->target_format() ) );

			if ( ! in_array( $format, AttachmentStatus::formats(), true ) ) {
				continue;
			}

			$source = $this->source_entry( $result->source() );
			$entry  = $this->format_entry( $format, $result, $output );

			if ( null === $source || null === $entry ) {
				continue;
			}

			$size_name = substr( $result->source()->size_name(), 0, 64 );

			if ( '' === $size_name ) {
				$size_name = 'unknown';
			}

			if ( ! isset( $sizes[ $size_name ] ) ) {
				$sizes[ $size_name ] = array(
					'source'  => $source,
					'formats' => array(),
				);
			}

			$sizes[ $size_name ]['source'] = $source;

			if ( ! isset( $sizes[ $size_name ]['formats'] ) || ! is_array( $sizes[ $size_name ]['formats'] ) ) {
				$sizes[ $size_name ]['formats'] = array();
			}

			$sizes[ $size_name ]['formats'][ $format ] = $entry;
			++$ready;
		}

		ksort( $sizes );

		return $ready;
	}

	/**
	 * Dispatch cache invalidation for successful derivative outputs.
	 *
	 * @param int                        $attachment_id Attachment ID.
	 * @param ConversionResultCollection $results Results.
	 * @param int                        $updated_at Timestamp.
	 * @return void
	 */
	private function dispatch_derivatives_saved( int $attachment_id, ConversionResultCollection $results, int $updated_at ): void {
		if ( ! $this->cache_invalidation instanceof CacheInvalidationDispatcherInterface ) {
			return;
		}

		$paths   = array();
		$formats = array();

		foreach ( $results->successful() as $result ) {
			$output = $result->output();

			if ( ! $output instanceof ConversionOutput ) {
				continue;
			}

			$paths[]   = $output->relative_path();
			$formats[] = $result->target_format();
		}

		if ( array() === $paths ) {
			return;
		}

		$this->cache_invalidation->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_SAVED,
				$attachment_id,
				'save_results',
				$paths,
				$formats,
				gmdate( 'Y-m-d H:i:s', $updated_at )
			)
		);
	}

	/**
	 * Build source manifest entry.
	 *
	 * @param SourceImage $source Source image.
	 * @return array<string,mixed>|null
	 */
	private function source_entry( SourceImage $source ): ?array {
		$file = $this->sanitizer->safe_relative_path( $source->relative_path() );

		if ( '' === $file || null === $source->mime_type() ) {
			return null;
		}

		return array(
			'file'   => $file,
			'width'  => $source->width(),
			'height' => $source->height(),
			'mime'   => $source->mime_type(),
			'bytes'  => $source->bytes(),
		);
	}

	/**
	 * Build format manifest entry.
	 *
	 * @param string           $format Format.
	 * @param ConversionResult $result Result.
	 * @param ConversionOutput $output Output.
	 * @return array<string,mixed>|null
	 */
	private function format_entry( string $format, ConversionResult $result, ConversionOutput $output ): ?array {
		$file = $this->sanitizer->safe_relative_path( $output->relative_path() );

		if ( '' === $file || $this->sanitizer->expected_mime( $format ) !== $output->mime_type() ) {
			return null;
		}

		return array(
			'file'            => $file,
			'mime'            => $output->mime_type(),
			'bytes'           => $output->bytes(),
			'quality'         => $output->quality(),
			'savings_bytes'   => $result->savings()->savings_bytes(),
			'savings_percent' => $result->savings()->savings_percent(),
			'status'          => DerivativeManifest::FORMAT_STATUS_READY,
			'generated_at'    => $output->generated_at(),
		);
	}

	/**
	 * Build status from conversion results.
	 *
	 * @param string                     $state Requested state.
	 * @param DerivativeManifest         $manifest Manifest.
	 * @param ConversionResultCollection $results Results.
	 * @param int                        $updated_at Updated timestamp.
	 * @return AttachmentStatus
	 */
	private function status_from_results(
		string $state,
		DerivativeManifest $manifest,
		ConversionResultCollection $results,
		int $updated_at
	): AttachmentStatus {
		$state      = AttachmentStatus::normalize_state( $state );
		$error_code = null;

		if ( array() === $results->successful() ) {
			$failed  = $results->failed();
			$skipped = $results->skipped();

			if ( array() !== $failed ) {
				$state      = AttachmentStatus::STATE_FAILED;
				$error_code = $failed[0]->code();
			} elseif ( array() !== $skipped ) {
				$state      = AttachmentStatus::STATE_SKIPPED;
				$error_code = $skipped[0]->code();
			}
		}

		return new AttachmentStatus( $state, $manifest->ready_formats(), $updated_at, $error_code, false );
	}

	/**
	 * Determine whether fingerprints describe the same source state.
	 *
	 * @param AttachmentFingerprint $left Left fingerprint.
	 * @param AttachmentFingerprint $right Right fingerprint.
	 * @return bool
	 */
	private function fingerprints_match( AttachmentFingerprint $left, AttachmentFingerprint $right ): bool {
		return $left->relative_file() === $right->relative_file()
			&& $left->file_size() === $right->file_size()
			&& $left->modified_time() === $right->modified_time()
			&& $left->metadata_hash() === $right->metadata_hash();
	}

	/**
	 * Whether stored status was normalized.
	 *
	 * @param mixed            $raw_status Raw status.
	 * @param AttachmentStatus $status Normalized status.
	 * @return bool
	 */
	private function status_needs_repair( $raw_status, AttachmentStatus $status ): bool {
		if ( null === $raw_status || false === $raw_status || '' === $raw_status ) {
			return false;
		}

		if ( ! is_array( $raw_status ) ) {
			return true;
		}

		return $status->to_array() !== array(
			'state'      => isset( $raw_status['state'] ) && is_scalar( $raw_status['state'] ) ? strtolower( trim( (string) $raw_status['state'] ) ) : AttachmentStatus::STATE_UNPROCESSED,
			'formats'    => isset( $raw_status['formats'] ) && is_array( $raw_status['formats'] ) ? AttachmentStatus::normalize_formats( $raw_status['formats'] ) : array(),
			'updated_at' => isset( $raw_status['updated_at'] ) && is_numeric( $raw_status['updated_at'] ) ? max( 0, (int) $raw_status['updated_at'] ) : 0,
			'error_code' => isset( $raw_status['error_code'] ) && is_scalar( $raw_status['error_code'] ) ? $this->normalize_error_code_for_compare( (string) $raw_status['error_code'] ) : null,
			'excluded'   => isset( $raw_status['excluded'] ) && (bool) $raw_status['excluded'],
		);
	}

	/**
	 * Normalize error code for comparison.
	 *
	 * @param string $error_code Error code.
	 * @return string|null
	 */
	private function normalize_error_code_for_compare( string $error_code ): ?string {
		$error_code = strtolower( trim( $error_code ) );
		$error_code = (string) preg_replace( '/[^a-z0-9_]/', '_', $error_code );
		$error_code = trim( $error_code, '_' );

		return '' === $error_code ? null : substr( $error_code, 0, 64 );
	}
}
