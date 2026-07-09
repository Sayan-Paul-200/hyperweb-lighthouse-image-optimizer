<?php
/**
 * Derivative manifest provider contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Provides attachment-owned derivative manifests.
 */
interface DerivativeManifestProviderInterface {

	/**
	 * Get derivative manifests keyed by attachment ID.
	 *
	 * @return iterable<int,array<string,mixed>>
	 */
	public function manifests(): iterable;
}
