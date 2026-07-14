<?php
/**
 * CLI command provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers the plugin's WP-CLI root command.
 */
final class CliCommands implements HookProviderInterface {

	/**
	 * CLI runtime.
	 *
	 * @var CliRuntimeInterface
	 */
	private $runtime;

	/**
	 * Root command object.
	 *
	 * @var object
	 */
	private $command;

	/**
	 * Create the provider.
	 *
	 * @param CliRuntimeInterface $runtime CLI runtime.
	 * @param object              $command Root command object.
	 */
	public function __construct( CliRuntimeInterface $runtime, object $command ) {
		$this->runtime = $runtime;
		$this->command = $command;
	}

	/**
	 * Register provider hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'cli_init', array( $this, 'register_command' ), 10, 0 );
	}

	/**
	 * Register the root `hwlio` CLI command when WP-CLI is available.
	 *
	 * @return void
	 */
	public function register_command(): void {
		if ( ! $this->runtime->available() ) {
			return;
		}

		$this->runtime->register_command( 'hwlio', $this->command );
	}
}
