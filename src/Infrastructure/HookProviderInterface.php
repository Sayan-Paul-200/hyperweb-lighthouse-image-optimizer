<?php
/**
 * Hook provider contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Describes a service that registers WordPress hooks through the registrar.
 */
interface HookProviderInterface {

	/**
	 * Register this provider's hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void;
}
