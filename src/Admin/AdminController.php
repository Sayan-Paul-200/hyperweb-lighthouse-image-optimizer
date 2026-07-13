<?php
/**
 * Admin screen shell controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers and renders the Phase 6.1 admin screen shell.
 */
final class AdminController implements HookProviderInterface {

	public const PRIORITY = 10;

	/**
	 * Menu helper.
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Pages keyed by tab slug.
	 *
	 * @var array<string,AdminPageInterface>
	 */
	private $pages;

	/**
	 * Admin runtime adapter.
	 *
	 * @var AdminRuntimeInterface
	 */
	private $runtime;

	/**
	 * Screen context resolver.
	 *
	 * @var AdminScreenContextResolver
	 */
	private $context_resolver;

	/**
	 * Notice manager.
	 *
	 * @var NoticeManager
	 */
	private $notice_manager;

	/**
	 * Build the default placeholder page list.
	 *
	 * @return AdminPageInterface[]
	 */
	public static function default_pages(): array {
		return array(
			new DashboardPage(),
			new BulkPage(),
			new SettingsPage(),
			new DiagnosticsPage(),
			new LogsPage(),
		);
	}

	/**
	 * Create the controller.
	 *
	 * @param Menu                     $menu Menu helper.
	 * @param AdminPageInterface[]     $pages Screen pages.
	 * @param AdminRuntimeInterface    $runtime Admin runtime adapter.
	 * @param AdminScreenContextResolver $context_resolver Screen context resolver.
	 * @param NoticeManager            $notice_manager Notice manager.
	 */
	public function __construct(
		Menu $menu,
		array $pages,
		AdminRuntimeInterface $runtime,
		AdminScreenContextResolver $context_resolver,
		NoticeManager $notice_manager
	) {
		$this->menu             = $menu;
		$this->pages            = $this->index_pages( $pages );
		$this->runtime          = $runtime;
		$this->context_resolver = $context_resolver;
		$this->notice_manager   = $notice_manager;
	}

	/**
	 * Register the admin-menu hook.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'admin_menu', array( $this, 'register_menu' ), self::PRIORITY, 0 );
	}

	/**
	 * Register the plugin submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->menu->register( array( $this, 'render' ) );
	}

	/**
	 * Render the plugin screen shell.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->runtime->current_user_can( $this->menu->capability() ) ) {
			$this->runtime->forbid(
				$this->translate( 'You do not have permission to access HyperWeb Lighthouse Image Optimizer.' ),
				$this->translate( 'Access denied' ),
				array( 'response' => 403 )
			);

			return;
		}

		$context      = $this->context_resolver->resolve();
		$current_page = $this->resolve_current_page( $context );
		$current_tab  = $context->current_tab();

		echo '<div class="wrap">';
		echo '<h1>' . $this->escape_html( $this->translate( 'Lighthouse Image Optimizer' ) ) . '</h1>';
		echo '<nav class="nav-tab-wrapper" aria-label="' . $this->escape_html( $this->translate( 'Lighthouse Image Optimizer sections' ) ) . '">';

		foreach ( $this->menu->tabs() as $tab ) {
			if ( ! isset( $this->pages[ $tab ] ) ) {
				continue;
			}

			$page  = $this->pages[ $tab ];
			$class = 'nav-tab';

			if ( $tab === $current_tab ) {
				$class .= ' nav-tab-active';
				echo '<a class="' . $this->escape_html( $class ) . '" href="' . $this->escape_url( $this->menu->page_url( $tab ) ) . '" aria-current="page">';
				echo $this->escape_html( $page->title() );
				echo '</a>';
				continue;
			}

			echo '<a class="' . $this->escape_html( $class ) . '" href="' . $this->escape_url( $this->menu->page_url( $tab ) ) . '">';
			echo $this->escape_html( $page->title() );
			echo '</a>';
		}

		echo '</nav>';
		$this->notice_manager->render_containers();
		echo '<div id="' . $this->escape_attr( $this->notice_manager->app_id() ) . '" class="hwlio-admin-app" data-page="' . $this->escape_attr( Menu::MENU_SLUG ) . '" data-tab="' . $this->escape_attr( $current_tab ) . '">';
		$current_page->render();
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Resolve the current page from request data.
	 *
	 * @return AdminPageInterface
	 */
	private function resolve_current_page( AdminScreenContext $context ): AdminPageInterface {
		$tab = $context->current_tab();

		if ( isset( $this->pages[ $tab ] ) ) {
			return $this->pages[ $tab ];
		}

		return $this->pages[ Menu::DEFAULT_TAB ];
	}

	/**
	 * Index pages by slug.
	 *
	 * @param AdminPageInterface[] $pages Pages.
	 * @return array<string,AdminPageInterface>
	 */
	private function index_pages( array $pages ): array {
		$indexed = array();

		foreach ( $pages as $page ) {
			if ( $page instanceof AdminPageInterface ) {
				$indexed[ $page->slug() ] = $page;
			}
		}

		return $indexed;
	}

	/**
	 * Escape HTML text for admin output.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function escape_html( string $text ): string {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $text );
		}

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape one HTML attribute for admin output.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape a URL for admin output.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function escape_url( string $url ): string {
		if ( function_exists( 'esc_url' ) ) {
			return esc_url( $url );
		}

		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}
}
