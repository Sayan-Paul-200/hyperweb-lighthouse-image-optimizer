<?php
/**
 * Fake log table installer for installer tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\InstallerResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LogTableInstallerInterface;

/**
 * Returns a preconfigured install result.
 */
final class FakeLogTableInstaller implements LogTableInstallerInterface {

	/**
	 * Install result.
	 *
	 * @var InstallerResult
	 */
	private $result;

	/**
	 * Number of install calls.
	 *
	 * @var int
	 */
	public $install_calls = 0;

	/**
	 * Create the fake installer.
	 *
	 * @param InstallerResult $result Install result.
	 */
	public function __construct( InstallerResult $result ) {
		$this->result = $result;
	}

	/**
	 * Install the log table.
	 *
	 * @return InstallerResult
	 */
	public function install(): InstallerResult {
		++$this->install_calls;

		return $this->result;
	}
}
