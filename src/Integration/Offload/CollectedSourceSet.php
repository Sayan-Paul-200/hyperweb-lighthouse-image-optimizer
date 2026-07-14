<?php
/**
 * Collected source set.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;

/**
 * Carries collected sources plus request-scoped temporary-source leases.
 */
final class CollectedSourceSet {

	/**
	 * Source collection.
	 *
	 * @var SourceImageCollection
	 */
	private $collection;

	/**
	 * Temporary source leases.
	 *
	 * @var TemporarySourceLease[]
	 */
	private $leases;

	/**
	 * Create source set.
	 *
	 * @param SourceImageCollection  $collection Source collection.
	 * @param TemporarySourceLease[] $leases Temporary source leases.
	 */
	public function __construct( SourceImageCollection $collection, array $leases = array() ) {
		$this->collection = $collection;
		$this->leases     = array_values(
			array_filter(
				$leases,
				static function ( $lease ): bool {
					return $lease instanceof TemporarySourceLease;
				}
			)
		);
	}

	/**
	 * Build a lease-free source set from one local collection.
	 *
	 * @param SourceImageCollection $collection Source collection.
	 * @return self
	 */
	public static function from_collection( SourceImageCollection $collection ): self {
		return new self( $collection );
	}

	/**
	 * Get collection.
	 *
	 * @return SourceImageCollection
	 */
	public function collection(): SourceImageCollection {
		return $this->collection;
	}

	/**
	 * Release all temporary leases idempotently.
	 *
	 * @return void
	 */
	public function release(): void {
		foreach ( $this->leases as $lease ) {
			$lease->release();
		}
	}
}
