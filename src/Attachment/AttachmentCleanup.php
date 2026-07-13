<?php
/**
 * Attachment cleanup provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationDispatcherInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\CacheInvalidationRequest;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressCacheInvalidationDispatcher;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressFilesystem;

/**
 * Cleans up plugin-owned state when an attachment is permanently deleted.
 */
final class AttachmentCleanup implements HookProviderInterface {

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Attachment meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Shared derivative file cleaner.
	 *
	 * @var DerivativeFileCleaner
	 */
	private $files;

	/**
	 * Pending attachment job cleaner.
	 *
	 * @var AttachmentJobCleanerInterface
	 */
	private $jobs;

	/**
	 * Source collector.
	 *
	 * @var SourceCollector
	 */
	private $collector;

	/**
	 * Cache invalidation dispatcher.
	 *
	 * @var CacheInvalidationDispatcherInterface|null
	 */
	private $cache_invalidation;

	/**
	 * Build the WordPress-backed cleanup provider.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$meta = new WordPressAttachmentMetaStore();

		return new self(
			new DerivativeRepository(
				$meta,
				new DerivativeManifestSanitizer(),
				new SystemAttachmentClock()
			),
			$meta,
			new DerivativeFileCleaner( self::uploads_base_dir(), new WordPressFilesystem() ),
			ActionSchedulerAttachmentJobCleaner::for_wordpress(),
			SourceCollector::for_wordpress(),
			new WordPressCacheInvalidationDispatcher()
		);
	}

	/**
	 * Create provider.
	 *
	 * @param DerivativeRepository          $repository Derivative repository.
	 * @param AttachmentMetaStoreInterface  $meta Attachment meta store.
	 * @param DerivativeFileCleaner         $files Shared derivative file cleaner.
	 * @param AttachmentJobCleanerInterface $jobs Pending job cleaner.
	 * @param SourceCollector               $collector Source collector.
	 * @param CacheInvalidationDispatcherInterface|null $cache_invalidation Cache invalidation dispatcher.
	 */
	public function __construct(
		DerivativeRepository $repository,
		AttachmentMetaStoreInterface $meta,
		DerivativeFileCleaner $files,
		AttachmentJobCleanerInterface $jobs,
		SourceCollector $collector,
		?CacheInvalidationDispatcherInterface $cache_invalidation = null
	) {
		$this->repository         = $repository;
		$this->meta               = $meta;
		$this->files              = $files;
		$this->jobs               = $jobs;
		$this->collector          = $collector;
		$this->cache_invalidation = $cache_invalidation;
	}

	/**
	 * Register runtime cleanup hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'delete_attachment', array( $this, 'handle_attachment_delete' ), 10, 1 );
	}

	/**
	 * Handle permanent attachment deletion.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function handle_attachment_delete( int $attachment_id ): void {
		$this->cleanup_attachment( $attachment_id );
	}

	/**
	 * Clean plugin-owned state for one attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	public function cleanup_attachment( int $attachment_id ): AttachmentCleanupResult {
		$attachment_id = max( 0, $attachment_id );

		if ( 0 === $attachment_id ) {
			return AttachmentCleanupResult::failure(
				array( AttachmentCleanupResult::CODE_INVALID_ATTACHMENT ),
				array( 'Attachment cleanup requires a valid attachment ID.' )
			);
		}

		$read             = $this->repository->read( $attachment_id );
		$manifest         = $read->manifest();
		$source_files     = DerivativeFileCleaner::source_files_from_manifest( $manifest );
		$derivative_files = DerivativeFileCleaner::derivative_files_from_manifest( $manifest );

		$result = AttachmentCleanupResult::combine(
			$this->repository_warnings( $read ),
			$this->files->cleanup_files( $source_files, $derivative_files ),
			$this->jobs->cancel_pending_actions( $attachment_id ),
			$this->delete_owned_meta( $attachment_id ),
			AttachmentCleanupResult::success(
				array( AttachmentCleanupResult::CODE_COMPLETED ),
				array( 'Attachment cleanup completed.' )
			)
		);

		$this->dispatch_derivatives_deleted( $attachment_id, $result, 'attachment_deleted' );

		return $result;
	}

	/**
	 * Report deterministic orphan derivatives for one attachment in dry-run mode.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	public function dry_run_orphan_reconciliation( int $attachment_id ): AttachmentCleanupResult {
		$attachment_id = max( 0, $attachment_id );

		if ( 0 === $attachment_id ) {
			return AttachmentCleanupResult::failure(
				array( AttachmentCleanupResult::CODE_INVALID_ATTACHMENT ),
				array( 'Orphan reconciliation requires a valid attachment ID.' )
			);
		}

		$read                = $this->repository->read( $attachment_id );
		$manifest            = $read->manifest();
		$authoritative_files = DerivativeFileCleaner::derivative_files_from_manifest( $manifest );
		$candidate_sources   = array_keys( DerivativeFileCleaner::source_files_from_manifest( $manifest ) );
		$collection          = $this->collector->collect( $attachment_id );

		foreach ( $collection->sources() as $source ) {
			$candidate_sources[] = $source->relative_path();
		}

		return AttachmentCleanupResult::combine(
			$this->repository_warnings( $read ),
			$this->files->find_existing_orphans( $candidate_sources, $authoritative_files )
		);
	}

	/**
	 * Convert repository warnings into cleanup warnings.
	 *
	 * @param DerivativeRepositoryResult $read Repository read result.
	 * @return AttachmentCleanupResult
	 */
	private function repository_warnings( DerivativeRepositoryResult $read ): AttachmentCleanupResult {
		if ( ! $read->has_warnings() ) {
			return AttachmentCleanupResult::success();
		}

		return AttachmentCleanupResult::warning( $read->codes(), $read->messages() );
	}

