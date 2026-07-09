<?php
/**
 * Application composition root.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n;

/**
 * Builds shared services and registers plugin hooks.
 */
final class Plugin {

	/**
	 * Stable plugin slug.
	 *
	 * @var string
	 */
	public const SLUG = 'hyperweb-lighthouse-image-optimizer';

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Shared hook registrar.
	 *
	 * @var HookRegistrar
	 */
	private $hooks;

	/**
	 * Hook providers owned by this composition root.
	 *
	 * @var HookProviderInterface[]
	 */
	private $providers;

	/**
	 * Whether providers have already registered their hooks.
	 *
	 * @var bool
	 */
	private $hooks_registered = false;

	/**
	 * Create the production plugin instance.
	 *
	 * @return self
	 */
	public static function create(): self {
		$version = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION' )
			: '0.1.0-alpha.3';

		$basename = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_BASENAME' )
			: self::SLUG . '/hyperweb-lighthouse-image-optimizer.php';

		$hooks = new HookRegistrar();

		return new self(
			$version,
			$hooks,
			array(
				new I18n( self::SLUG, dirname( $basename ) . '/languages/' ),
			)
		);
	}

	/**
	 * Create the composition root.
	 *
	 * @param string                  $version Plugin version.
	 * @param HookRegistrar           $hooks Shared hook registrar.
	 * @param HookProviderInterface[] $providers Hook providers.
	 */
	public function __construct( string $version, HookRegistrar $hooks, array $providers ) {
		$this->version   = $version;
		$this->hooks     = $hooks;
		$this->providers = $providers;
	}

	/**
	 * Register provider hooks and pass them to WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->register_hooks();
		$this->hooks->register();
	}

	/**
	 * Register provider hooks with the shared registrar.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( $this->hooks_registered ) {
			return;
		}

		foreach ( $this->providers as $provider ) {
			$provider->register_hooks( $this->hooks );
		}

		$this->hooks_registered = true;
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function version(): string {
		return $this->version;
	}

	/**
	 * Get the shared hook registrar.
	 *
	 * @return HookRegistrar
	 */
	public function hooks(): HookRegistrar {
		return $this->hooks;
	}

	/**
	 * Get configured hook providers.
	 *
	 * @return HookProviderInterface[]
	 */
	public function providers(): array {
		return $this->providers;
	}
}
