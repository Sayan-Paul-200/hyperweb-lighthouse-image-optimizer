<?php
/**
 * Fake site-context runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Multisite;

use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\SiteContextRuntimeInterface;

/**
 * Provides deterministic site-context behavior for multisite tests.
 */
final class FakeSiteContextRuntime implements SiteContextRuntimeInterface {

	/**
	 * Current site ID.
	 *
	 * @var int
	 */
	public $current_site_id = 1;

	/**
	 * Whether multisite is active.
	 *
	 * @var bool
	 */
	public $is_multisite = true;

	/**
	 * Network-active plugin basenames.
	 *
	 * @var string[]
	 */
	public $network_active_plugins = array();

	/**
	 * Switched site IDs.
	 *
	 * @var int[]
	 */
	public $switched_sites = array();

	/**
	 * Restore call count.
	 *
	 * @var int
	 */
	public $restore_calls = 0;

	/**
	 * {@inheritDoc}
	 */
	public function current_site_id(): int {
		return $this->current_site_id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_multisite(): bool {
		return $this->is_multisite;
	}

	/**
	 * {@inheritDoc}
	 */
	public function plugin_network_active( string $plugin_basename ): bool {
		return in_array( $plugin_basename, $this->network_active_plugins, true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function switch_to_site( int $site_id ): void {
		$this->current_site_id = $site_id;
		$this->switched_sites[] = $site_id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function restore_site(): void {
		++$this->restore_calls;
	}
}