	/**
	 * Delete plugin-owned attachment meta idempotently.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return AttachmentCleanupResult
	 */
	private function delete_owned_meta( int $attachment_id ): AttachmentCleanupResult {
		$deleted  = 0;
		$warnings = array();
		$missing  = new \stdClass();

		foreach ( LifecyclePolicy::owned_attachment_meta_keys() as $key ) {
			$current = $this->meta->get( $attachment_id, $key, $missing );

			if ( $current === $missing ) {
				continue;
			}

			if ( ! $this->meta->delete( $attachment_id, $key ) ) {
				$warnings[] = sprintf( 'Attachment meta key %s could not be deleted.', $key );
				continue;
			}

			++$deleted;
		}

		$result = AttachmentCleanupResult::success(
			array( AttachmentCleanupResult::CODE_ATTACHMENT_META_DELETED ),
			array( sprintf( 'Deleted %d plugin-owned attachment meta key(s).', $deleted ) ),
			0,
			0,
			$deleted
		);

		if ( array() === $warnings ) {
			return $result;
		}

		return AttachmentCleanupResult::combine(
			$result,
			AttachmentCleanupResult::warning(
				array( AttachmentCleanupResult::CODE_ATTACHMENT_META_DELETE_FAILED ),
				$warnings
			)
		);
	}

	/**
	 * Dispatch cache invalidation for deleted derivative files.
	 *
	 * @param int                     $attachment_id Attachment ID.
	 * @param AttachmentCleanupResult $result Cleanup result.
	 * @param string                  $reason Reason.
	 * @return void
	 */
	private function dispatch_derivatives_deleted( int $attachment_id, AttachmentCleanupResult $result, string $reason ): void {
		if (
			! $this->cache_invalidation instanceof CacheInvalidationDispatcherInterface
			|| 0 >= $result->deleted_files()
			|| array() === $result->deleted_relative_paths()
		) {
			return;
		}

		$this->cache_invalidation->dispatch(
			new CacheInvalidationRequest(
				CacheInvalidationRequest::EVENT_DERIVATIVES_DELETED,
				$attachment_id,
				$reason,
				$result->deleted_relative_paths(),
				$this->formats_from_paths( $result->deleted_relative_paths() ),
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}

	/**
	 * Extract known derivative formats from relative paths.
	 *
	 * @param string[] $paths Relative paths.
	 * @return string[]
	 */
	private function formats_from_paths( array $paths ): array {
		$formats = array();

		foreach ( $paths as $path ) {
			if ( 1 === preg_match( '/\.hwlio\.(webp|avif)$/', (string) $path, $matches ) ) {
				$formats[] = $matches[1];
			}
		}

		return array_values( array_unique( $formats ) );
	}

	/**
	 * Resolve uploads base directory.
	 *
	 * @return string
	 */
	private static function uploads_base_dir(): string {
		if ( ! function_exists( 'wp_get_upload_dir' ) ) {
			return '';
		}

		$uploads = \wp_get_upload_dir();

		if ( ! is_array( $uploads ) || empty( $uploads['basedir'] ) || ! is_string( $uploads['basedir'] ) ) {
			return '';
		}

		return $uploads['basedir'];
	}
}
