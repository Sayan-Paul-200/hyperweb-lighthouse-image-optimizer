<?php
/**
 * Safe derivative cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentCleanupResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeFileCleaner;

/**
 * Deletes only attachment-owned derivative files recorded in plugin metadata.
 */
final class DerivativeCleanup implements DerivativeCleanupInterface {

	/**
	 * Manifest provider.
	 *
	 * @var DerivativeManifestProviderInterface
	 */
	private $manifests;

	/**
	 * Shared derivative file cleaner.
	 *
	 * @var DerivativeFileCleaner
	 */
	private $cleaner;

	/**
	 * Create the cleanup service.
	 *
	 * @param string                              $uploads_base_dir Uploads base directory.
	 * @param FilesystemInterface                 $filesystem Filesystem adapter.
	 * @param DerivativeManifestProviderInterface $manifests Manifest provider.
	 */
	public function __construct(
		string $uploads_base_dir,
		FilesystemInterface $filesystem,
		DerivativeManifestProviderInterface $manifests
	) {
		$this->manifests = $manifests;
		$this->cleaner   = new DerivativeFileCleaner( $uploads_base_dir, $filesystem );
	}

	/**
	 * Delete eligible derivative files.
	 *
	 * @return LifecycleResult
	 */
	public function cleanup(): LifecycleResult {
		$results = array();

		foreach ( $this->manifests->manifests() as $manifest ) {
			if ( ! is_array( $manifest ) ) {
				continue;
			}

			$results[] = $this->cleaner->cleanup_files(
				DerivativeFileCleaner::source_files_from_manifest_array( $manifest ),
				DerivativeFileCleaner::derivative_files_from_manifest_array( $manifest )
			);
		}

		if ( array() === $results ) {
			$results[] = AttachmentCleanupResult::success(
				array( AttachmentCleanupResult::CODE_DERIVATIVES_DELETED ),
				array( 'Deleted 0 derivative file(s).' )
			);
		}

		return $this->to_lifecycle_result( AttachmentCleanupResult::combine( ...$results ) );
	}

	/**
	 * Convert cleanup result into lifecycle result.
	 *
	 * @param AttachmentCleanupResult $result Cleanup result.
	 * @return LifecycleResult
	 */
	private function to_lifecycle_result( AttachmentCleanupResult $result ): LifecycleResult {
		if ( AttachmentCleanupResult::SEVERITY_FAILURE === $result->severity() ) {
			return LifecycleResult::failure( $result->codes(), $result->messages() );
		}

		if ( AttachmentCleanupResult::SEVERITY_WARNING === $result->severity() ) {
			return LifecycleResult::warning( $result->codes(), $result->messages() );
		}

		return LifecycleResult::success( $result->codes(), $result->messages() );
	}
}
