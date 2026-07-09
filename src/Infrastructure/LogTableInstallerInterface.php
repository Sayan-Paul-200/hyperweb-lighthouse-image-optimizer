<?php
/**
 * Log table installer contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Describes a service that installs or upgrades the log table.
 */
interface LogTableInstallerInterface {

	/**
	 * Install or upgrade the log table.
	 *
	 * @return InstallerResult
	 */
	public function install(): InstallerResult;
}
