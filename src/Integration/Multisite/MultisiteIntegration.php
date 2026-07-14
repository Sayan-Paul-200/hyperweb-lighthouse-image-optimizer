<?php
/**
 * Multisite integration provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Multisite;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer;

/**
 * Initializes new multisite sites when the plugin is network-active.
 */
final class MultisiteIntegration implements HookProviderInterface {

	/**
	 * Site-context runtime.
	 *
	 * @var SiteContextRuntimeInterface
	 */
	private $runtime;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * DB version.
	 *
	 * @var string
	 */
	private $db_version;

	/**
	 * Schema version.
	 *
	 * @var int
	 */
	private $schema_version;

	/**
	 * Optional installer factory.
	 *
	 * @var callable|null
	 */
	private $installer_factory;

	/**
	 * Create integration.
	 *
	 * @param SiteContextRuntimeInterface $runtime Site-context runtime.
	 * @param string                      $plugin_basename Plugin basename.
	 * @param string                      $version Plugin version.
	 * @param string                      $db_version DB version.
	 * @param int                         $schema_version Schema version.
	 * @param callable|null               $installer_factory Optional installer factory.
	 */
	public function __construct(
		SiteContextRuntimeInterface $runtime,
		string $plugin_basename,
		string $version,
		string $db_version,
		int $schema_version,
		?callable $installer_factory = null
	) {
		$this->runtime           = $runtime;
		$this->plugin_basename   = $plugin_basename;
		$this->version           = $version;
		$this->db_version        = $db_version;
		$this->schema_version    = $schema_version;
		$this->installer_factory = $installer_factory;
	}

	/**
	 * Register only the new-site initialization hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'wp_initialize_site', array( $this, 'initialize_site' ), 10, 2 );
	}

	/**
	 * Initialize plugin state for one newly created site when network-active.
	 *
	 * @param mixed                $site Site object.
	 * @param array<string,mixed> $args Initialization args.
	 * @return void
	 */
	public function initialize_site( $site, array $args = array() ): void {
		unset( $args );

		if ( ! $this->runtime->is_multisite() || ! $this->runtime->plugin_network_active( $this->plugin_basename ) ) {
			return;
		}

		$site_id = $this->site_id_from_payload( $site );

		if ( $site_id < 1 ) {
			return;
		}

		$this->runtime->switch_to_site( $site_id );

		try {
			$installer = $this->installer();

			if ( $installer instanceof Installer ) {
				$installer->install();
			}
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		} finally {
			$this->runtime->restore_site();
		}
	}

	/**
	 * Build the installer for one site.
	 *
	 * @return Installer
	 */
	private function installer(): Installer {
		if ( null !== $this->installer_factory ) {
			$installer = call_user_func( $this->installer_factory, $this->version, $this->db_version, $this->schema_version );

			if ( $installer instanceof Installer ) {
				return $installer;
			}
		}

		return Installer::for_wordpress( $this->version, $this->db_version, $this->schema_version );
	}

	/**
	 * Extract a numeric site ID from the hook payload.
	 *
	 * @param mixed $site Site payload.
	 * @return int
	 */
	private function site_id_from_payload( $site ): int {
		if ( is_object( $site ) ) {
			if ( isset( $site->blog_id ) && is_numeric( $site->blog_id ) ) {
				return max( 0, (int) $site->blog_id );
			}

			if ( isset( $site->id ) && is_numeric( $site->id ) ) {
				return max( 0, (int) $site->id );
			}
		}

		if ( is_array( $site ) ) {
			if ( isset( $site['blog_id'] ) && is_numeric( $site['blog_id'] ) ) {
				return max( 0, (int) $site['blog_id'] );
			}

			if ( isset( $site['id'] ) && is_numeric( $site['id'] ) ) {
				return max( 0, (int) $site['id'] );
			}
		}

		return 0;
	}
}
