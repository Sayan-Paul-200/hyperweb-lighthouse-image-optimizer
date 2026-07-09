<?php
/**
 * Runtime upgrade runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Runs installer upgrades when stored versions are stale.
 */
final class UpgradeRunner implements HookProviderInterface {

	/**
	 * Installer.
	 *
	 * @var Installer
	 */
	private $installer;

	/**
	 * Create the upgrade runner.
	 *
	 * @param Installer $installer Installer.
	 */
	public function __construct( Installer $installer ) {
		$this->installer = $installer;
	}

	/**
	 * Register runtime upgrade checks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ), 1, 0 );
	}

	/**
	 * Run installer if stored versions are stale.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		if ( $this->installer->needs_upgrade() ) {
			$this->installer->install();
		}
	}

	/**
	 * Get the installer.
	 *
	 * @return Installer
	 */
	public function installer(): Installer {
		return $this->installer;
	}
}
