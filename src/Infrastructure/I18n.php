<?php
/**
 * Internationalization hook provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Registers and loads the plugin textdomain.
 */
final class I18n implements HookProviderInterface {

	/**
	 * Plugin textdomain.
	 *
	 * @var string
	 */
	private $text_domain;

	/**
	 * Relative languages directory passed to WordPress.
	 *
	 * @var string
	 */
	private $languages_path;

	/**
	 * Create the i18n provider.
	 *
	 * @param string $text_domain Plugin textdomain.
	 * @param string $languages_path Relative languages directory.
	 */
	public function __construct( string $text_domain, string $languages_path ) {
		$this->text_domain    = $text_domain;
		$this->languages_path = $languages_path;
	}

	/**
	 * Register textdomain loading.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load the plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		\load_plugin_textdomain(
			$this->text_domain,
			false,
			$this->languages_path
		);
	}

	/**
	 * Get the configured textdomain.
	 *
	 * @return string
	 */
	public function text_domain(): string {
		return $this->text_domain;
	}

	/**
	 * Get the configured relative languages path.
	 *
	 * @return string
	 */
	public function languages_path(): string {
		return $this->languages_path;
	}
}
