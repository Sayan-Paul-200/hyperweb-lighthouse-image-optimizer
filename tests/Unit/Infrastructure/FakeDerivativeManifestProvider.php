<?php
/**
 * Fake derivative manifest provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\DerivativeManifestProviderInterface;

/**
 * Provides test derivative manifests.
 */
final class FakeDerivativeManifestProvider implements DerivativeManifestProviderInterface {

	/**
	 * Manifests keyed by attachment ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $manifests;

	/**
	 * Create the provider.
	 *
	 * @param array<int,array<string,mixed>> $manifests Manifests.
	 */
	public function __construct( array $manifests ) {
		$this->manifests = $manifests;
	}

	/**
	 * Get derivative manifests keyed by attachment ID.
	 *
	 * @return iterable<int,array<string,mixed>>
	 */
	public function manifests(): iterable {
		foreach ( $this->manifests as $attachment_id => $manifest ) {
			yield $attachment_id => $manifest;
		}
	}
}
