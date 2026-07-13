<?php
/**
 * Elementor background stylesheet manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Lazily regenerates and enqueues plugin-owned Elementor background companion CSS.
 */
final class ElementorBackgroundStylesheetManager implements HookProviderInterface {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Elementor runtime seam.
	 *
	 * @var ElementorRuntimeInterface
	 */
	private $elementor_runtime;

	/**
	 * CSS runtime seam.
	 *
	 * @var ElementorBackgroundStylesheetRuntimeInterface
	 */
	private $runtime;

	/**
	 * Stylesheet generator.
	 *
	 * @var ElementorBackgroundStylesheetGenerator
	 */
	private $generator;

	/**
	 * Artifact store.
	 *
	 * @var ElementorBackgroundStylesheetStoreInterface
	 */
	private $store;

	/**
	 * Create manager.
	 *
	 * @param SettingsRepositoryInterface                   $settings Settings repository.
	 * @param ElementorRuntimeInterface                     $elementor_runtime Elementor request-mode runtime.
	 * @param ElementorBackgroundStylesheetRuntimeInterface $runtime CSS runtime seam.
	 * @param ElementorBackgroundStylesheetGenerator        $generator Stylesheet generator.
	 * @param ElementorBackgroundStylesheetStoreInterface   $store Artifact store.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		ElementorRuntimeInterface $elementor_runtime,
		ElementorBackgroundStylesheetRuntimeInterface $runtime,
		ElementorBackgroundStylesheetGenerator $generator,
		ElementorBackgroundStylesheetStoreInterface $store
	) {
		$this->settings          = $settings;
		$this->elementor_runtime = $elementor_runtime;
		$this->runtime           = $runtime;
		$this->generator         = $generator;
		$this->store             = $store;
	}

	/**
	 * Register runtime hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_current_document_stylesheet' ), 99, 0 );
	}

	/**
	 * Enqueue the current document companion stylesheet when eligible.
	 *
	 * @return void
	 */
	public function enqueue_current_document_stylesheet(): void {
		if ( ! $this->settings->delivery_enabled() || $this->settings->delivery_emergency_disabled() ) {
			return;
		}

		if ( ! $this->runtime->is_frontend_request() ) {
			return;
		}

		if ( ! $this->elementor_runtime->is_available() || $this->elementor_runtime->is_editor_mode() || $this->elementor_runtime->is_preview_mode() ) {
			return;
		}

		$document_id = $this->runtime->current_singular_document_id();

		if ( 1 > $document_id ) {
			return;
		}

		$result = $this->regenerate_document( $document_id );

		if ( ! $result->is_ready() || ! $result->has_rules() || null === $result->url() ) {
			return;
		}

		$this->runtime->enqueue_stylesheet(
			'hwlio-elementor-backgrounds-' . $document_id,
			$result->url(),
			'' !== $result->signature() ? $result->signature() : '1'
		);
	}

	/**
	 * Regenerate one document companion stylesheet when needed.
	 *
	 * @param int $document_id Document ID.
	 * @return ElementorBackgroundStylesheetResult
	 */
	public function regenerate_document( int $document_id ): ElementorBackgroundStylesheetResult {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return ElementorBackgroundStylesheetResult::noop(
				0,
				ElementorBackgroundStylesheetResult::CODE_DOCUMENT_UNAVAILABLE
			);
		}

		$generated = $this->generator->generate( $document_id, $this->runtime->breakpoint_map() );

		if ( $generated->is_failure() ) {
			return $generated->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		if ( ! $generated->has_rules() ) {
			if ( $this->store->exists( $document_id ) ) {
				return $this->rollback_document( $document_id );
			}

			return $generated->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		$current_contents  = $this->store->read( $document_id );
		$current_signature = is_string( $current_contents ) ? $this->extract_signature( $current_contents ) : '';

		if ( '' !== $current_signature && $current_signature === $generated->signature() ) {
			return ElementorBackgroundStylesheetResult::ready(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_STYLESHEET_CURRENT,
				true,
				$generated->rule_count(),
				$generated->signature(),
				$generated->css()
			)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		if ( ! $this->store->write( $document_id, $generated->css() ) ) {
			return ElementorBackgroundStylesheetResult::failure(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_STYLESHEET_WRITE_FAILED
			)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		return ElementorBackgroundStylesheetResult::ready(
			$document_id,
			ElementorBackgroundStylesheetResult::CODE_STYLESHEET_WRITTEN,
			true,
			$generated->rule_count(),
			$generated->signature(),
			$generated->css()
		)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
	}

	/**
	 * Roll back one document companion stylesheet.
	 *
	 * @param int $document_id Document ID.
	 * @return ElementorBackgroundStylesheetResult
	 */
	public function rollback_document( int $document_id ): ElementorBackgroundStylesheetResult {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return ElementorBackgroundStylesheetResult::noop(
				0,
				ElementorBackgroundStylesheetResult::CODE_DOCUMENT_UNAVAILABLE
			);
		}

		if ( ! $this->store->exists( $document_id ) ) {
			return ElementorBackgroundStylesheetResult::noop(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_STYLESHEET_DELETED
			)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		if ( ! $this->store->delete( $document_id ) ) {
			return ElementorBackgroundStylesheetResult::failure(
				$document_id,
				ElementorBackgroundStylesheetResult::CODE_STYLESHEET_DELETE_FAILED
			)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
		}

		return ElementorBackgroundStylesheetResult::noop(
			$document_id,
			ElementorBackgroundStylesheetResult::CODE_STYLESHEET_DELETED
		)->with_artifact( $this->store->relative_path( $document_id ), $this->store->url( $document_id ) );
	}

	/**
	 * Extract one stored stylesheet signature from the header comment.
	 *
	 * @param string $contents Stylesheet contents.
	 * @return string
	 */
	private function extract_signature( string $contents ): string {
		if ( preg_match( '/signature:([a-f0-9]{64})/i', $contents, $matches ) ) {
			return strtolower( $matches[1] );
		}

		return '';
	}
}
