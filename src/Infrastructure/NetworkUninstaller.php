<?php
/**
 * Network uninstall orchestration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Runs per-site uninstall cleanup in bounded multisite batches.
 */
final class NetworkUninstaller {

	private const DEFAULT_BATCH_SIZE = 100;

	/**
	 * Site IDs provider.
	 *
	 * @var callable
	 */
	private $site_ids_provider;

	/**
	 * Site switcher.
	 *
	 * @var callable
	 */
	private $switch_site;

	/**
	 * Site restorer.
	 *
	 * @var callable
	 */
	private $restore_site;

	/**
	 * Per-site uninstall runner.
	 *
	 * @var callable
	 */
	private $site_uninstaller;

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * Build a WordPress-backed network uninstaller.
	 *
	 * @param callable $site_uninstaller Per-site uninstall runner.
	 * @return self
	 */
	public static function for_wordpress( callable $site_uninstaller ): self {
		return new self(
			static function ( int $offset, int $limit ): array {
				if ( ! function_exists( 'get_sites' ) ) {
					return array();
				}

				$site_ids = \get_sites(
					array(
						'fields' => 'ids',
						'number' => $limit,
						'offset' => $offset,
					)
				);

				return array_map( 'intval', $site_ids );
			},
			static function ( int $site_id ): void {
				if ( function_exists( 'switch_to_blog' ) ) {
					\switch_to_blog( $site_id );
				}
			},
			static function (): void {
				if ( function_exists( 'restore_current_blog' ) ) {
					\restore_current_blog();
				}
			},
			$site_uninstaller
		);
	}

	/**
	 * Create the network uninstaller.
	 *
	 * @param callable $site_ids_provider Provides site IDs for offset and limit.
	 * @param callable $switch_site Switches to one site ID.
	 * @param callable $restore_site Restores the previous site.
	 * @param callable $site_uninstaller Runs uninstall cleanup for the current site.
	 * @param int      $batch_size Batch size.
	 */
	public function __construct(
		callable $site_ids_provider,
		callable $switch_site,
		callable $restore_site,
		callable $site_uninstaller,
		int $batch_size = self::DEFAULT_BATCH_SIZE
	) {
		$this->site_ids_provider = $site_ids_provider;
		$this->switch_site       = $switch_site;
		$this->restore_site      = $restore_site;
		$this->site_uninstaller  = $site_uninstaller;
		$this->batch_size        = max( 1, $batch_size );
	}

	/**
	 * Run uninstall cleanup across sites.
	 *
	 * @return LifecycleResult
	 */
	public function uninstall(): LifecycleResult {
		$offset  = 0;
		$results = array();

		do {
			$site_ids = call_user_func( $this->site_ids_provider, $offset, $this->batch_size );

			if ( ! is_array( $site_ids ) ) {
				$site_ids = array();
			}

			foreach ( $site_ids as $site_id ) {
				call_user_func( $this->switch_site, (int) $site_id );

				try {
					$result = call_user_func( $this->site_uninstaller );

					if ( $result instanceof LifecycleResult ) {
						$results[] = $result;
					}
				} catch ( \Throwable $throwable ) {
					$results[] = LifecycleResult::warning(
						array( LifecycleResult::CODE_NETWORK_SITE_FAILED ),
						array( $throwable->getMessage() )
					);
				} finally {
					call_user_func( $this->restore_site );
				}
			}

			$site_count = count( $site_ids );

			if ( 0 < $site_count ) {
				$results[] = LifecycleResult::success( array( LifecycleResult::CODE_NETWORK_BATCH_PROCESSED ) );
			}

			$offset += $this->batch_size;
		} while ( $site_count === $this->batch_size );

		$results[] = LifecycleResult::success( array( LifecycleResult::CODE_NETWORK_UNINSTALL_COMPLETED ) );

		return LifecycleResult::combine( ...$results );
	}
}
